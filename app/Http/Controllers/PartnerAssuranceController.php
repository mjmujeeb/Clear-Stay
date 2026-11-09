<?php

namespace App\Http\Controllers;

use App\Models\IntegrationPartner;
use App\Models\IntegrationTestRun;
use App\Models\IntegrationTestSuite;
use App\Models\PartnerContract;
use App\Services\Integration\ContractValidatorService;
use App\Services\Integration\ScenarioRunnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerAssuranceController extends Controller
{
    protected ContractValidatorService $contractService;
    protected ScenarioRunnerService $scenarioService;

    public function __construct(ContractValidatorService $contractService, ScenarioRunnerService $scenarioService)
    {
        $this->contractService = $contractService;
        $this->scenarioService = $scenarioService;
    }

    /**
     * Partner Registry & Assurance Hub
     */
    public function index()
    {
        $partners = IntegrationPartner::with(['contracts', 'testSuites.testCases'])->get();
        $recentRuns = IntegrationTestRun::with(['suite', 'partner', 'runner'])
            ->latest()
            ->take(8)
            ->get();

        return view('partner_assurance.index', compact('partners', 'recentRuns'));
    }

    /**
     * Show specific partner integration workspace.
     */
    public function show(Request $request, IntegrationPartner $partner)
    {
        $partner->load([
            'contracts',
            'testSuites.testCases',
            'eventTraces' => fn($q) => $q->latest()->take(10),
            'readinessReports' => fn($q) => $q->latest(),
        ]);

        return view('partner_assurance.show', compact('partner'));
    }

    /**
     * Run all scenarios for this partner.
     */
    public function runScenarios(Request $request, IntegrationPartner $partner)
    {
        $suites = IntegrationTestSuite::where('partner_id', $partner->id)->with('testCases')->get();

        if ($suites->isEmpty()) {
            // Create a default suite on the fly if none exists
            $suite = IntegrationTestSuite::create([
                'partner_id' => $partner->id,
                'suite_name' => 'Default Verification Suite',
                'category' => 'reservations',
            ]);
            $suites = collect([$suite]);
        }

        $totalPassed = 0;
        $totalCases = 0;
        foreach ($suites as $suite) {
            $run = $this->scenarioService->runSuite($suite, Auth::id());
            $totalPassed += $run->passed_cases;
            $totalCases += $run->total_cases;
        }

        return back()->with('success', "Scenario automation complete for {$partner->name}: {$totalPassed}/{$totalCases} tests passed. Telemetry traces created in EventTrace Hub.");
    }

    /**
     * Validate an arbitrary JSON payload or active contract against the partner's contract.
     */
    public function validateSchema(Request $request, IntegrationPartner $partner)
    {
        $contract = $partner->contracts()->where('is_active', true)->first();

        if (!$contract) {
            return back()->with('error', 'No active contract specification found for this partner.');
        }

        $rawJson = $request->input('payload_json');
        $payload = $rawJson ? json_decode($rawJson, true) : ['test' => true, 'timestamp' => now()->toIso8601String()];

        $result = $this->contractService->validatePayload($contract, $payload);

        if ($result['is_valid']) {
            return back()->with('success', "Contract validation PASSED against schema version {$contract->version}.");
        }

        return back()->with('info', "Contract validation analyzed: " . implode(' | ', $result['errors'] ?? ['Schema verified']));
    }

    /**
     * Upload / update partner contract specification.
     */
    public function uploadContract(Request $request, IntegrationPartner $partner)
    {
        $request->validate([
            'contract_name' => 'required|string',
            'version' => 'required|string',
            'spec_content' => 'required|string',
        ]);

        PartnerContract::create([
            'partner_id' => $partner->id,
            'contract_name' => $request->contract_name,
            'version' => $request->version,
            'schema_format' => 'openapi3',
            'spec_content' => $request->spec_content,
            'is_active' => true,
        ]);

        return back()->with('success', "New contract version {$request->version} registered successfully.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\IntegrationPartner;
use App\Models\PartnerReadinessReport;
use App\Services\Integration\ReadinessReportService;
use App\Services\Governance\HybridAccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadinessCentreController extends Controller
{
    public function __construct(
        protected ReadinessReportService $readinessService,
        protected HybridAccessControlService $accessControl
    ) {}

    public function index(Request $request)
    {
        $hotelId = $request->user()->hotel_id;
        
        $decision = $this->accessControl->evaluate($request->user(), 'partner.read', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return redirect()->route('dashboard')->with('error', 'Access denied: ' . $decision->getReason());
        }

        $partners = IntegrationPartner::with(['contracts', 'latestReadinessReport'])->get();
        $reports = PartnerReadinessReport::with('partner')->latest()->paginate(15);

        return view('readiness.index', compact('partners', 'reports'));
    }

    public function show(Request $request, PartnerReadinessReport $report)
    {
        $report->load([
            'partner.contracts',
            'partner.testSuites.testCases',
            'partner.testSuites.testRuns',
            'partner.eventTraces' => fn($q) => $q->latest()->take(15),
            'partner.readinessReports' => fn($q) => $q->latest()->take(10),
            'evaluator',
        ]);

        return view('readiness.show', compact('report'));
    }

    public function generate(Request $request, IntegrationPartner $partner)
    {
        $hotelId = $request->user()?->hotel_id;

        $decision = $this->accessControl->evaluate($request->user(), 'partner.test_execute', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $idempotencyToken = $request->input('idempotency_token') ?? $request->header('X-Idempotency-Token');

        // Server-Side Duplicate Submission Protection:
        // If the exact same request token is re-submitted, return the already generated report safely
        if ($idempotencyToken) {
            $existingReport = PartnerReadinessReport::where('partner_id', $partner->id)
                ->whereJsonContains('evidence_data->idempotency_token', $idempotencyToken)
                ->first();

            if ($existingReport) {
                return redirect()->route('readiness.show', $existingReport)
                    ->with('info', "Report already generated for this submission (Report Code: {$existingReport->report_code}).");
            }
        }

        // Concurrency-safe atomic lock to serialize simultaneous submissions with the exact same token
        $lockKey = $idempotencyToken ? "readiness_token_lock_{$partner->id}_{$idempotencyToken}" : null;
        $lock = $lockKey ? Cache::lock($lockKey, 10) : null;

        if ($lock && !$lock->get()) {
            // Concurrent request with same token, wait and retrieve created report
            $lock->block(3);
            $existingReport = PartnerReadinessReport::where('partner_id', $partner->id)
                ->whereJsonContains('evidence_data->idempotency_token', $idempotencyToken)
                ->first();

            if ($existingReport) {
                return redirect()->route('readiness.show', $existingReport)
                    ->with('info', "Report already generated for this submission (Report Code: {$existingReport->report_code}).");
            }
        }

        try {
            $report = $this->readinessService->generateReport(
                $partner,
                $request->user()?->id,
                $idempotencyToken
            );
        } finally {
            if ($lock) {
                $lock->release();
            }
        }

        return redirect()->route('readiness.show', $report)->with('success', "Readiness evaluation generated successfully for {$partner->name}. Evaluation score: {$report->score_percentage}% ({$report->status_label}).");
    }

    public function exportCsv(Request $request, PartnerReadinessReport $report): StreamedResponse
    {
        $csvContent = $this->readinessService->exportCsv($report);
        $fileName = 'readiness_report_' . $report->partner->slug . '_' . $report->created_at->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\IntegrationPartner;
use App\Models\PartnerReadinessReport;
use App\Services\Integration\ReadinessReportService;
use App\Services\Governance\HybridAccessControlService;
use Illuminate\Http\Request;
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
        $hotelId = $request->user()->hotel_id;

        $decision = $this->accessControl->evaluate($request->user(), 'partner.test_execute', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $report = $this->readinessService->generateReport($partner);

        return redirect()->route('readiness.show', $report)->with('success', "Readiness evaluation generated successfully for {$partner->name}. Certification score: {$report->overall_score}% ({$report->certification_status})");
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

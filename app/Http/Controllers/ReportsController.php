<?php

namespace App\Http\Controllers;

use App\Models\AuthorizationDecision;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\RateParityObservation;
use App\Models\Reservation;
use App\Models\SyncAttemptLog;
use App\Services\Governance\HybridAccessControlService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(
        protected HybridAccessControlService $accessControl
    ) {}

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        $decision = $this->accessControl->evaluate($currentUser, 'reports.view', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return redirect()->route('dashboard')->with('error', 'Access denied: ' . $decision->getReason());
        }

        $totalReservations = Reservation::where('hotel_id', $hotelId)->count();
        $totalRevenue = Reservation::where('hotel_id', $hotelId)->sum('total_price') ?? 0;
        
        $syncSuccessCount = SyncAttemptLog::where('status', 'success')->count();
        $syncFailCount = SyncAttemptLog::where('status', 'failed')->count();
        $syncTotal = $syncSuccessCount + $syncFailCount;
        $syncUptime = $syncTotal > 0 ? round(($syncSuccessCount / $syncTotal) * 100, 1) : 100.0;

        $parityObservations = RateParityObservation::where('hotel_id', $hotelId)
            ->with(['roomType', 'channel'])
            ->latest('observed_at')
            ->take(10)
            ->get();

        $channels = Channel::withCount(['connections as active_connections' => function ($q) use ($hotelId) {
            $q->where('hotel_id', $hotelId)->where('status', 'active');
        }])->get();

        $recentDecisions = AuthorizationDecision::with('user')->latest()->take(10)->get();

        return view('reports.index', compact(
            'totalReservations',
            'totalRevenue',
            'syncUptime',
            'syncSuccessCount',
            'syncFailCount',
            'parityObservations',
            'channels',
            'recentDecisions'
        ));
    }

    public function exportSyncLogsCsv(Request $request): StreamedResponse
    {
        $fileName = 'sync_activity_report_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Channel', 'Event Type', 'Status', 'HTTP Status', 'Latency (ms)', 'Error Details', 'Timestamp']);

            SyncAttemptLog::with('channel')
                ->latest()
                ->take(500)
                ->chunk(100, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->channel?->name ?? 'N/A',
                            $log->event_type ?? 'inventory_sync',
                            $log->status,
                            $log->http_status ?? '',
                            $log->latency_ms ?? 0,
                            $log->error_message ?? '',
                            $log->created_at->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function exportAuditLogsCsv(Request $request): StreamedResponse
    {
        $fileName = 'security_authorization_decisions_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'User', 'Action', 'Decision', 'Reason', 'Risk Score', 'Timestamp']);

            AuthorizationDecision::with('user')
                ->latest()
                ->take(500)
                ->chunk(100, function ($decisions) use ($handle) {
                    foreach ($decisions as $d) {
                        fputcsv($handle, [
                            $d->id,
                            $d->user?->name ?? 'System',
                            $d->action,
                            strtoupper($d->decision),
                            $d->reason,
                            $d->risk_score_at_evaluation ?? 0,
                            $d->created_at->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function exportInventoryCsv(Request $request): StreamedResponse
    {
        $hotelId = $request->user()->hotel_id;
        $fileName = 'central_inventory_rates_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($hotelId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Room Type', 'Available', 'Booked', 'Blocked', 'Rate (JPY)', 'Min Stay', 'Stop Sell']);

            CentralInventory::where('hotel_id', $hotelId)
                ->with('roomType')
                ->orderBy('date')
                ->take(500)
                ->chunk(100, function ($rows) use ($handle) {
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->date->format('Y-m-d'),
                            $r->roomType?->name ?? 'Standard Room',
                            $r->available_rooms,
                            $r->booked_rooms,
                            $r->blocked_rooms,
                            $r->price,
                            $r->min_stay_nights,
                            $r->stop_sell ? 'YES' : 'NO',
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}

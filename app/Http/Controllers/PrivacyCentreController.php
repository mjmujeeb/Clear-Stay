<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\PrivacyAccessLog;
use App\Services\Privacy\PrivacyProtectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyCentreController extends Controller
{
    public function __construct(
        protected PrivacyProtectionService $privacyService
    ) {}

    /**
     * Privacy & Data Protection Centre Main Hub.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? $request->user()->hotel_id;
        $hotel = Hotel::find($hotelId) ?? Hotel::first();

        // Sample guest records with default masking applied
        $guests = Guest::with('reservations')->take(10)->get()->map(function ($g) {
            return [
                'id' => $g->id,
                'name' => $g->full_name,
                'masked_email' => $this->privacyService->maskEmail($g->email),
                'masked_phone' => $this->privacyService->maskPhone($g->phone),
                'reservations_count' => $g->reservations->count(),
            ];
        });

        // Recent privacy unmasking access logs
        $accessLogs = PrivacyAccessLog::with(['user', 'hotel'])
            ->latest()
            ->paginate(15);

        // Compliance mapping to India DPDP Act 2023 & DPDP Rules 2025
        $dpdpMatrix = $this->privacyService->getDpdpComplianceMatrix();

        // Automated Tenant Isolation Verification
        $tenantIsolation = $hotel ? $this->privacyService->verifyTenantIsolation($hotel) : null;

        return view('privacy_centre.index', compact(
            'guests',
            'accessLogs',
            'dpdpMatrix',
            'tenantIsolation'
        ));
    }

    /**
     * Unmask guest phone and email with mandatory justification audit.
     */
    public function unmask(Request $request, $guestId): JsonResponse
    {
        $request->validate([
            'justification' => 'required|string|min:5|max:500',
        ]);

        $guest = Guest::findOrFail($guestId);
        $actor = $request->user();
        $justification = $request->input('justification');

        $result = $this->privacyService->unmaskGuestPii($guest, $actor, $justification);

        return response()->json($result);
    }

    /**
     * Export Privacy Access Log as CSV evidence.
     */
    public function exportAuditReport(Request $request): StreamedResponse
    {
        $filename = 'clearstay_privacy_audit_log_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Log ID',
                'Correlation ID',
                'Timestamp (UTC)',
                'Hotel ID',
                'User Name',
                'User Email',
                'Guest ID',
                'Action',
                'Field Accessed',
                'Justification',
                'IP Address',
                'User Agent',
            ]);

            PrivacyAccessLog::with(['user', 'hotel'])
                ->chunk(100, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->correlation_id,
                            $log->created_at->toIso8601String(),
                            $log->hotel_id,
                            $log->user?->name ?? 'System',
                            $log->user?->email ?? 'N/A',
                            $log->guest_id,
                            $log->action,
                            $log->field_accessed,
                            $log->justification,
                            $log->ip_address,
                            $log->user_agent,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

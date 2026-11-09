<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Hotel;
use App\Models\PaymentReconciliation;
use App\Services\Payment\PaymentReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentReconciliationController extends Controller
{
    protected PaymentReconciliationService $reconService;

    public function __construct(PaymentReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    /**
     * Reservation-to-Payment Reconciliation Hub (Agoda & NTT DATA Style).
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId) ?? Hotel::first();

        // If no reconciliations exist, auto-run the initial batch
        if (PaymentReconciliation::where('hotel_id', $hotelId)->count() === 0) {
            $this->reconService->runReconciliationBatch($hotelId);
        }

        $status = $request->input('status');
        $channelId = $request->input('channel_id');
        $search = $request->input('search');

        $query = PaymentReconciliation::where('hotel_id', $hotelId)
            ->with(['reservation.guest', 'channel']);

        if ($status) {
            $query->where('reconciliation_status', $status);
        }
        if ($channelId) {
            $query->where('channel_id', $channelId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reservation_reference', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%");
            });
        }

        $reconciliations = $query->latest('transaction_date')->paginate(15);

        // Summary Statistics
        $totalRecords = PaymentReconciliation::where('hotel_id', $hotelId)->count();
        $matchedCount = PaymentReconciliation::where('hotel_id', $hotelId)->where('reconciliation_status', 'matched')->count();
        $partialCount = PaymentReconciliation::where('hotel_id', $hotelId)->where('reconciliation_status', 'partial')->count();
        $pendingCount = PaymentReconciliation::where('hotel_id', $hotelId)->where('reconciliation_status', 'pending')->count();
        $totalDiscrepancyAmount = (float) PaymentReconciliation::where('hotel_id', $hotelId)->sum('discrepancy_amount');

        $channels = Channel::where('is_active', true)->get();

        return view('payment_reconciliation.index', compact(
            'hotel',
            'reconciliations',
            'status',
            'channelId',
            'search',
            'totalRecords',
            'matchedCount',
            'partialCount',
            'pendingCount',
            'totalDiscrepancyAmount',
            'channels'
        ));
    }

    /**
     * Run on-demand reconciliation batch.
     */
    public function reconcile(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $result = $this->reconService->runReconciliationBatch($hotelId);

        return back()->with('success', "Payment reconciliation completed: {$result['matched']} matched, {$result['discrepancies']} discrepancies identified.");
    }

    /**
     * Resolve / Override a payment discrepancy.
     */
    public function resolve(Request $request, PaymentReconciliation $reconciliation)
    {
        $request->validate([
            'action' => 'required|in:accept_adjustment,force_match,write_off',
            'notes' => 'required|string|min:4|max:500',
        ]);

        $action = $request->input('action');
        $notes = $request->input('notes');

        $reconciliation->reconciliation_status = 'matched';
        $reconciliation->settlement_status = 'settled';
        $reconciliation->exception_reason = "Resolved by manager ({$action}): {$notes}";
        $history = $reconciliation->audit_history ?? [];
        $history[] = [
            'resolved_by' => Auth::id(),
            'resolved_at' => now()->toIso8601String(),
            'action' => $action,
            'notes' => $notes,
        ];
        $reconciliation->audit_history = $history;
        $reconciliation->save();

        return back()->with('success', "Discrepancy for {$reconciliation->reservation_reference} successfully marked as resolved.");
    }

    /**
     * CSV Export of settlement reconciliation audit.
     */
    public function exportCsv(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::findOrFail($hotelId);

        $reconciliations = PaymentReconciliation::where('hotel_id', $hotelId)
            ->with(['reservation', 'channel'])
            ->latest()
            ->get();

        $filename = "payment_reconciliation_{$hotel->code}_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reconciliations) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Reservation Ref',
                'Payment Ref',
                'Channel',
                'Gateway / Method',
                'Expected Amount (JPY)',
                'Received Amount (JPY)',
                'Discrepancy (JPY)',
                'Reconciliation Status',
                'Settlement Status',
                'Transaction Date',
                'Notes / Exception Reason',
            ]);

            foreach ($reconciliations as $r) {
                fputcsv($handle, [
                    $r->reservation_reference,
                    $r->payment_reference,
                    $r->channel?->name ?? 'Direct Web',
                    $r->payment_gateway . ' (' . $r->payment_method . ')',
                    $r->expected_amount,
                    $r->received_amount,
                    $r->discrepancy_amount,
                    $r->reconciliation_status,
                    $r->settlement_status,
                    $r->transaction_date?->toDateString(),
                    $r->exception_reason,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

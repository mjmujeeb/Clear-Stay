<?php

namespace App\Services\Payment;

use App\Models\Hotel;
use App\Models\PaymentReconciliation;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentReconciliationService
{
    /**
     * Run automated reservation-to-payment matching routine (Agoda & NTT DATA Virtual Card Style).
     */
    public function runReconciliationBatch(int $hotelId): array
    {
        $hotel = Hotel::findOrFail($hotelId);
        $reservations = Reservation::where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->get();

        $matchedCount = 0;
        $unmatchedCount = 0;
        $discrepancyCount = 0;

        foreach ($reservations as $res) {
            $existing = PaymentReconciliation::where('reservation_id', $res->id)->first();

            $expectedAmount = (float) $res->total_price;
            $gateway = $res->channel ? "{$res->channel->name} Pay / NTT DATA Virtual Card" : 'Direct Web Payment';
            $method = $res->channel ? 'virtual_credit_card' : 'credit_card';

            // Simulate realistic settlement behaviors:
            // 90% full match, 5% partial payment (commission deducted), 5% pending
            $seedMod = $res->id % 20;

            if ($seedMod === 0) {
                // Partial payment discrepancy (e.g. unexpected foreign exchange or fee discrepancy)
                $receivedAmount = round($expectedAmount * 0.90, 2);
                $discrepancy = $expectedAmount - $receivedAmount;
                $reconStatus = 'partial';
                $settleStatus = 'disputed';
                $reason = "Received amount differs by ¥{$discrepancy} (Net OTA deduction under review)";
                $discrepancyCount++;
            } elseif ($seedMod === 1) {
                // Pending settlement
                $receivedAmount = 0.00;
                $discrepancy = $expectedAmount;
                $reconStatus = 'pending';
                $settleStatus = 'pending_settlement';
                $reason = 'Awaiting virtual card activation on guest check-in date';
                $unmatchedCount++;
            } else {
                // Full Match
                $receivedAmount = $expectedAmount;
                $discrepancy = 0.00;
                $reconStatus = 'matched';
                $settleStatus = 'settled';
                $reason = 'Complete match: Virtual card charged and settled successfully';
                $matchedCount++;
            }

            PaymentReconciliation::updateOrCreate(
                [
                    'hotel_id' => $hotelId,
                    'reservation_id' => $res->id,
                ],
                [
                    'reservation_reference' => $res->confirmation_code ?? "RES-{$res->id}",
                    'payment_reference' => "VCC-NTT-" . strtoupper(substr(md5($res->id . 'salt'), 0, 10)),
                    'channel_id' => $res->channel_id,
                    'payment_gateway' => $gateway,
                    'payment_method' => $method,
                    'expected_amount' => $expectedAmount,
                    'received_amount' => $receivedAmount,
                    'discrepancy_amount' => $discrepancy,
                    'currency' => 'JPY',
                    'settlement_status' => $settleStatus,
                    'reconciliation_status' => $reconStatus,
                    'transaction_date' => $res->check_in_date ?? Carbon::today(),
                    'settled_at' => $reconStatus === 'matched' ? now() : null,
                    'exception_reason' => $reason,
                    'audit_history' => [
                        'last_run' => now()->toIso8601String(),
                        'rule' => 'automated_vcc_reconciliation',
                    ],
                ]
            );
        }

        return [
            'total_evaluated' => $reservations->count(),
            'matched' => $matchedCount,
            'unmatched' => $unmatchedCount,
            'discrepancies' => $discrepancyCount,
        ];
    }
}

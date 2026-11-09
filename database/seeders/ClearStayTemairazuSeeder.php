<?php

namespace Database\Seeders;

use App\Models\BookingSnapshot;
use App\Models\Channel;
use App\Models\Hotel;
use App\Models\InventoryReconciliation;
use App\Models\PaymentReconciliation;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Services\Analytics\BookingAnalyticsService;
use App\Services\Payment\PaymentReconciliationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClearStayTemairazuSeeder extends Seeder
{
    /**
     * Seed realistic data for TEMAIRAZU-oriented enterprise modules:
     * - Historical Booking Snapshots (BA+ direction)
     * - Payment Reconciliations (Agoda / NTT DATA VCC workflow)
     * - Inventory Reconciliations (PMS vs OTAs drift auditing)
     */
    public function run(): void
    {
        $hotel = Hotel::where('slug', 'tokyo-central')->first() ?? Hotel::first();
        if (!$hotel) {
            return;
        }

        $channels = Channel::where('is_active', true)->get();
        $roomTypes = RoomType::where('hotel_id', $hotel->id)->get();

        // 1. Seed Historical Booking Snapshots for upcoming stay dates
        $analyticsService = app(BookingAnalyticsService::class);
        $today = Carbon::today();

        for ($d = 1; $d <= 30; $d++) {
            $stayDate = Carbon::today()->addDays($d);

            // Capture current snapshot
            $current = $analyticsService->captureSnapshot($hotel, $stayDate, $today);

            // 7 Days ago snapshot (simulating fewer bookings back then)
            BookingSnapshot::updateOrCreate(
                [
                    'hotel_id' => $hotel->id,
                    'snapshot_date' => $today->copy()->subDays(7)->toDateString(),
                    'stay_date' => $stayDate->toDateString(),
                ],
                [
                    'booking_count' => max(0, $current->booking_count - rand(1, 3)),
                    'occupied_rooms' => max(0, $current->occupied_rooms - rand(1, 3)),
                    'available_rooms' => min($current->available_rooms + 2, 20),
                    'revenue' => max(0, $current->revenue - 45000),
                    'cancellation_count' => rand(0, 1),
                    'adr' => 21000.00,
                    'revpar' => 14000.00,
                    'occupancy_rate' => max(0, $current->occupancy_rate - 15.0),
                    'metadata' => ['historical_source' => 'temairazu_7d_snapshot'],
                ]
            );

            // 30 Days ago snapshot
            BookingSnapshot::updateOrCreate(
                [
                    'hotel_id' => $hotel->id,
                    'snapshot_date' => $today->copy()->subDays(30)->toDateString(),
                    'stay_date' => $stayDate->toDateString(),
                ],
                [
                    'booking_count' => max(0, $current->booking_count - rand(3, 5)),
                    'occupied_rooms' => max(0, $current->occupied_rooms - rand(3, 5)),
                    'available_rooms' => min($current->available_rooms + 4, 20),
                    'revenue' => max(0, $current->revenue - 90000),
                    'cancellation_count' => 0,
                    'adr' => 19500.00,
                    'revpar' => 11000.00,
                    'occupancy_rate' => max(0, $current->occupancy_rate - 30.0),
                    'metadata' => ['historical_source' => 'temairazu_30d_snapshot'],
                ]
            );

            // Previous Year YoY Snapshot
            BookingSnapshot::updateOrCreate(
                [
                    'hotel_id' => $hotel->id,
                    'snapshot_date' => $today->copy()->subYear()->toDateString(),
                    'stay_date' => $stayDate->copy()->subYear()->toDateString(),
                ],
                [
                    'booking_count' => max(1, $current->booking_count + rand(-2, 2)),
                    'occupied_rooms' => max(1, $current->occupied_rooms + rand(-2, 2)),
                    'available_rooms' => 10,
                    'revenue' => 165000.00,
                    'cancellation_count' => 1,
                    'adr' => 20000.00,
                    'revpar' => 13500.00,
                    'occupancy_rate' => 68.0,
                    'metadata' => ['historical_source' => 'temairazu_yoy_snapshot'],
                ]
            );
        }

        // 2. Seed Payment Reconciliations (Agoda Pay / NTT DATA Virtual Card)
        $paymentService = app(PaymentReconciliationService::class);
        $paymentService->runReconciliationBatch($hotel->id);

        // 3. Seed Inventory Reconciliations
        foreach ($roomTypes as $rt) {
            for ($i = 0; $i < 14; $i++) {
                $stayDate = Carbon::today()->addDays($i);

                foreach ($channels as $ch) {
                    $hasDrift = ($i === 3 || $i === 8) && $ch->name === 'Agoda';
                    $central = 5;
                    $remote = $hasDrift ? 4 : 5;
                    $diff = $central - $remote;

                    InventoryReconciliation::updateOrCreate(
                        [
                            'hotel_id' => $hotel->id,
                            'room_type_id' => $rt->id,
                            'channel_id' => $ch->id,
                            'stay_date' => $stayDate->toDateString(),
                        ],
                        [
                            'central_units' => $central,
                            'channel_units' => $remote,
                            'difference' => $diff,
                            'status' => $diff === 0 ? 'in_sync' : 'discrepancy',
                            'last_synced_at' => now()->subMinutes(15),
                            'suggested_action' => $diff === 0
                                ? 'No action needed. In perfect sync.'
                                : "Discrepancy: Central has {$central} units, {$ch->name} reports {$remote}. Recommended: Re-push central inventory.",
                        ]
                    );
                }
            }
        }
    }
}

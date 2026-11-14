<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\IntegrationMode;
use App\Models\IntegrationPartner;
use App\Models\SimulatedPartnerInventory;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerReservation;
use App\Models\SimulatedPartnerRoomMapping;
use App\Models\SyncAttemptLog;
use App\Models\SyncOutboxEvent;
use App\Services\Channel\SimulatedRemoteStateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClearStay42SimulatedPartnerNetworkSeeder extends Seeder
{
    /**
     * Seed stateful demonstration data for ClearStay 4.2 Simulated Partner Network.
     */
    public function run(): void
    {
        $hotels = Hotel::where('is_active', true)->get();
        if ($hotels->isEmpty()) {
            return;
        }

        $remoteService = app(SimulatedRemoteStateService::class);

        // 1. Ensure Channels exist in database for all 7 simulated partners
        $partnerDefinitions = [
            'booking_com' => ['name' => 'Booking.com — SIMULATED', 'type' => 'ota'],
            'agoda' => ['name' => 'Agoda — SIMULATED', 'type' => 'ota'],
            'expedia' => ['name' => 'Expedia — SIMULATED', 'type' => 'ota'],
            'rakuten' => ['name' => 'Rakuten Travel (楽天トラベル) — SIMULATED', 'type' => 'ota'],
            'jalan' => ['name' => 'Jalan.net (じゃらんnet) — SIMULATED', 'type' => 'ota'],
            'direct_web' => ['name' => 'Direct Booking Engine — SIMULATED', 'type' => 'direct'],
            'sakura_pms' => ['name' => 'Sakura PMS Connector — SIMULATED', 'type' => 'pms'],
        ];

        foreach ($partnerDefinitions as $code => $def) {
            Channel::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'is_active' => true,
                    'status' => 'active',
                    'api_endpoint' => "http://127.0.0.1:8000/api/simulators/{$code}",
                ]
            );

            // Seed default SIMULATOR mode
            IntegrationMode::updateOrCreate(
                ['partner_code' => $code],
                [
                    'mode' => 'simulator',
                    'notes' => 'Provisioned in local stateful simulation mode.',
                ]
            );
        }

        // Global mode record
        IntegrationMode::updateOrCreate(
            ['partner_code' => null],
            [
                'mode' => 'simulator',
                'notes' => 'Default system-wide integration mode is SIMULATOR.',
            ]
        );

        // 2. Provision properties, room mappings, and 14 days of remote inventory for each hotel
        foreach ($hotels as $hotel) {
            foreach ($partnerDefinitions as $code => $def) {
                $channel = Channel::where('code', $code)->first();
                if ($channel) {
                    ChannelConnection::firstOrCreate(
                        [
                            'hotel_id' => $hotel->id,
                            'channel_id' => $channel->id,
                        ],
                        [
                            'is_enabled' => true,
                            'sync_status' => 'ok',
                            'sync_interval_mins' => 15,
                        ]
                    );
                }

                // Provision stateful remote property & mappings
                $remoteService->ensureProvisioned($hotel->id, $code);

                // Initialize 14 days of remote inventory
                $remoteService->initializeRemoteInventory($hotel->id, $code, 14);
            }
        }

        // 3. Seed sample inbound OTA reservations
        $primaryHotel = $hotels->first();
        if ($primaryHotel) {
            $sampleInbound = [
                [
                    'partner_code' => 'agoda',
                    'external_reservation_id' => 'AGD-RES-2026-901',
                    'property_code' => "AGD-{$primaryHotel->code}",
                    'remote_room_code' => 'AGD_RM_DLX_KNG',
                    'guest_name' => 'Kenji Tanaka',
                    'guest_email' => 'kenji.tanaka@example.jp',
                    'guest_phone' => '+81-90-1111-2222',
                    'check_in_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
                    'check_out_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
                    'guest_count' => 2,
                    'total_amount' => 52000,
                    'currency' => 'JPY',
                    'status' => 'confirmed',
                    'idempotency_key' => 'IDEM-AGD-INIT-001',
                ],
                [
                    'partner_code' => 'booking_com',
                    'external_reservation_id' => 'BKG-RES-2026-442',
                    'property_code' => "BKG-{$primaryHotel->code}",
                    'remote_room_code' => 'BKG_RM_DLX_KNG',
                    'guest_name' => 'Marcus Vance',
                    'guest_email' => 'marcus.vance@example.com',
                    'guest_phone' => '+1-415-555-0199',
                    'check_in_date' => Carbon::today()->addDays(7)->format('Y-m-d'),
                    'check_out_date' => Carbon::today()->addDays(9)->format('Y-m-d'),
                    'guest_count' => 2,
                    'total_amount' => 56000,
                    'currency' => 'JPY',
                    'status' => 'confirmed',
                    'idempotency_key' => 'IDEM-BKG-INIT-002',
                ],
            ];

            foreach ($sampleInbound as $resData) {
                SimulatedPartnerReservation::updateOrCreate(
                    [
                        'partner_code' => $resData['partner_code'],
                        'external_reservation_id' => $resData['external_reservation_id'],
                    ],
                    $resData
                );
            }
        }
    }
}

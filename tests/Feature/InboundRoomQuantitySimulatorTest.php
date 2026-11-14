<?php

namespace Tests\Feature;

use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SimulatedFailureConfig;
use App\Models\SimulatedPartnerInventory;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerReservation;
use App\Models\SimulatedPartnerRoomMapping;
use App\Models\SyncOutboxEvent;
use App\Models\User;
use App\Services\Channel\ChannelSynchronizationService;
use App\Services\Channel\InboundBookingWebhookService;
use App\Services\Channel\SimulatedRemoteStateService;
use App\Services\Integration\IntegrationModeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InboundRoomQuantitySimulatorTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected RoomType $queenType;
    protected Room $room201;
    protected Room $room202;
    protected Room $room203;
    protected Room $room204;
    protected Room $room205;
    protected User $manager;

    protected SimulatedRemoteStateService $remoteStateService;
    protected ChannelSynchronizationService $syncService;
    protected InboundBookingWebhookService $inboundService;

    protected string $checkIn;
    protected string $checkOut;
    protected string $night1;
    protected string $night2;
    protected string $checkoutDay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Ginza Premier',
            'slug' => 'clearstay-ginza-premier',
            'code' => 'GNZ-01',
            'address' => '5-7-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'currency' => 'JPY',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Hotel Operations Manager',
            'email' => 'ops.manager@clearstay.test',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->queenType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Superior Queen Double',
            'code' => 'SUP-QNN',
            'base_price' => 24000,
            'max_occupancy' => 2,
            'total_rooms' => 5,
        ]);

        // Create 5 physical rooms
        $this->room201 = Room::create(['hotel_id' => $this->hotel->id, 'room_type_id' => $this->queenType->id, 'room_number' => '201', 'status' => 'clean', 'is_active' => true]);
        $this->room202 = Room::create(['hotel_id' => $this->hotel->id, 'room_type_id' => $this->queenType->id, 'room_number' => '202', 'status' => 'clean', 'is_active' => true]);
        $this->room203 = Room::create(['hotel_id' => $this->hotel->id, 'room_type_id' => $this->queenType->id, 'room_number' => '203', 'status' => 'clean', 'is_active' => true]);
        $this->room204 = Room::create(['hotel_id' => $this->hotel->id, 'room_type_id' => $this->queenType->id, 'room_number' => '204', 'status' => 'clean', 'is_active' => true]);
        $this->room205 = Room::create(['hotel_id' => $this->hotel->id, 'room_type_id' => $this->queenType->id, 'room_number' => '205', 'status' => 'clean', 'is_active' => true]);

        // Create channels & connections for all 5 partner OTAs
        $channels = [
            'agoda' => 'Agoda',
            'booking_com' => 'Booking.com',
            'expedia' => 'Expedia',
            'rakuten' => 'Rakuten Travel',
            'jalan' => 'Jalan.net',
        ];

        foreach ($channels as $code => $name) {
            $ch = Channel::create([
                'name' => $name,
                'code' => $code,
                'channel_type' => 'ota',
                'is_active' => true,
            ]);

            ChannelConnection::create([
                'hotel_id' => $this->hotel->id,
                'channel_id' => $ch->id,
                'is_enabled' => true,
                'sync_status' => 'ok',
                'credentials' => ['api_key' => 'sim_test_key', 'mode' => 'SIMULATOR'],
            ]);
        }

        $this->remoteStateService = app(SimulatedRemoteStateService::class);
        $this->syncService = app(ChannelSynchronizationService::class);
        $this->inboundService = app(InboundBookingWebhookService::class);

        // Provision remote partner properties & room mappings
        foreach ($channels as $code => $name) {
            $this->remoteStateService->ensureProvisioned($this->hotel->id, $code);
        }

        // Future stay dates: 2 occupied nights (Dec 20 & Dec 21), checkout on Dec 22
        $this->checkIn = Carbon::now()->addDays(10)->format('Y-m-d');
        $this->night1 = $this->checkIn;
        $this->night2 = Carbon::parse($this->checkIn)->addDay()->format('Y-m-d');
        $this->checkoutDay = Carbon::parse($this->checkIn)->addDays(2)->format('Y-m-d');
        $this->checkOut = $this->checkoutDay;

        // Seed central inventories: 5 total, 0 reserved, 5 available
        foreach ([$this->night1, $this->night2, $this->checkoutDay] as $d) {
            CentralInventory::create([
                'hotel_id' => $this->hotel->id,
                'room_type_id' => $this->queenType->id,
                'date' => $d,
                'total_units' => 5,
                'reserved_units' => 0,
                'available_units' => 5,
                'maintenance_units' => 0,
                'base_rate' => 24000,
                'stop_sell' => false,
            ]);
        }

        // Initialize 14 days remote inventory matching central PMS
        foreach ($channels as $code => $name) {
            $this->remoteStateService->initializeRemoteInventory($this->hotel->id, $code, 14);
        }
    }

    /**
     * Scenario A: Normal multi-room booking within capacity (5 total, 0 reserved, requested 2 -> 3 remaining).
     */
    public function test_scenario_a_normal_multi_room_booking_within_capacity(): void
    {
        $payload = [
            'external_reservation_id' => 'AGD-RES-2001',
            'property_code' => 'AGD-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 2,
            'guest_name' => 'Kenji Tanaka',
            'guest_email' => 'kenji.tanaka@example.test',
            'price' => 96000,
            'idempotency_key' => 'IDEM-SCENARIO-A',
        ];

        $response = $this->inboundService->processInboundBooking('agoda', $payload);

        $this->assertTrue($response['success']);
        $this->assertEquals(201, $response['status_code']);
        $this->assertEquals('accepted', $response['status']);
        $this->assertEquals(2, $response['rooms_count']);

        // Check Central Inventory on Night 1 and Night 2: reserved = 2, available = 3
        $invNight1 = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(2, $invNight1->reserved_units);
        $this->assertEquals(3, $invNight1->available_units);

        $invNight2 = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night2)
            ->first();
        $this->assertEquals(2, $invNight2->reserved_units);
        $this->assertEquals(3, $invNight2->available_units);

        // Checkout day must NOT be modified (available remains 5)
        $invCheckout = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->checkoutDay)
            ->first();
        $this->assertEquals(0, $invCheckout->reserved_units);
        $this->assertEquals(5, $invCheckout->available_units);

        // Agoda remote inventory was decremented to 3
        $agodaProp = SimulatedPartnerProperty::where('hotel_id', $this->hotel->id)->where('partner_code', 'agoda')->first();
        $agodaMapping = $agodaProp->roomMappings()->where('room_type_id', $this->queenType->id)->first();
        $agodaInv1 = SimulatedPartnerInventory::where('property_id', $agodaProp->id)
            ->where('remote_room_code', $agodaMapping->remote_room_code)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(3, $agodaInv1->available_units);

        // Booking.com and Expedia remote inventory synchronized to 3 via outbound outbox
        $bkgProp = SimulatedPartnerProperty::where('hotel_id', $this->hotel->id)->where('partner_code', 'booking_com')->first();
        $bkgMapping = $bkgProp->roomMappings()->where('room_type_id', $this->queenType->id)->first();
        $bkgInv1 = SimulatedPartnerInventory::where('property_id', $bkgProp->id)
            ->where('remote_room_code', $bkgMapping->remote_room_code)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(3, $bkgInv1->available_units);
    }

    /**
     * Scenario B: Booking request exceeding capacity is rejected with 409 and does not deduct inventory.
     */
    public function test_scenario_b_booking_request_exceeding_capacity_rejected_409(): void
    {
        // Reduce available capacity to 2 rooms
        CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->whereIn('date', [$this->night1, $this->night2])
            ->update(['reserved_units' => 3, 'available_units' => 2]);

        $payload = [
            'external_reservation_id' => 'EXP-RES-OVERFLOW',
            'property_code' => 'EXP-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 3, // Request 3 when only 2 available
            'guest_name' => 'Yuki Sato',
        ];

        $response = $this->inboundService->processInboundBooking('expedia', $payload);

        $this->assertFalse($response['success']);
        $this->assertEquals(409, $response['status_code']);
        $this->assertEquals('CAPACITY_EXHAUSTED', $response['error_code']);
        $this->assertEquals(2, $response['available_units']);
        $this->assertEquals(3, $response['requested_rooms']);

        // Assert inventory is completely unchanged
        $inv = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(2, $inv->available_units);
        $this->assertEquals(3, $inv->reserved_units);
    }

    /**
     * Scenario C: Booking consuming all remaining inventory (full sell-out -> 0 remaining).
     */
    public function test_scenario_c_full_sell_out_booking_to_zero_remaining(): void
    {
        // Set available capacity to 2
        CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->whereIn('date', [$this->night1, $this->night2])
            ->update(['reserved_units' => 3, 'available_units' => 2]);

        $payload = [
            'external_reservation_id' => 'BKG-RES-SELLOUT',
            'property_code' => 'BKG-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 2, // Exactly consumes remaining 2 rooms
            'guest_name' => 'Akira Kurosawa',
        ];

        $response = $this->inboundService->processInboundBooking('booking_com', $payload);

        $this->assertTrue($response['success']);
        $this->assertEquals(201, $response['status_code']);
        $this->assertEquals(0, $response['inventory_summary']['remaining']);

        // Verify central inventory is now 0
        $inv = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(0, $inv->available_units);
        $this->assertEquals(5, $inv->reserved_units);
    }

    /**
     * Scenario D: Multi-night bottleneck test (Night 1 has 5, Night 2 has 1, requested 2 -> rejected).
     */
    public function test_scenario_d_multi_night_bottleneck_prevents_partial_booking(): void
    {
        // Night 1 has 5 rooms available; Night 2 has only 1 available
        CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->update(['reserved_units' => 0, 'available_units' => 5]);

        CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night2)
            ->update(['reserved_units' => 4, 'available_units' => 1]);

        $payload = [
            'external_reservation_id' => 'JAL-RES-BOTTLENECK',
            'property_code' => 'JAL-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 2, // Request 2 rooms across both nights
            'guest_name' => 'Haruki Murakami',
        ];

        $response = $this->inboundService->processInboundBooking('jalan', $payload);

        $this->assertFalse($response['success']);
        $this->assertEquals(409, $response['status_code']);
        $this->assertEquals($this->night2, $response['failed_stay_date']);

        // Crucial: Night 1 must NOT have deducted units! Both nights remain intact
        $inv1 = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(5, $inv1->available_units);

        $inv2 = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night2)
            ->first();
        $this->assertEquals(1, $inv2->available_units);
    }

    /**
     * Scenario E: Idempotency & duplicate webhook delivery does not double-decrement.
     */
    public function test_scenario_e_idempotency_duplicate_webhook_does_not_double_deduct(): void
    {
        $payload = [
            'external_reservation_id' => 'RAK-RES-IDEM-001',
            'property_code' => 'RAK-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 2,
            'guest_name' => 'Hideo Kojima',
            'idempotency_key' => 'IDEM-KEY-UNIQUE-777',
        ];

        // 1. First delivery
        $first = $this->inboundService->processInboundBooking('rakuten', $payload);
        $this->assertTrue($first['success']);
        $this->assertEquals(201, $first['status_code']);

        $invAfterFirst = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(3, $invAfterFirst->available_units);

        // 2. Duplicate delivery with same idempotency key
        $second = $this->inboundService->processInboundBooking('rakuten', $payload);
        $this->assertTrue($second['success']);
        $this->assertEquals(200, $second['status_code']);
        $this->assertEquals('duplicate_acknowledged', $second['status']);

        // Assert available inventory remains 3 and was NOT decremented to 1
        $invAfterSecond = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->queenType->id)
            ->where('date', $this->night1)
            ->first();
        $this->assertEquals(3, $invAfterSecond->available_units);
        $this->assertEquals(2, $invAfterSecond->reserved_units);
    }

    /**
     * Scenario F: Live Availability Preview endpoint calculations.
     */
    public function test_scenario_f_live_availability_preview_endpoint(): void
    {
        $this->actingAs($this->manager);

        $res = $this->postJson('/integrations/simulators/availability-preview', [
            'partner_code' => 'agoda',
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'requested_rooms' => 2,
        ]);

        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
            'nights' => 2,
            'requested_rooms' => 2,
            'available_before' => 5,
            'estimated_remaining' => 3,
            'can_accept' => true,
            'capacity_status' => 'available',
        ]);

        $json = $res->json();
        $this->assertCount(2, $json['date_breakdown']);
        $this->assertEquals($this->night1, $json['date_breakdown'][0]['date']);
        $this->assertEquals($this->night2, $json['date_breakdown'][1]['date']);
        $this->assertGreaterThanOrEqual(5, count($json['partner_network']));
    }

    /**
     * Scenario G: Originating partner anti-double decrement guarantee.
     */
    public function test_scenario_g_originating_partner_anti_double_decrement_guarantee(): void
    {
        $payload = [
            'external_reservation_id' => 'AGD-ANTI-DOUBLE',
            'property_code' => 'AGD-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 2,
            'guest_name' => 'Daigo Umehara',
        ];

        $res = $this->inboundService->processInboundBooking('agoda', $payload);
        $this->assertTrue($res['success']);

        // Check that Agoda's remote inventory is exactly 3 (5 - 2), NEVER decremented twice to 1
        $agodaProp = SimulatedPartnerProperty::where('hotel_id', $this->hotel->id)->where('partner_code', 'agoda')->first();
        $agodaMapping = $agodaProp->roomMappings()->where('room_type_id', $this->queenType->id)->first();
        $agodaInv = SimulatedPartnerInventory::where('property_id', $agodaProp->id)
            ->where('remote_room_code', $agodaMapping->remote_room_code)
            ->where('date', $this->night1)
            ->first();

        $this->assertEquals(3, $agodaInv->available_units);
    }

    /**
     * Scenario H: Partial channel failure, discrepancy detection and one-click retry.
     */
    public function test_scenario_h_partial_channel_failure_and_retry_reconciliation(): void
    {
        // 1. Inject failure into Expedia outbound inventory push
        SimulatedFailureConfig::updateOrCreate(
            ['partner_code' => 'expedia', 'operation' => 'inventory_push'],
            [
                'failure_type' => '500_server_error',
                'http_status' => 500,
                'latency_ms' => 10,
                'error_code' => 'EXPEDIA_SERVER_ERROR',
                'error_message' => 'Simulated Expedia downstream gateway timeout',
                'is_active' => true,
            ]
        );

        // 2. Booking arrives from Agoda for 1 room
        $payload = [
            'external_reservation_id' => 'AGD-FAIL-TEST',
            'property_code' => 'AGD-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 1,
            'guest_name' => 'Shigeru Miyamoto',
        ];

        $res = $this->inboundService->processInboundBooking('agoda', $payload);
        $this->assertTrue($res['success']);

        // Central inventory dropped from 5 to 4
        $inv = CentralInventory::where('hotel_id', $this->hotel->id)->where('date', $this->night1)->first();
        $this->assertEquals(4, $inv->available_units);

        // Agoda dropped to 4
        $agodaProp = SimulatedPartnerProperty::where('hotel_id', $this->hotel->id)->where('partner_code', 'agoda')->first();
        $agodaMap = $agodaProp->roomMappings()->where('room_type_id', $this->queenType->id)->first();
        $agodaInv = SimulatedPartnerInventory::where('property_id', $agodaProp->id)->where('remote_room_code', $agodaMap->remote_room_code)->where('date', $this->night1)->first();
        $this->assertEquals(4, $agodaInv->available_units);

        // Expedia push failed, so Expedia remote inventory remains at 5
        $expProp = SimulatedPartnerProperty::where('hotel_id', $this->hotel->id)->where('partner_code', 'expedia')->first();
        $expMap = $expProp->roomMappings()->where('room_type_id', $this->queenType->id)->first();
        $expInv = SimulatedPartnerInventory::where('property_id', $expProp->id)->where('remote_room_code', $expMap->remote_room_code)->where('date', $this->night1)->first();
        $this->assertEquals(5, $expInv->available_units);

        // Find failed outbox event
        $failedOutbox = SyncOutboxEvent::where('hotel_id', $this->hotel->id)
            ->where('status', 'failed')
            ->first();
        $this->assertNotNull($failedOutbox);

        // 3. Deactivate failure injection and retry outbox event
        SimulatedFailureConfig::where('partner_code', 'expedia')->update(['is_active' => false]);

        $this->actingAs($this->manager);
        $retryRes = $this->postJson("/integrations/simulators/retry-sync/{$failedOutbox->id}");
        $retryRes->assertStatus(200);
        $retryRes->assertJson(['success' => true]);

        // 4. Assert Expedia is now synchronized to 4
        $expInvAfterRetry = SimulatedPartnerInventory::where('property_id', $expProp->id)->where('remote_room_code', $expMap->remote_room_code)->where('date', $this->night1)->first();
        $this->assertEquals(4, $expInvAfterRetry->available_units);
    }

    /**
     * Scenario I: Multi-room reservation records rooms_count and physical room assignments.
     */
    public function test_scenario_i_multi_room_reservation_records_rooms_count_and_allocates_physical_rooms(): void
    {
        $payload = [
            'external_reservation_id' => 'BKG-MULTI-ROOM-001',
            'property_code' => 'BKG-' . $this->hotel->code,
            'remote_room_code' => $this->queenType->code,
            'check_in_date' => $this->checkIn,
            'check_out_date' => $this->checkOut,
            'rooms_count' => 3,
            'guest_name' => 'Satoshi Nakamoto',
            'price' => 144000,
        ];

        $res = $this->inboundService->processInboundBooking('booking_com', $payload);
        $this->assertTrue($res['success']);

        $reservation = Reservation::find($res['clearstay_reservation_id']);
        $this->assertNotNull($reservation);
        $this->assertEquals(3, $reservation->rooms_count);
        $this->assertNotNull($reservation->group_booking_reference);
        $this->assertStringStartsWith('GRP-', $reservation->group_booking_reference);

        $simRes = SimulatedPartnerReservation::where('external_reservation_id', 'BKG-MULTI-ROOM-001')->first();
        $this->assertNotNull($simRes);
        $this->assertEquals(3, $simRes->rooms_count);
    }
}

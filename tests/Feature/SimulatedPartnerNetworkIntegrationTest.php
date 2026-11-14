<?php

namespace Tests\Feature;

use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\IntegrationMode;
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
use App\Services\Integration\InterviewerSimulationScenarioService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SimulatedPartnerNetworkIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected RoomType $deluxeType;
    protected Room $room101;
    protected User $manager;

    protected SimulatedRemoteStateService $remoteStateService;
    protected ChannelSynchronizationService $syncService;
    protected InboundBookingWebhookService $inboundService;
    protected IntegrationModeService $modeService;
    protected InterviewerSimulationScenarioService $scenarioService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'code' => 'TYO-GNZ',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'currency' => 'JPY',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'General Manager',
            'email' => 'gm.tokyo@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->deluxeType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe King',
            'code' => 'DLX-KNG',
            'base_price' => 25000,
            'max_occupancy' => 2,
        ]);

        $this->room101 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->deluxeType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'clean',
            'is_active' => true,
        ]);

        $this->remoteStateService = app(SimulatedRemoteStateService::class);
        $this->syncService = app(ChannelSynchronizationService::class);
        $this->inboundService = app(InboundBookingWebhookService::class);
        $this->modeService = app(IntegrationModeService::class);
        $this->scenarioService = app(InterviewerSimulationScenarioService::class);
    }

    /**
     * Test 1: All 7 simulated partners support the adapter contract and report distinct branding.
     */
    public function test_all_7_simulators_support_contract_and_report_metadata(): void
    {
        $expectedPartners = [
            'booking_com',
            'agoda',
            'expedia',
            'rakuten',
            'jalan',
            'direct_web',
            'sakura_pms',
        ];

        foreach ($expectedPartners as $code) {
            $adapter = $this->syncService->getAdapter($code);
            $this->assertNotNull($adapter);
            $this->assertEquals($code, $adapter->getChannelCode());
            $this->assertStringContainsString('SIMULATED', $adapter->getPartnerName());

            $caps = $adapter->getCapabilities();
            $this->assertTrue($caps['inventory_push']);
            $this->assertTrue($caps['rate_push']);
            $this->assertTrue($caps['reconciliation']);
        }
    }

    /**
     * Test 2: Integration Mode System prevents activation of Live Production without credentials.
     */
    public function test_integration_mode_system_prevents_unauthorized_live_mode(): void
    {
        // 1. Initial state must be SIMULATOR
        $this->assertEquals('simulator', $this->modeService->getMode('agoda'));
        $this->assertTrue($this->modeService->isSimulator('agoda'));

        // 2. Can switch safely to SANDBOX
        $this->modeService->setMode('sandbox', 'agoda', $this->manager->id);
        $this->assertEquals('sandbox', $this->modeService->getMode('agoda'));
        $this->assertTrue($this->modeService->isSandbox('agoda'));

        // 3. Attempting to activate LIVE without approved production credentials MUST throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot activate Live Production mode');
        $this->modeService->setMode('live', 'agoda', $this->manager->id);
    }

    /**
     * Test 3: Remote state maintains independent inventory per partner in database.
     */
    public function test_stateful_remote_inventory_persists_independently_per_channel(): void
    {
        $targetDate = Carbon::today()->addDays(2)->format('Y-m-d');

        // Provision Booking.com and Agoda properties
        $bookingProp = $this->remoteStateService->ensureProvisioned($this->hotel->id, 'booking_com');
        $agodaProp = $this->remoteStateService->ensureProvisioned($this->hotel->id, 'agoda');

        // Push 3 units to Booking.com
        $this->remoteStateService->commitPushedInventory($this->hotel->id, 'booking_com', [
            ['room_type_id' => $this->deluxeType->id, 'date' => $targetDate, 'available' => 3, 'rate' => 25000]
        ]);

        // Push 5 units to Agoda
        $this->remoteStateService->commitPushedInventory($this->hotel->id, 'agoda', [
            ['room_type_id' => $this->deluxeType->id, 'date' => $targetDate, 'available' => 5, 'rate' => 25000]
        ]);

        // Check independent remote database records
        $bookingInv = SimulatedPartnerInventory::where('property_id', $bookingProp->id)
            ->where('date', $targetDate)
            ->first();

        $agodaInv = SimulatedPartnerInventory::where('property_id', $agodaProp->id)
            ->where('date', $targetDate)
            ->first();

        $this->assertNotNull($bookingInv);
        $this->assertNotNull($agodaInv);
        $this->assertEquals(3, $bookingInv->available_units);
        $this->assertEquals(5, $agodaInv->available_units);
        $this->assertNotEquals($bookingInv->available_units, $agodaInv->available_units);
    }

    /**
     * Test 4: Failure Injection Lab forces real adapter failures without mutating remote state.
     */
    public function test_failure_injection_lab_triggers_real_adapter_failures_and_isolates_channels(): void
    {
        $targetDate = Carbon::today()->addDays(3)->format('Y-m-d');
        $conn = ChannelConnection::create([
            'hotel_id' => $this->hotel->id,
            'channel_id' => Channel::create(['name' => 'Agoda', 'code' => 'agoda', 'type' => 'ota', 'is_active' => true])->id,
            'is_enabled' => true,
        ]);

        // 1. Initial push: 5 units succeeds
        $adapter = $this->syncService->getAdapter('agoda');
        $res1 = $adapter->pushInventory($conn, [
            ['room_type_id' => $this->deluxeType->id, 'date' => $targetDate, 'available' => 5]
        ]);
        $this->assertTrue($res1['success']);
        $this->assertEquals(200, $res1['status_code']);

        // 2. Inject HTTP 504 Timeout on Agoda
        SimulatedFailureConfig::updateOrCreate(
            ['partner_code' => 'agoda', 'operation' => 'inventory_push'],
            ['failure_type' => '504_timeout', 'http_status' => 504, 'is_active' => true]
        );

        // 3. Second push: attempts to set 2 units -> MUST return 504 failure
        $res2 = $adapter->pushInventory($conn, [
            ['room_type_id' => $this->deluxeType->id, 'date' => $targetDate, 'available' => 2]
        ]);
        $this->assertFalse($res2['success']);
        $this->assertEquals(504, $res2['status_code']);

        // 4. Crucial: Remote inventory MUST STILL be 5 (drift preserved because update failed!)
        $prop = SimulatedPartnerProperty::where('partner_code', 'agoda')->where('hotel_id', $this->hotel->id)->first();
        $remoteInv = SimulatedPartnerInventory::where('property_id', $prop->id)->where('date', $targetDate)->first();
        $this->assertEquals(5, $remoteInv->available_units);
    }

    /**
     * Test 5: Inbound OTA Booking Webhook decrements Central PMS inventory.
     */
    public function test_inbound_ota_booking_creates_reservation_and_decrements_central_capacity(): void
    {
        $stayDate = Carbon::today()->addDays(5)->format('Y-m-d');
        $checkout = Carbon::today()->addDays(6)->format('Y-m-d');

        // Seed central inventory with 4 units
        CentralInventory::updateOrCreate(
            ['hotel_id' => $this->hotel->id, 'room_type_id' => $this->deluxeType->id, 'date' => $stayDate],
            ['total_units' => 5, 'available_units' => 4, 'reserved_units' => 1, 'base_rate' => 25000]
        );

        $payload = [
            'external_reservation_id' => 'AGD-RES-TEST-001',
            'property_code' => "AGD-{$this->hotel->code}",
            'remote_room_code' => $this->deluxeType->code,
            'check_in_date' => $stayDate,
            'check_out_date' => $checkout,
            'guest_count' => 2,
            'guest_name' => 'Taro Suzuki',
            'guest_email' => 'taro.suzuki@example.jp',
            'price' => 25000,
            'idempotency_key' => 'IDEM-KEY-001',
        ];

        $res = $this->inboundService->processInboundBooking('agoda', $payload);

        $this->assertTrue($res['success']);
        $this->assertEquals(201, $res['status_code']);
        $this->assertNotNull($res['confirmation_code']);

        // Central inventory must now have 3 available units and 2 reserved units
        $centralAfter = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->deluxeType->id)
            ->where('date', $stayDate)
            ->first();

        $this->assertEquals(3, $centralAfter->available_units);
        $this->assertEquals(2, $centralAfter->reserved_units);

        // Reservation record exists in DB
        $pmsRes = Reservation::find($res['clearstay_reservation_id']);
        $this->assertNotNull($pmsRes);
        $this->assertEquals('confirmed', $pmsRes->status);
    }

    /**
     * Test 6: Inbound OTA Booking Idempotency prevents duplicate bookings.
     */
    public function test_inbound_ota_booking_idempotency_blocks_duplicate_deliveries(): void
    {
        $stayDate = Carbon::today()->addDays(8)->format('Y-m-d');
        $checkout = Carbon::today()->addDays(9)->format('Y-m-d');

        CentralInventory::updateOrCreate(
            ['hotel_id' => $this->hotel->id, 'room_type_id' => $this->deluxeType->id, 'date' => $stayDate],
            ['total_units' => 5, 'available_units' => 4, 'reserved_units' => 1, 'base_rate' => 25000]
        );

        $idempotencyKey = 'IDEM-UNIQUE-' . Str::uuid();
        $payload = [
            'external_reservation_id' => 'BKG-RES-IDEM-001',
            'property_code' => "BKG-{$this->hotel->code}",
            'remote_room_code' => $this->deluxeType->code,
            'check_in_date' => $stayDate,
            'check_out_date' => $checkout,
            'guest_count' => 2,
            'guest_name' => 'Elena Rostova',
            'price' => 25000,
            'idempotency_key' => $idempotencyKey,
        ];

        // First delivery: HTTP 201
        $res1 = $this->inboundService->processInboundBooking('booking_com', $payload);
        $this->assertEquals(201, $res1['status_code']);

        // Second delivery with identical idempotency key: HTTP 200 duplicate_acknowledged
        $res2 = $this->inboundService->processInboundBooking('booking_com', $payload);
        $this->assertEquals(200, $res2['status_code']);
        $this->assertEquals('duplicate_acknowledged', $res2['status']);

        // Inventory was only decremented ONCE (4 -> 3, NOT 4 -> 2)
        $centralAfter = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->deluxeType->id)
            ->where('date', $stayDate)
            ->first();

        $this->assertEquals(3, $centralAfter->available_units);
    }

    /**
     * Test 7: Inbound Booking rejects capacity exhaustion (HTTP 409).
     */
    public function test_inbound_ota_booking_rejects_capacity_exhaustion(): void
    {
        $stayDate = Carbon::today()->addDays(10)->format('Y-m-d');
        $checkout = Carbon::today()->addDays(11)->format('Y-m-d');

        // Central inventory is completely sold out: 0 available units
        CentralInventory::updateOrCreate(
            ['hotel_id' => $this->hotel->id, 'room_type_id' => $this->deluxeType->id, 'date' => $stayDate],
            ['total_units' => 5, 'available_units' => 0, 'reserved_units' => 5, 'base_rate' => 25000]
        );

        $payload = [
            'external_reservation_id' => 'EXP-RES-CAPACITY-001',
            'property_code' => "EXP-{$this->hotel->code}",
            'remote_room_code' => $this->deluxeType->code,
            'check_in_date' => $stayDate,
            'check_out_date' => $checkout,
            'guest_count' => 2,
            'guest_name' => 'John Doe',
            'price' => 25000,
        ];

        $res = $this->inboundService->processInboundBooking('expedia', $payload);
        $this->assertFalse($res['success']);
        $this->assertEquals(409, $res['status_code']);
        $this->assertEquals('CAPACITY_EXHAUSTED', $res['error_code']);
    }

    /**
     * Test 8: Inbound Booking rejects unmapped remote room code (HTTP 422).
     */
    public function test_inbound_ota_booking_rejects_unmapped_room_codes(): void
    {
        $payload = [
            'external_reservation_id' => 'BKG-RES-UNMAPPED',
            'property_code' => "BKG-{$this->hotel->code}",
            'remote_room_code' => 'BKG_NONEXISTENT_PENTHOUSE',
            'check_in_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'guest_name' => 'Mystery Guest',
        ];

        $res = $this->inboundService->processInboundBooking('booking_com', $payload);
        $this->assertFalse($res['success']);
        $this->assertEquals(422, $res['status_code']);
        $this->assertEquals('UNMAPPED_ROOM_CODE', $res['error_code']);
    }

    /**
     * Test 9: Full Channel Audit compares central against actual remote simulator inventory.
     */
    public function test_full_channel_audit_compares_central_against_actual_remote_simulator_inventory(): void
    {
        $this->actingAs($this->manager);
        session(['current_hotel_id' => $this->hotel->id]);

        $ch = Channel::create(['name' => 'Agoda', 'code' => 'agoda', 'type' => 'ota', 'is_active' => true]);
        ChannelConnection::create(['hotel_id' => $this->hotel->id, 'channel_id' => $ch->id, 'is_enabled' => true]);

        // Seed central inventory for 14 days
        for ($i = 0; $i < 14; $i++) {
            $d = Carbon::today()->addDays($i)->format('Y-m-d');
            CentralInventory::create([
                'hotel_id' => $this->hotel->id,
                'room_type_id' => $this->deluxeType->id,
                'date' => $d,
                'total_units' => 5,
                'available_units' => 5,
                'reserved_units' => 0,
                'base_rate' => 20000,
            ]);
        }

        // Initialize remote inventory matching central (5 units)
        $this->remoteStateService->initializeRemoteInventory($this->hotel->id, 'agoda', 14);

        // Run audit via HTTP
        $res = $this->post(route('inventory_reconciliation.audit'));
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // Check view page loads with findings
        $page = $this->get(route('inventory_reconciliation.index'));
        $page->assertOk();
        $page->assertSee('in_sync');
    }

    /**
     * Test 10: Real RFC 5545 iCalendar feed export returns valid iCal text.
     */
    public function test_icalendar_feed_exports_valid_rfc_5545_availability_format(): void
    {
        $res = $this->get(route('ical.export', ['hotel' => $this->hotel->id, 'roomType' => $this->deluxeType->id]));

        $res->assertOk();
        $this->assertStringContainsString('text/calendar', $res->headers->get('content-type'));
        $content = $res->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('VERSION:2.0', $content);
        $this->assertStringContainsString('PRODID:-//ClearStay Hospitality//ClearStay 4.2 iCal Engine//EN', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    /**
     * Test 11: All 15 Interviewer Demonstration Scenarios execute cleanly.
     */
    public function test_all_15_interviewer_demonstration_scenarios_execute_cleanly(): void
    {
        $this->actingAs($this->manager);
        session(['current_hotel_id' => $this->hotel->id]);

        for ($scenarioId = 1; $scenarioId <= 15; $scenarioId++) {
            $res = $this->postJson(route('simulators.run_scenario', ['id' => $scenarioId]));
            $res->assertOk();
            $res->assertJsonPath('success', true);
            $res->assertJsonPath('scenario_id', $scenarioId);

            $data = $res->json('data');
            $this->assertTrue($data['success'], "Scenario {$scenarioId} reported failure: " . json_encode($data));
            $this->assertNotEmpty($data['title']);
            $this->assertNotEmpty($data['final_result']);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SimulatedPartnerInventory;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerRoomMapping;
use App\Models\SyncOutboxEvent;
use App\Models\User;
use App\Services\Inventory\RoomAvailabilityMatrixService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveRoomAvailabilityMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotelA;
    protected Hotel $hotelB;
    protected User $managerUser;
    protected User $frontdeskUser;
    protected RoomType $deluxeRoom;
    protected RoomType $suiteRoom;
    protected Channel $agodaChannel;
    protected Channel $bookingChannel;
    protected RoomAvailabilityMatrixService $matrixService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotelA = Hotel::create([
            'name' => 'ClearStay Premier Tokyo',
            'slug' => 'clearstay-premier-tokyo',
            'code' => 'TYO-PREM',
            'city' => 'Tokyo',
            'address' => '1-1 Marunouchi, Chiyoda-ku',
            'total_rooms' => 15,
            'is_active' => true,
        ]);

        $this->hotelB = Hotel::create([
            'name' => 'ClearStay Kyoto Ryokan',
            'slug' => 'clearstay-kyoto-ryokan',
            'code' => 'KYO-RYO',
            'city' => 'Kyoto',
            'address' => '2-2 Gion, Higashiyama-ku',
            'total_rooms' => 8,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'hotel_id' => $this->hotelA->id,
            'name' => 'Kenji Manager',
            'email' => 'manager.test@clearstay.jp',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->frontdeskUser = User::create([
            'hotel_id' => $this->hotelA->id,
            'name' => 'Yuki Receptionist',
            'email' => 'reception.test@clearstay.jp',
            'password' => bcrypt('password'),
            'role' => 'frontdesk',
            'is_active' => true,
        ]);

        $this->deluxeRoom = RoomType::create([
            'hotel_id' => $this->hotelA->id,
            'name' => 'Superior Queen Double',
            'code' => 'SUP-QNN',
            'base_price' => 24000,
            'total_rooms' => 5,
            'capacity' => 2,
        ]);

        $this->suiteRoom = RoomType::create([
            'hotel_id' => $this->hotelA->id,
            'name' => 'Presidential Tatami Suite',
            'code' => 'PRS-TAT',
            'base_price' => 58000,
            'total_rooms' => 2,
            'capacity' => 4,
        ]);

        // Physical rooms
        for ($i = 1; $i <= 5; $i++) {
            Room::create([
                'hotel_id' => $this->hotelA->id,
                'room_type_id' => $this->deluxeRoom->id,
                'room_number' => '10' . $i,
                'status' => 'available',
            ]);
        }
        for ($i = 1; $i <= 2; $i++) {
            Room::create([
                'hotel_id' => $this->hotelA->id,
                'room_type_id' => $this->suiteRoom->id,
                'room_number' => '50' . $i,
                'status' => 'available',
            ]);
        }

        // Active Channels
        $this->agodaChannel = Channel::create([
            'code' => 'agoda',
            'name' => 'Agoda',
            'type' => 'ota',
            'is_active' => true,
        ]);

        $this->bookingChannel = Channel::create([
            'code' => 'booking_com',
            'name' => 'Booking.com',
            'type' => 'ota',
            'is_active' => true,
        ]);

        ChannelConnection::create([
            'hotel_id' => $this->hotelA->id,
            'channel_id' => $this->agodaChannel->id,
            'is_enabled' => true,
            'sync_status' => 'ok',
        ]);

        ChannelConnection::create([
            'hotel_id' => $this->hotelA->id,
            'channel_id' => $this->bookingChannel->id,
            'is_enabled' => true,
            'sync_status' => 'ok',
        ]);

        $this->matrixService = app(RoomAvailabilityMatrixService::class);
    }

    /**
     * Test 1: Physical capacity, reserved units, maintenance units, and available units calculation.
     */
    public function test_central_inventory_calculates_correct_available_units(): void
    {
        $testDate = '2026-12-20';

        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => $testDate,
            'total_units' => 5,
            'reserved_units' => 2,
            'maintenance_units' => 1,
            'available_units' => 2, // 5 - 2 - 1 = 2
        ]);

        $matrix = $this->matrixService->getMatrixForDate($this->hotelA->id, $testDate);

        $this->assertEquals('single', $matrix['view']);
        $this->assertCount(2, $matrix['rows']);

        $deluxeRow = collect($matrix['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);
        $this->assertNotNull($deluxeRow);
        $this->assertEquals(5, $deluxeRow['pms']['total_units']);
        $this->assertEquals(2, $deluxeRow['pms']['reserved_units']);
        $this->assertEquals(1, $deluxeRow['pms']['maintenance_units']);
        $this->assertEquals(2, $deluxeRow['pms']['available_units']);
        $this->assertEquals('LOW_STOCK', $deluxeRow['pms']['status']);
    }

    /**
     * Test 2: Missing inventory record on selected date flags MISSING_INVENTORY without mutating DB.
     */
    public function test_missing_inventory_record_flags_status_and_does_not_mutate_db(): void
    {
        $testDate = '2026-12-25';

        $beforeCount = CentralInventory::count();
        $matrix = $this->matrixService->getMatrixForDate($this->hotelA->id, $testDate);
        $afterCount = CentralInventory::count();

        $this->assertEquals($beforeCount, $afterCount, 'Page load and read-only query must not mutate or insert inventory.');

        $deluxeRow = collect($matrix['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);
        $this->assertEquals('MISSING_INVENTORY', $deluxeRow['pms']['status']);
        $this->assertFalse($deluxeRow['pms']['has_record']);
    }

    /**
     * Test 3: Multi-night stay availability excludes checkout date and computes minimum capacity.
     */
    public function test_multi_night_stay_excludes_checkout_and_evaluates_min_availability(): void
    {
        $checkIn = '2026-12-20';
        $checkOut = '2026-12-23'; // 3 nights: 20, 21, 22. Dec 23 is checkout!

        // Night 1: 5 available
        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => '2026-12-20',
            'total_units' => 5,
            'reserved_units' => 0,
            'maintenance_units' => 0,
            'available_units' => 5,
        ]);

        // Night 2: 2 available (constraining night!)
        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => '2026-12-21',
            'total_units' => 5,
            'reserved_units' => 3,
            'maintenance_units' => 0,
            'available_units' => 2,
        ]);

        // Night 3: 4 available
        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => '2026-12-22',
            'total_units' => 5,
            'reserved_units' => 1,
            'maintenance_units' => 0,
            'available_units' => 4,
        ]);

        // Checkout date (2026-12-23) has 0 available, but MUST be excluded!
        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => '2026-12-23',
            'total_units' => 5,
            'reserved_units' => 5,
            'maintenance_units' => 0,
            'available_units' => 0,
        ]);

        $multiStay = $this->matrixService->getMatrixForStayRange(
            $this->hotelA->id,
            $checkIn,
            $checkOut,
            2 // requested 2 rooms
        );

        $this->assertEquals(3, $multiStay['nights_count']);
        $this->assertFalse(in_array('2026-12-23', $multiStay['nights']), 'Checkout date must be excluded from occupied stay nights.');

        $deluxeStay = collect($multiStay['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);
        $this->assertEquals(2, $deluxeStay['min_pms_available'], 'Minimum available across the stay is 2.');
        $this->assertEquals(5, $deluxeStay['max_pms_available']);
        $this->assertTrue($deluxeStay['can_accommodate'], 'Can accommodate 2 rooms because minimum availability is 2.');

        // If requesting 3 rooms, it should be marked as insufficient
        $insufficientStay = $this->matrixService->getMatrixForStayRange(
            $this->hotelA->id,
            $checkIn,
            $checkOut,
            3 // requested 3 rooms
        );
        $insufficientDeluxe = collect($insufficientStay['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);
        $this->assertFalse($insufficientDeluxe['can_accommodate']);
        $this->assertCount(1, $insufficientDeluxe['insufficient_dates']);
        $this->assertEquals('2026-12-21', $insufficientDeluxe['insufficient_dates'][0]['date']);
    }

    /**
     * Test 4: Independent remote inventory reads and discrepancy detection.
     */
    public function test_independent_remote_inventory_reads_and_detects_discrepancy(): void
    {
        $testDate = '2026-12-20';

        // PMS has 5 available
        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => $testDate,
            'total_units' => 5,
            'reserved_units' => 0,
            'maintenance_units' => 0,
            'available_units' => 5,
        ]);

        // Agoda property & mapping
        $agodaProp = SimulatedPartnerProperty::create([
            'hotel_id' => $this->hotelA->id,
            'partner_code' => 'agoda',
            'property_code' => 'AGD-TYO-PREM',
            'name' => 'Agoda Tokyo Property',
            'is_connected' => true,
        ]);

        $agodaMapping = SimulatedPartnerRoomMapping::create([
            'property_id' => $agodaProp->id,
            'room_type_id' => $this->deluxeRoom->id,
            'partner_code' => 'agoda',
            'remote_room_code' => 'AGD_SUP_QNN',
            'remote_room_name' => 'Agoda Superior Queen',
            'is_active' => true,
        ]);

        // Booking.com property & mapping
        $bkgProp = SimulatedPartnerProperty::create([
            'hotel_id' => $this->hotelA->id,
            'partner_code' => 'booking_com',
            'property_code' => 'BKG-TYO-PREM',
            'name' => 'Booking Tokyo Property',
            'is_connected' => true,
        ]);

        $bkgMapping = SimulatedPartnerRoomMapping::create([
            'property_id' => $bkgProp->id,
            'room_type_id' => $this->deluxeRoom->id,
            'partner_code' => 'booking_com',
            'remote_room_code' => 'BKG_SUP_QNN',
            'remote_room_name' => 'Booking Superior Queen',
            'is_active' => true,
        ]);

        // Booking.com has 5 (MATCHED)
        SimulatedPartnerInventory::create([
            'property_id' => $bkgProp->id,
            'partner_code' => 'booking_com',
            'remote_room_code' => $bkgMapping->remote_room_code,
            'date' => $testDate,
            'available_units' => 5,
            'last_synced_at' => now(),
        ]);

        // Agoda has 3 (DISCREPANCY: PMS 5 vs Agoda 3)
        SimulatedPartnerInventory::create([
            'property_id' => $agodaProp->id,
            'partner_code' => 'agoda',
            'remote_room_code' => $agodaMapping->remote_room_code,
            'date' => $testDate,
            'available_units' => 3,
            'last_synced_at' => now()->subHour(),
        ]);

        $matrix = $this->matrixService->getMatrixForDate($this->hotelA->id, $testDate);
        $deluxeRow = collect($matrix['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);

        $this->assertEquals(5, $deluxeRow['pms']['available_units']);
        $this->assertEquals(5, $deluxeRow['channels']['booking_com']['remote_units']);
        $this->assertEquals('MATCHED', $deluxeRow['channels']['booking_com']['status']);

        $this->assertEquals(3, $deluxeRow['channels']['agoda']['remote_units']);
        $this->assertEquals('DISCREPANCY', $deluxeRow['channels']['agoda']['status']);
        $this->assertEquals(2, $deluxeRow['channels']['agoda']['difference'], 'PMS 5 - Agoda 3 = Discrepancy of 2');
        $this->assertTrue($deluxeRow['has_discrepancy']);
    }

    /**
     * Test 5: Critical oversell risk is detected when remote channel has MORE inventory than PMS.
     */
    public function test_oversell_risk_detected_when_remote_inventory_exceeds_pms(): void
    {
        $testDate = '2026-12-20';

        // PMS has only 2 rooms left
        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => $testDate,
            'total_units' => 5,
            'reserved_units' => 3,
            'maintenance_units' => 0,
            'available_units' => 2,
        ]);

        $agodaProp = SimulatedPartnerProperty::create([
            'hotel_id' => $this->hotelA->id,
            'partner_code' => 'agoda',
            'property_code' => 'AGD-TYO-PREM',
            'name' => 'Agoda Tokyo Property',
            'is_connected' => true,
        ]);

        $agodaMapping = SimulatedPartnerRoomMapping::create([
            'property_id' => $agodaProp->id,
            'room_type_id' => $this->deluxeRoom->id,
            'partner_code' => 'agoda',
            'remote_room_code' => 'AGD_SUP_QNN',
            'remote_room_name' => 'Agoda Superior Queen',
            'is_active' => true,
        ]);

        // Agoda still offers 4 rooms (exceeding PMS by 2!)
        SimulatedPartnerInventory::create([
            'property_id' => $agodaProp->id,
            'partner_code' => 'agoda',
            'remote_room_code' => $agodaMapping->remote_room_code,
            'date' => $testDate,
            'available_units' => 4,
            'last_synced_at' => now(),
        ]);

        $matrix = $this->matrixService->getMatrixForDate($this->hotelA->id, $testDate);
        $deluxeRow = collect($matrix['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);

        $this->assertEquals('OVERSELL_RISK', $deluxeRow['channels']['agoda']['status']);
        $this->assertTrue($deluxeRow['has_oversell_risk']);
    }

    /**
     * Test 6: One-click channel re-alignment synchronizes remote inventory to match PMS.
     */
    public function test_one_click_channel_realignment_synchronizes_remote_inventory(): void
    {
        $testDate = '2026-12-20';

        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => $testDate,
            'total_units' => 5,
            'reserved_units' => 1,
            'maintenance_units' => 0,
            'available_units' => 4,
        ]);

        $realignResult = $this->matrixService->realignChannelInventory(
            $this->hotelA->id,
            $this->deluxeRoom->id,
            $testDate,
            'agoda',
            $this->managerUser->id
        );

        $this->assertTrue($realignResult['success']);
        $this->assertEquals(4, $realignResult['available_units']);

        // Verify remote record in DB
        $matrix = $this->matrixService->getMatrixForDate($this->hotelA->id, $testDate);
        $deluxeRow = collect($matrix['rows'])->firstWhere('room_type_id', $this->deluxeRoom->id);

        $this->assertEquals(4, $deluxeRow['channels']['agoda']['remote_units']);
        $this->assertEquals('MATCHED', $deluxeRow['channels']['agoda']['status']);
    }

    /**
     * Test 7: Multi-tenant property isolation prevents cross-hotel inventory visibility.
     */
    public function test_multi_tenant_isolation_enforced(): void
    {
        $testDate = '2026-12-20';

        // Hotel B room type and inventory
        $kyotoRoom = RoomType::create([
            'hotel_id' => $this->hotelB->id,
            'name' => 'Kyoto Garden Villa',
            'code' => 'KYO-VIL',
            'base_price' => 75000,
            'total_rooms' => 3,
            'capacity' => 4,
        ]);

        CentralInventory::create([
            'hotel_id' => $this->hotelB->id,
            'room_type_id' => $kyotoRoom->id,
            'date' => $testDate,
            'total_units' => 3,
            'available_units' => 3,
            'reserved_units' => 0,
            'maintenance_units' => 0,
        ]);

        $matrixA = $this->matrixService->getMatrixForDate($this->hotelA->id, $testDate);
        $roomTypeIdsInA = collect($matrixA['rows'])->pluck('room_type_id')->toArray();

        $this->assertFalse(in_array($kyotoRoom->id, $roomTypeIdsInA), 'Hotel A matrix must not expose Hotel B room types.');
    }

    /**
     * Test 8: HTTP Web Routes, AJAX Data Endpoint, Quantity Ledger, and CSV Export.
     */
    public function test_web_routes_and_export_endpoints_functional(): void
    {
        $testDate = '2026-12-20';

        CentralInventory::create([
            'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->deluxeRoom->id,
            'date' => $testDate,
            'total_units' => 5,
            'reserved_units' => 1,
            'maintenance_units' => 0,
            'available_units' => 4,
        ]);

        // 1. GET /room-availability
        $response = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->get('/room-availability?stay_date=' . $testDate);

        $response->assertStatus(200);
        $response->assertSee('Live Room Availability Matrix');
        $response->assertSee('Superior Queen Double');

        // 2. GET /room-availability (Multi-Night)
        $multiRes = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->get('/room-availability?view=multi&check_in_date=' . $testDate . '&check_out_date=2026-12-22');

        $multiRes->assertStatus(200);
        $multiRes->assertSee('Multi-Night Stay Availability Matrix');

        // 3. GET /room-availability/matrix-data (AJAX)
        $ajaxRes = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->getJson('/room-availability/matrix-data?stay_date=' . $testDate);

        $ajaxRes->assertStatus(200);
        $ajaxRes->assertJsonPath('success', true);

        // 4. GET /room-availability/details (Modal Ledger)
        $detailRes = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->getJson("/room-availability/details?room_type_id={$this->deluxeRoom->id}&date={$testDate}&channel_code=agoda");

        $detailRes->assertStatus(200);
        $detailRes->assertJsonPath('success', true);
        $detailRes->assertJsonPath('details.pms.available_units', 4);

        // 5. POST /room-availability/realign
        $realignRes = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->postJson('/room-availability/realign', [
                'room_type_id' => $this->deluxeRoom->id,
                'date' => $testDate,
                'partner_code' => 'agoda',
            ]);

        $realignRes->assertStatus(200);
        $realignRes->assertJsonPath('success', true);

        // 6. GET /room-availability/export (CSV)
        $csvRes = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->get('/room-availability/export?stay_date=' . $testDate . '&format=csv');

        $csvRes->assertStatus(200);
        $csvRes->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // 7. GET /room-availability/export (Print)
        $printRes = $this->actingAs($this->managerUser)
            ->withSession(['current_hotel_id' => $this->hotelA->id])
            ->get('/room-availability/export?stay_date=' . $testDate . '&format=print');

        $printRes->assertStatus(200);
        $printRes->assertSee('Live Room Availability Report');
    }
}

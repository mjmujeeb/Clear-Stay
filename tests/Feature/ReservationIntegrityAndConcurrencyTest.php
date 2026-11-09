<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\InventoryLedgerEntry;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Reservation\ReservationIntegrityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationIntegrityAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected Hotel $hotel;
    protected RoomType $roomType;
    protected Room $room;
    protected ReservationIntegrityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Tokyo Ginza',
            'slug' => 'clearstay-tokyo-ginza',
            'code' => 'TYO-GNZ',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 10,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Executive Suite',
            'code' => 'EXEC-01',
            'base_price' => 25000.00,
            'capacity' => 2,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '501',
            'floor' => 5,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->manager = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->service = app(ReservationIntegrityService::class);
    }

    public function test_row_locked_reservation_creation_succeeds_and_appends_ledger_entries()
    {
        $checkIn = Carbon::tomorrow()->toDateString();
        $checkOut = Carbon::tomorrow()->addDays(2)->toDateString();

        $res = $this->service->createReservationWithLock([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guest_first_name' => 'Taro',
            'guest_last_name' => 'Yamada',
            'guest_email' => 'taro.yamada@example.test',
            'source' => 'DIRECT_PMS',
        ], null, $this->manager);

        $this->assertTrue($res['success']);
        $this->assertEquals('ALLOCATED', $res['status']);
        $this->assertNotNull($res['reservation']);

        // Verify ledger entries created for both nights
        $ledgerCount = InventoryLedgerEntry::where('reservation_id', $res['reservation']->id)->count();
        $this->assertEquals(2, $ledgerCount);
    }

    public function test_concurrent_booking_for_overlapping_dates_is_strictly_blocked()
    {
        $checkIn = Carbon::tomorrow()->toDateString();
        $checkOut = Carbon::tomorrow()->addDays(3)->toDateString();

        // 1. Initial reservation
        $res1 = $this->service->createReservationWithLock([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guest_email' => 'booking1@example.test',
        ]);
        $this->assertTrue($res1['success']);

        // 2. Overlapping reservation attempt
        $res2 = $this->service->createReservationWithLock([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'check_in' => Carbon::tomorrow()->addDay()->toDateString(), // Overlaps!
            'check_out' => Carbon::tomorrow()->addDays(4)->toDateString(),
            'guest_email' => 'booking2@example.test',
        ]);

        $this->assertFalse($res2['success']);
        $this->assertEquals('CAPACITY_EXHAUSTED', $res2['status']);
        $this->assertTrue($res2['conflict']);
    }

    public function test_concurrency_simulation_endpoint_executes_ten_competing_requests()
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/reservation-integrity/simulate', [
                'concurrency_count' => 10,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'concurrency_attempts' => 10,
            'successful_allocations' => 1,
            'prevented_conflicts' => 9,
        ]);
    }

    public function test_idempotent_request_replay_returns_cached_reservation()
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/reservation-integrity/idempotency');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deduplication_verified' => true,
        ]);
    }

    public function test_reservation_integrity_hub_view_renders_successfully()
    {
        $response = $this->actingAs($this->manager)
            ->get('/reservation-integrity');

        $response->assertStatus(200);
        $response->assertSee('Reservation Integrity & Capacity Intelligence');
        $response->assertSee('Pessimistic Row Locking Architecture');
        $response->assertSee('Strategy 1: Zero Overbooking');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClearStayCoreTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected Room $room101;
    protected Room $room102;
    protected Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed test hotel
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

        $this->manager = User::create([
            'name' => 'Test Hotel Manager',
            'email' => 'manager@clearstay.test',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'locale' => 'en',
            'is_active' => true,
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Deluxe',
            'code' => 'STD-DLX',
            'capacity' => 2,
            'base_price' => 15000,
        ]);

        $this->room101 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->room102 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'room_number' => '102',
            'floor' => 1,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'email' => 'taro.yamada@example.test',
            'phone' => '+81-90-1111-2222',
        ]);
    }

    /**
     * Test public SEO routes are accessible.
     */
    public function test_public_and_seo_routes_respond_ok(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response = $this->get('/rooms');
        $response->assertStatus(200);

        $response = $this->get('/features');
        $response->assertStatus(200);

        $response = $this->get('/contact');
        $response->assertStatus(200);

        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertSee('Sitemap:');
    }

    /**
     * Test staff authentication and tenant session assignment.
     */
    public function test_staff_authentication_sets_tenant_session(): void
    {
        $response = $this->post('/login', [
            'email' => 'manager@clearstay.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->manager);
        $this->assertEquals($this->hotel->id, session('current_hotel_id'));
    }

    /**
     * Test Pessimistic Row-Locked Date Modification Service.
     */
    public function test_row_locked_date_modification_succeeds_and_logs_audit(): void
    {
        $today = Carbon::today();

        $reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room101->id,
            'confirmation_code' => 'CS-TEST-001',
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->copy()->addDays(5)->toDateString(),
            'check_out_date' => $today->copy()->addDays(8)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'total_price' => 45000,
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $service = app(ReservationService::class);

        // Shift dates forward by 2 days safely
        $newCheckIn = $today->copy()->addDays(7)->toDateString();
        $newCheckOut = $today->copy()->addDays(10)->toDateString();

        $updated = $service->changeDatesWithRowLock(
            $reservation->id,
            $newCheckIn,
            $newCheckOut,
            $this->room101->id,
            'Guest extension request',
            $this->manager->id
        );

        $this->assertEquals($newCheckIn, $updated->check_in_date->toDateString());
        $this->assertEquals($newCheckOut, $updated->check_out_date->toDateString());

        // Check audit trail recorded in reservation_changes
        $this->assertDatabaseHas('reservation_changes', [
            'reservation_id' => $reservation->id,
            'reason' => 'Guest extension request',
        ]);

        $change = $reservation->fresh()->changes->first();
        $this->assertNotNull($change);
        $this->assertEquals($newCheckIn, $change->new_check_in_date->toDateString());
    }

    /**
     * Test Concurrency Conflict Detection:
     * Attempting to shift dates into an existing reservation throws ValidationException.
     */
    public function test_row_locked_date_modification_detects_conflict(): void
    {
        $today = Carbon::today();

        // Reservation A: Days 5 to 10 on Room 101
        $resA = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room101->id,
            'confirmation_code' => 'CS-RESA-001',
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->copy()->addDays(5)->toDateString(),
            'check_out_date' => $today->copy()->addDays(10)->toDateString(),
            'adults' => 1,
            'total_price' => 75000,
            'status' => 'confirmed',
        ]);

        // Reservation B: Days 15 to 20 on Room 102
        $resB = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room102->id,
            'confirmation_code' => 'CS-RESB-002',
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->copy()->addDays(15)->toDateString(),
            'check_out_date' => $today->copy()->addDays(20)->toDateString(),
            'adults' => 1,
            'total_price' => 75000,
            'status' => 'confirmed',
        ]);

        $service = app(ReservationService::class);

        // Attempting to move Res B to Room 101 on Days 7 to 12 (overlaps with Res A!)
        $this->expectException(ValidationException::class);

        $service->changeDatesWithRowLock(
            $resB->id,
            $today->copy()->addDays(7)->toDateString(),
            $today->copy()->addDays(12)->toDateString(),
            $this->room101->id,
            'Conflicting move'
        );
    }

    /**
     * Test Live Board Status AJAX endpoint returns json.
     */
    public function test_live_board_status_endpoint_returns_valid_json(): void
    {
        $this->actingAs($this->manager);

        $response = $this->getJson('/board/status');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'timestamp',
            'counts' => ['total', 'clean', 'dirty', 'inspecting', 'maintenance', 'occupied'],
            'rooms',
        ]);
    }

    /**
     * Test Guest Token Self-Service Portal.
     */
    public function test_guest_portal_is_accessible_via_token(): void
    {
        $today = Carbon::today();

        $token = 'sample-guest-secret-token-123456';
        $reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room101->id,
            'confirmation_code' => 'CS-GUEST-PORTAL',
            'guest_portal_token' => $token,
            'check_in_date' => $today->toDateString(),
            'check_out_date' => $today->copy()->addDays(2)->toDateString(),
            'adults' => 1,
            'total_price' => 30000,
            'status' => 'confirmed',
        ]);

        $response = $this->get('/guest/portal/' . $token);
        $response->assertStatus(200);
        $response->assertSee('Taro');
        $response->assertSee('CS-GUEST-PORTAL');
    }
}

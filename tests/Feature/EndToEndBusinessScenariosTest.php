<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EndToEndBusinessScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected RoomType $roomType;
    protected Room $room;
    protected User $admin;
    protected User $receptionist;
    protected User $housekeeper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Tokyo Ginza Flagship',
            'slug' => 'clearstay-tokyo-ginza',
            'code' => 'CS-TYO-01',
            'city' => 'Tokyo',
            'address' => '6-10-1 Ginza, Chuo-ku, Tokyo',
            'phone' => '+81-3-5555-0101',
            'email' => 'ginza@clearstay.jp',
            'country' => 'Japan',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'total_rooms' => 100,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe King Suite',
            'code' => 'DKS',
            'base_price' => 28000,
            'capacity' => 2,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '501',
            'floor' => 5,
            'status' => 'clean',
        ]);

        $this->admin = User::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Tokyo Lead Administrator',
            'email' => 'admin@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'department' => 'Administration',
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Front Desk Receptionist',
            'email' => 'reception@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'front_desk',
            'department' => 'Front Office',
            'is_active' => true,
        ]);

        $this->housekeeper = User::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Lead Housekeeper',
            'email' => 'housekeeping@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'housekeeping',
            'department' => 'Housekeeping',
            'is_active' => true,
        ]);
    }

    protected function getValidChallengePayload(int $answer = 10): array
    {
        $timestamp = time();
        $payload = "{$answer}:{$timestamp}";
        $secretKey = config('app.key', 'clearstay-default-defense-salt-key-9988');
        $token = base64_encode($payload . ':' . hash_hmac('sha256', $payload, $secretKey));

        return [
            '_cs_challenge_token' => $token,
            '_cs_challenge_answer' => (string) $answer,
            '_cs_rendered_at' => (string) (time() - 3),
        ];
    }

    /**
     * Scenario A: Hotel Administrator Complete Lifecycle
     */
    public function test_scenario_a_hotel_administrator_complete_lifecycle(): void
    {
        // 1. Log in as administrator
        $loginRes = $this->post('/login', array_merge([
            'email' => 'admin@clearstay.jp',
            'password' => 'password',
        ], $this->getValidChallengePayload(10)));
        $loginRes->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);

        // 2. Open dashboard and inspect metrics
        $dashRes = $this->actingAs($this->admin)->get('/dashboard');
        $dashRes->assertOk();
        $dashRes->assertSee('Tokyo Lead Administrator');

        // 3. Inspect multi-property assignment dropdown on registration
        $regPage = $this->actingAs($this->admin)->get('/register');
        $regPage->assertOk();
        $regPage->assertSee($this->hotel->name);
        $regPage->assertSee('Assigned Property (Multi-Tenant)');

        // 4. Register new staff account with manager role and property ID
        $regPayload = array_merge([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.jp',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Operations',
            'locale' => 'ja',
        ], $this->getValidChallengePayload(12));

        $registerRes = $this->post('/register', $regPayload);
        $registerRes->assertRedirect(route('staff.index'));

        // 5. Verify user saved in database with proper hashed password and assigned property
        $createdUser = User::where('email', 'kenji.sato@clearstay.jp')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('manager', $createdUser->role);
        $this->assertEquals($this->hotel->id, $createdUser->hotel_id);
        $this->assertTrue(Hash::check('SecurePass123!', $createdUser->password));

        // 6. Log out
        $this->post('/logout');
        $this->assertGuest();

        // 7. Log in as newly registered manager
        $managerLogin = $this->post('/login', array_merge([
            'email' => 'kenji.sato@clearstay.jp',
            'password' => 'SecurePass123!',
        ], $this->getValidChallengePayload(14)));
        $managerLogin->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($createdUser);

        // 8. Verify manager can view governance list
        $govRes = $this->actingAs($createdUser)->get('/staff-governance');
        $govRes->assertOk();
        $govRes->assertSee('Kenji Sato');

        // 9. Edit permitted user details
        $updateRes = $this->actingAs($createdUser)->post("/staff-governance/{$this->receptionist->id}/update-details", [
            'name' => 'Senior Front Desk Lead',
            'email' => $this->receptionist->email,
            'department' => 'Front Office Operations',
            'hotel_id' => $this->hotel->id,
            'role' => 'frontdesk',
        ]);
        $updateRes->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $this->receptionist->id,
            'name' => 'Senior Front Desk Lead',
            'department' => 'Front Office Operations',
        ]);

        // 10. Verify non-superadmin manager cannot transfer staff across properties (unauthorized operation rejected with 403)
        $otherHotel = Hotel::create([
            'name' => 'ClearStay Kyoto Gion Heritage',
            'slug' => 'clearstay-kyoto-gion',
            'code' => 'CS-GION',
            'city' => 'Kyoto',
            'address' => 'Gion-machi, Kyoto',
            'phone' => '+81-75-555-0101',
            'email' => 'gion@clearstay.jp',
            'country' => 'Japan',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'total_rooms' => 50,
            'is_active' => true,
        ]);

        $unauthRes = $this->actingAs($createdUser)->post("/staff-governance/{$this->receptionist->id}/update-details", [
            'name' => 'Senior Front Desk Lead',
            'email' => $this->receptionist->email,
            'department' => 'Front Office Operations',
            'hotel_id' => $otherHotel->id, // Unauthorized cross-property transfer!
            'role' => 'frontdesk',
        ]);
        $unauthRes->assertForbidden();
        $this->assertEquals($this->hotel->id, $this->receptionist->fresh()->hotel_id);
    }

    /**
     * Scenario B: Hotel Manager and Front Desk Lifecycle & Double-Booking Prevention
     */
    public function test_scenario_b_hotel_manager_front_desk_and_double_booking_prevention(): void
    {
        $manager = User::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Hotel General Manager',
            'email' => 'gm@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        // 1. Manager logs in and inspects dashboard
        $dashRes = $this->actingAs($manager)->get('/dashboard');
        $dashRes->assertOk();

        // 2. Open room reservations
        $createPage = $this->actingAs($manager)->get('/reservations/create');
        $createPage->assertOk();
        $createPage->assertSee('Deluxe King Suite');

        // 3. Validation test: invalid email & checkout before checkin
        $invalidRes = $this->actingAs($manager)->post('/reservations', [
            'guest_mode' => 'new',
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'email' => 'not-an-email',
            'room_id' => $this->room->id,
            'check_in_date' => date('Y-m-d', strtotime('+5 days')),
            'check_out_date' => date('Y-m-d', strtotime('+3 days')),
            'adults' => 2,
        ]);
        $invalidRes->assertSessionHasErrors(['email', 'check_out_date']);

        // 4. Create first reservation
        $checkIn = date('Y-m-d', strtotime('+2 days'));
        $checkOut = date('Y-m-d', strtotime('+4 days'));

        $validRes = $this->actingAs($manager)->post('/reservations', [
            'guest_mode' => 'new',
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'email' => 'taro.yamada@example.com',
            'phone' => '+81-90-1234-5678',
            'room_id' => $this->room->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'adults' => 2,
            'children' => 0,
        ]);
        $validRes->assertSessionHas('success');

        $reservation = Reservation::where('room_id', $this->room->id)->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('confirmed', $reservation->status);
        $this->assertEquals(56000, $reservation->total_price); // 2 nights * 28000

        // 5. Attempt overlapping double booking for the exact same room and dates
        $conflictRes = $this->actingAs($manager)->post('/reservations', [
            'guest_mode' => 'new',
            'first_name' => 'Hanako',
            'last_name' => 'Tanaka',
            'email' => 'hanako.tanaka@example.com',
            'room_id' => $this->room->id,
            'check_in_date' => date('Y-m-d', strtotime('+3 days')), // Overlaps with +2 to +4!
            'check_out_date' => date('Y-m-d', strtotime('+5 days')),
            'adults' => 1,
        ]);
        // System must reject double booking
        $conflictRes->assertSessionHasErrors();
        $this->assertEquals(1, Reservation::where('room_id', $this->room->id)->count());

        // 6. Update reservation status to confirmed
        $this->actingAs($manager)->patch("/reservations/{$reservation->id}/status", [
            'status' => 'confirmed',
        ]);
        $this->assertEquals('confirmed', $reservation->fresh()->status);
    }

    /**
     * Scenario C: Receptionist and Housekeeping Synchronization
     */
    public function test_scenario_c_receptionist_and_housekeeping_synchronization(): void
    {
        // 1. Create a guest and confirmed reservation for today
        $guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Eiji',
            'last_name' => 'Arakawa',
            'email' => 'arakawa@example.com',
        ]);

        $reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $guest->id,
            'room_id' => $this->room->id,
            'check_in_date' => date('Y-m-d'),
            'check_out_date' => date('Y-m-d', strtotime('+2 days')),
            'total_price' => 56000,
            'status' => 'confirmed',
            'confirmation_code' => 'CS-TEST-99',
            'adults' => 1,
            'children' => 0,
        ]);

        // 2. Receptionist logs in and views reservations
        $frontDeskRes = $this->actingAs($this->receptionist)->get('/reservations');
        $frontDeskRes->assertOk();
        $frontDeskRes->assertSee('CS-TEST-99');

        // 3. Receptionist checks in the guest
        $checkinRes = $this->actingAs($this->receptionist)->post("/reservations/{$reservation->id}/check-in");
        $checkinRes->assertSessionHas('success');
        $this->assertEquals('checked_in', $reservation->fresh()->status);
        $this->assertTrue($this->room->fresh()->is_occupied);

        // 4. Guest departs: Receptionist checks out the guest, which automatically flags room as dirty and generates turnover task
        $checkoutRes = $this->actingAs($this->receptionist)->post("/reservations/{$reservation->id}/check-out");
        $checkoutRes->assertSessionHas('success');
        $this->assertEquals('checked_out', $reservation->fresh()->status);
        $this->assertFalse($this->room->fresh()->is_occupied);
        $this->assertEquals('dirty', $this->room->fresh()->status);

        // 5. Housekeeping task was automatically provisioned
        $task = HousekeepingTask::where('room_id', $this->room->id)->first();
        $this->assertNotNull($task);

        // 6. Housekeeper logs in and opens housekeeping board
        $hkRes = $this->actingAs($this->housekeeper)->get('/housekeeping');
        $hkRes->assertOk();
        $hkRes->assertSee('501');

        // 7. Housekeeper starts cleaning
        $inProgRes = $this->actingAs($this->housekeeper)->post("/housekeeping/tasks/{$task->id}/status", [
            'status' => 'in_progress',
        ]);
        $inProgRes->assertSessionHas('success');
        $this->assertEquals('in_progress', $task->fresh()->status);

        // 8. Housekeeper finishes cleaning
        $compRes = $this->actingAs($this->housekeeper)->post("/housekeeping/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);
        $compRes->assertSessionHas('success');
        $this->assertEquals('completed', $task->fresh()->status);
        $this->assertEquals('clean', $this->room->fresh()->status);

        // 9. Verify housekeeper is redirected with error when attempting staff governance
        $forbiddenGov = $this->actingAs($this->housekeeper)->get('/staff-governance');
        $forbiddenGov->assertRedirect(route('dashboard'));
        $forbiddenGov->assertSessionHas('error');

        // 10. Verify housekeeper is 403 Forbidden from accessing security center
        $forbiddenAdmin = $this->actingAs($this->housekeeper)->get('/security');
        $forbiddenAdmin->assertForbidden();
    }
}

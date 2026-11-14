<?php

namespace Tests\Feature;

use App\Models\BulkChangeBatch;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\PaymentReconciliation;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalToProductionParityTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotelTokyo;
    protected Hotel $hotelKyoto;
    protected User $superAdmin;
    protected User $managerTokyo;
    protected RoomType $deluxeType;
    protected Room $room101;
    protected Room $room102;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotelTokyo = Hotel::create([
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'code' => 'TYO-GNZ',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'is_active' => true,
        ]);

        $this->hotelKyoto = Hotel::create([
            'name' => 'ClearStay Kyoto Gion Heritage',
            'slug' => 'clearstay-kyoto-gion-heritage',
            'code' => 'KYO-GIO',
            'address' => '570 Gionmachi Minamigawa, Higashiyama-ku',
            'city' => 'Kyoto',
            'country' => 'JP',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'hotel_id' => null,
            'is_active' => true,
        ]);

        $this->managerTokyo = User::create([
            'name' => 'Tokyo Manager',
            'email' => 'manager.tokyo@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotelTokyo->id,
            'is_active' => true,
        ]);

        $this->deluxeType = RoomType::create([
            'hotel_id' => $this->hotelTokyo->id,
            'name' => 'Deluxe King',
            'code' => 'DLX-KNG',
            'base_price' => 25000,
            'max_occupancy' => 2,
        ]);

        $this->room101 = Room::create([
            'hotel_id' => $this->hotelTokyo->id,
            'room_type_id' => $this->deluxeType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'clean',
            'is_active' => true,
        ]);

        $this->room102 = Room::create([
            'hotel_id' => $this->hotelTokyo->id,
            'room_type_id' => $this->deluxeType->id,
            'room_number' => '102',
            'floor' => 1,
            'status' => 'clean',
            'is_active' => true,
        ]);

        \App\Models\HotelPricingSetting::create([
            'hotel_id' => $this->hotelTokyo->id,
            'stacking_policy' => 'additive',
            'weekend_days' => [],
            'weekend_increase_percentage' => 0.00,
            'rounding_policy' => 'whole_yen',
            'currency' => 'JPY',
            'tax_percentage' => 10.00,
            'tax_included' => true,
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
     * Workflow A: New Staff Registration & Lifecycle Workflow
     */
    public function test_workflow_a_staff_registration_and_authentication_lifecycle(): void
    {
        // 1. Unauthenticated user visits registration page
        $res = $this->get('/register');
        $res->assertOk();
        $res->assertSee('Staff Registration');
        $res->assertSee('ClearStay Premier Tokyo Ginza');

        // 2. Register a new frontdesk staff member
        $newStaffData = array_merge([
            'name' => 'Emi Watanabe',
            'email' => 'emi.watanabe@clearstay.jp',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'hotel_id' => $this->hotelTokyo->id,
            'role' => 'frontdesk',
            'department' => 'Front Office',
            'locale' => 'ja',
            'terms' => '1',
        ], $this->getValidChallengePayload(10));

        $postRes = $this->post('/register', $newStaffData);
        // Public self-registration auto-logs in the new user and redirects to dashboard.
        $postRes->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Sign out before we test the explicit login flow below
        $this->post('/logout');

        // 3. Verify user is saved in DB with hashed password and active state
        $newUser = User::where('email', 'emi.watanabe@clearstay.jp')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('SecurePass2026!', $newUser->password));
        $this->assertEquals('frontdesk', $newUser->role);
        $this->assertEquals($this->hotelTokyo->id, $newUser->hotel_id);
        $this->assertTrue($newUser->is_active);

        // 4. Authenticate using the new account
        $loginRes = $this->post('/login', array_merge([
            'email' => 'emi.watanabe@clearstay.jp',
            'password' => 'SecurePass2026!',
        ], $this->getValidChallengePayload(10)));
        $loginRes->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($newUser);

        // 5. Verify user has session populated with correct hotel context
        $dashboardRes = $this->get('/dashboard');
        $dashboardRes->assertOk();
        $dashboardRes->assertSee('Emi Watanabe');

        // 6. Sign out
        $logoutRes = $this->post('/logout');
        $logoutRes->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Workflow B: Full Reservation Lifecycle
     */
    public function test_workflow_b_full_reservation_lifecycle(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        $today = Carbon::today()->toDateString();
        $futureDate = Carbon::today()->addDays(3)->toDateString();

        // 1. Create a reservation
        $res = $this->post('/reservations', [
            'guest_mode' => 'new',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+81-90-5555-4444',
            'room_id' => $this->room101->id,
            'check_in_date' => $today,
            'check_out_date' => $futureDate,
            'adults' => 2,
            'children' => 0,
        ]);
        $res->assertRedirect(route('reservations.index'));

        // 2. Retrieve reservation from DB
        $reservation = Reservation::where('room_id', $this->room101->id)->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('confirmed', $reservation->status);
        $this->assertEquals(75000, $reservation->total_price); // 3 nights * 25000

        // 3. Inspect JSON detail modal API
        $showRes = $this->getJson("/reservations/{$reservation->id}");
        $showRes->assertOk();
        $showRes->assertJsonPath('reservation.guest_name', 'John Doe');

        // 4. Change dates using Row-Locked Database Transaction
        $newCheckOut = Carbon::today()->addDays(4)->toDateString();
        $changeRes = $this->postJson("/reservations/{$reservation->id}/change-dates", [
            'check_in_date' => $today,
            'check_out_date' => $newCheckOut,
            'reason' => 'Guest requested extended stay.',
        ]);
        $changeRes->assertOk();
        $reservation->refresh();
        $this->assertEquals($newCheckOut, $reservation->check_out_date->toDateString());
        $this->assertEquals(100000, $reservation->total_price); // 4 nights * 25000

        // 5. Check In
        $checkinRes = $this->post("/reservations/{$reservation->id}/check-in");
        $checkinRes->assertSessionHas('success');
        $reservation->refresh();
        $this->assertEquals('checked_in', $reservation->status);

        // 6. Check Out
        $checkoutRes = $this->post("/reservations/{$reservation->id}/check-out");
        $checkoutRes->assertSessionHas('success');
        $reservation->refresh();
        $this->assertEquals('checked_out', $reservation->status);
    }

    /**
     * Workflow C: Room Management & Room Board
     */
    public function test_workflow_c_room_management_and_board_verification(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        // 1. Visit Room Board
        $boardRes = $this->get('/board');
        $boardRes->assertOk();
        $boardRes->assertSee('101');
        $boardRes->assertSee('102');

        // 2. Change Room 101 status to maintenance
        $statusRes = $this->post("/board/room/{$this->room101->id}/status", [
            'status' => 'maintenance',
        ]);
        $statusRes->assertOk();
        $this->room101->refresh();
        $this->assertEquals('maintenance', $this->room101->status);

        // 3. Verify status endpoint returns real counts
        $jsonStatus = $this->getJson('/board/status');
        $jsonStatus->assertOk();
        $jsonStatus->assertJsonStructure(['success', 'counts' => ['total', 'occupied', 'available', 'dirty', 'maintenance'], 'rooms']);
    }

    /**
     * Workflow D: Housekeeping Task Management
     */
    public function test_workflow_d_housekeeping_task_lifecycle(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        // 1. Create a cleaning task
        $taskRes = $this->post('/housekeeping/tasks', [
            'room_id' => $this->room102->id,
            'task_type' => 'deep_clean',
            'priority' => 'high',
            'notes' => 'Thorough disinfection before VIP arrival',
        ]);
        $taskRes->assertRedirect();

        $task = HousekeepingTask::where('room_id', $this->room102->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('high', $task->priority);

        // 2. Update task to in_progress
        $this->post("/housekeeping/tasks/{$task->id}/status", ['status' => 'in_progress']);
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);

        // 3. Complete task -> room status automatically updates to clean
        $this->post("/housekeeping/tasks/{$task->id}/status", ['status' => 'completed']);
        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->room102->refresh();
        $this->assertEquals('clean', $this->room102->status);
    }

    /**
     * Workflow E: Bulk Editor & Inventory Matrix
     */
    public function test_workflow_e_bulk_editor_commit_and_rollback(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        $startDate = Carbon::today()->addDays(10)->toDateString();
        $endDate = Carbon::today()->addDays(12)->toDateString();

        // 0. Ensure upcoming inventory is initialized
        $this->get('/bulk-editor');

        // 1. Commit bulk adjustment (set price 30000, stop_sell = 1)
        $commitRes = $this->post('/bulk-editor/commit', [
            'room_type_id' => $this->deluxeType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'base_rate' => 30000,
            'stop_sell' => '1',
            'reason' => 'Holiday surcharge and stop-sell',
        ]);
        $commitRes->assertRedirect();

        // Verify batch created
        $batch = BulkChangeBatch::where('hotel_id', $this->hotelTokyo->id)->latest()->first();
        $this->assertNotNull($batch);
        $this->assertFalse((bool) $batch->is_rolled_back);

        // 2. Perform 1-Click Rollback
        $rollbackRes = $this->post("/bulk-editor/rollback/{$batch->id}");
        $rollbackRes->assertRedirect();
        $batch->refresh();
        $this->assertTrue((bool) $batch->is_rolled_back);
    }

    /**
     * Workflow F: Revenue, Rate Plans & Japanese Calendar Parity
     */
    public function test_workflow_f_rate_studio_and_parity_management(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        // 1. Visit Rate Studio
        $res = $this->get('/rate-studio');
        $res->assertOk();
        $res->assertSee('Rate Studio');

        // 2. Create a rate plan
        $planRes = $this->post('/rate-studio/plans', [
            'name' => 'Autumn Foliage Flex Plan',
            'code' => 'AUTUMN-FLEX-01',
            'room_type_id' => $this->deluxeType->id,
            'base_price' => 28000,
            'pricing_model' => 'flat',
            'min_stay_nights' => 2,
            'cancellation_policy' => 'moderate',
            'meal_plan' => 'breakfast_included',
            'is_active' => '1',
        ]);
        $planRes->assertRedirect();

        $plan = RatePlan::where('hotel_id', $this->hotelTokyo->id)->where('name', 'Autumn Foliage Flex Plan')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(28000, $plan->base_price);
    }

    /**
     * Workflow G: Payment Reconciliation & Audit Resolution
     */
    public function test_workflow_g_payment_reconciliation_and_resolution(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        $channel = Channel::firstOrCreate(['code' => 'BOOKING'], ['name' => 'Booking.com', 'is_active' => true]);

        // 1. Create a discrepancy record
        $rec = PaymentReconciliation::create([
            'hotel_id' => $this->hotelTokyo->id,
            'reservation_reference' => 'RES-REC-001',
            'payment_reference' => 'PAY-001',
            'transaction_date' => Carbon::today(),
            'channel_id' => $channel->id,
            'expected_amount' => 50000,
            'received_amount' => 45000,
            'discrepancy_amount' => 5000,
            'reconciliation_status' => 'unmatched',
            'settlement_status' => 'settled',
            'exception_reason' => 'Commission deduction variance',
        ]);

        // 2. Resolve discrepancy
        $resolveRes = $this->post("/payment-reconciliation/{$rec->id}/resolve", [
            'resolution_notes' => 'Verified and accepted OTA merchant fee structure.',
        ]);
        $resolveRes->assertRedirect();
        $rec->refresh();
        $this->assertEquals('resolved', $rec->reconciliation_status);
    }

    /**
     * Workflow H: Multi-Tenant Strict Isolation
     */
    public function test_workflow_h_multi_tenant_isolation_between_tokyo_and_kyoto(): void
    {
        // 1. Manager of Tokyo visits reservations
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        $guest = Guest::create([
            'hotel_id' => $this->hotelTokyo->id,
            'first_name' => 'Ken',
            'last_name' => 'Suzuki',
            'email' => 'ken.suzuki@example.com',
        ]);

        $resTokyo = Reservation::create([
            'hotel_id' => $this->hotelTokyo->id,
            'guest_id' => $guest->id,
            'confirmation_code' => 'RES-TOKYO-001',
            'room_id' => $this->room101->id,
            'room_type_id' => $this->deluxeType->id,
            'check_in_date' => Carbon::today(),
            'check_out_date' => Carbon::today()->addDays(2),
            'total_price' => 50000,
            'status' => 'confirmed',
        ]);

        $tokyoViewRes = $this->get('/reservations');
        $tokyoViewRes->assertOk();
        $tokyoViewRes->assertSee('RES-TOKYO-001');

        // 2. Create Kyoto Manager
        $managerKyoto = User::create([
            'name' => 'Kyoto Manager',
            'email' => 'manager.kyoto@clearstay.jp',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotelKyoto->id,
            'is_active' => true,
        ]);

        // 3. Manager of Kyoto logs in -> MUST NOT see Tokyo's reservation
        $this->actingAs($managerKyoto);
        session(['current_hotel_id' => $this->hotelKyoto->id]);

        $kyotoViewRes = $this->get('/reservations');
        $kyotoViewRes->assertOk();
        $kyotoViewRes->assertDontSee('RES-TOKYO-001');
    }

    /**
     * Workflow I: Reports & CSV Export Generation
     */
    public function test_workflow_i_reports_and_csv_generation(): void
    {
        $this->actingAs($this->managerTokyo);
        session(['current_hotel_id' => $this->hotelTokyo->id]);

        // 1. Visit Reports Hub
        $reportHub = $this->get('/reports');
        $reportHub->assertOk();
        $reportHub->assertSee('Reports');

        // 2. Export Inventory CSV
        $invCsv = $this->get('/reports/export/inventory');
        $invCsv->assertOk();
        $this->assertStringContainsString('text/csv', $invCsv->headers->get('content-type'));

        // 3. Export Audit Logs CSV
        $auditCsv = $this->get('/reports/export/audit-logs');
        $auditCsv->assertOk();
        $this->assertStringContainsString('text/csv', $auditCsv->headers->get('content-type'));
    }
}

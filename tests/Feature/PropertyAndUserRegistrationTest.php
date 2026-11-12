<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PropertyAndUserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $activeHotelA;
    private Hotel $activeHotelB;
    private Hotel $inactiveHotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeHotelA = Hotel::create([
            'name' => 'ClearStay Ginza Luxury Suites',
            'slug' => 'clearstay-ginza',
            'code' => 'CS-GINZA',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'phone' => '+81-3-5555-0101',
            'email' => 'ginza@clearstay.jp',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'total_rooms' => 100,
            'is_active' => true,
        ]);

        $this->activeHotelB = Hotel::create([
            'name' => 'ClearStay Kyoto Gion Heritage',
            'slug' => 'clearstay-kyoto-gion',
            'code' => 'CS-GION',
            'address' => 'Gion-machi, Higashiyama-ku',
            'city' => 'Kyoto',
            'country' => 'Japan',
            'phone' => '+81-75-555-0202',
            'email' => 'gion@clearstay.jp',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'total_rooms' => 45,
            'is_active' => true,
        ]);

        $this->inactiveHotel = Hotel::create([
            'name' => 'ClearStay Under Renovation',
            'slug' => 'clearstay-renovating',
            'code' => 'CS-RENOV',
            'address' => '1-2 Sakae, Naka-ku',
            'city' => 'Nagoya',
            'country' => 'Japan',
            'phone' => '+81-52-555-0303',
            'email' => 'nagoya@clearstay.jp',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'total_rooms' => 30,
            'is_active' => false,
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
     * Requirement 1 & 2: Active properties appear in dropdown; inactive are excluded.
     */
    public function test_registration_page_shows_active_properties_and_excludes_inactive(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('ClearStay Ginza Luxury Suites');
        $response->assertSee('CS-GINZA');
        $response->assertSee('ClearStay Kyoto Gion Heritage');
        $response->assertSee('CS-GION');
        $response->assertDontSee('ClearStay Under Renovation');
    }

    /**
     * Requirement 2: Fallback when no properties exist.
     */
    public function test_registration_page_shows_fallback_when_no_properties_exist(): void
    {
        Hotel::query()->update(['is_active' => false]);

        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('No hotels or properties are available. Please ask your administrator to initialize or create a property before registering staff.');
        $response->assertSee('disabled', false);
    }

    /**
     * Requirement 3: Registration creates user with hashed password and persists correctly.
     */
    public function test_registration_succeeds_with_valid_data_and_persists_user(): void
    {
        $response = $this->post(route('register.post'), array_merge($this->getValidChallengePayload(12), [
            'name' => 'Taro Yamada',
            'email' => 'taro.yamada@clearstay.jp',
            'role' => 'frontdesk',
            'locale' => 'en',
            'hotel_id' => $this->activeHotelA->id,
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'taro.yamada@clearstay.jp')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Taro Yamada', $user->name);
        $this->assertEquals('frontdesk', $user->role);
        $this->assertEquals($this->activeHotelA->id, $user->hotel_id);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_suspended);
        $this->assertTrue(Hash::check('SecurePass2026!', $user->password));
        $this->assertNotEquals('SecurePass2026!', $user->password); // Plaintext never stored
    }

    /**
     * Requirement 3: Duplicate email rejection with exact message.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing Member',
            'email' => 'existing@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'frontdesk',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $response = $this->post(route('register.post'), array_merge($this->getValidChallengePayload(8), [
            'name' => 'Duplicate Attempt',
            'email' => 'existing@clearstay.jp',
            'role' => 'frontdesk',
            'locale' => 'en',
            'hotel_id' => $this->activeHotelA->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]));

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertContains('This email address is already registered. Please sign in or use another email.', $errors);
    }

    /**
     * Requirement 3: Password confirmation mismatch rejection.
     */
    public function test_registration_fails_with_unmatched_password_confirmation(): void
    {
        $response = $this->post(route('register.post'), array_merge($this->getValidChallengePayload(4), [
            'name' => 'Mismatch Test',
            'email' => 'mismatch@clearstay.jp',
            'role' => 'frontdesk',
            'locale' => 'en',
            'hotel_id' => $this->activeHotelA->id,
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]));

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Requirement 3: Invalid or inactive property rejection.
     */
    public function test_registration_fails_with_invalid_or_inactive_property(): void
    {
        // Test non-existent hotel ID
        $response = $this->post(route('register.post'), array_merge($this->getValidChallengePayload(2), [
            'name' => 'Invalid Hotel',
            'email' => 'invalid.hotel@clearstay.jp',
            'role' => 'frontdesk',
            'locale' => 'en',
            'hotel_id' => 99999,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]));

        $response->assertSessionHasErrors(['hotel_id']);

        // Test inactive hotel ID
        $responseInactive = $this->post(route('register.post'), array_merge($this->getValidChallengePayload(2), [
            'name' => 'Inactive Hotel',
            'email' => 'inactive.hotel@clearstay.jp',
            'role' => 'frontdesk',
            'locale' => 'en',
            'hotel_id' => $this->inactiveHotel->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]));

        $responseInactive->assertSessionHasErrors(['hotel_id']);
    }

    /**
     * Requirement 3: Newly registered user can log in with credentials.
     */
    public function test_newly_registered_user_can_login(): void
    {
        $user = User::create([
            'name' => 'Login Tester',
            'email' => 'login.tester@clearstay.jp',
            'password' => Hash::make('LoginPassword123!'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'frontdesk',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'login.tester@clearstay.jp',
            'password' => 'LoginPassword123!',
            '_cs_token' => 'cs_valid_token',
            '_cs_rendered_at' => (string) (time() - 3),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Requirement 4: Authenticated admin can create staff without being logged out.
     */
    public function test_authenticated_admin_can_register_staff_and_remains_logged_in(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'email' => 'boss@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('register.post'), [
            'name' => 'Created Staff',
            'email' => 'created.staff@clearstay.jp',
            'role' => 'housekeeping',
            'locale' => 'ja',
            'hotel_id' => $this->activeHotelB->id,
            'password' => 'NewStaffPass123!',
            'password_confirmation' => 'NewStaffPass123!',
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertAuthenticatedAs($admin); // Admin is STILL logged in

        $created = User::where('email', 'created.staff@clearstay.jp')->first();
        $this->assertNotNull($created);
        $this->assertEquals('housekeeping', $created->role);
        $this->assertEquals($this->activeHotelB->id, $created->hotel_id);
    }

    /**
     * Requirement 5: Multi-tenancy check - Property Manager cannot assign outside their hotel.
     */
    public function test_property_manager_cannot_register_staff_for_another_property(): void
    {
        $manager = User::create([
            'name' => 'Ginza Manager',
            'email' => 'manager.ginza@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        // Attempt to register staff for Hotel B (Kyoto)
        $response = $this->post(route('register.post'), [
            'name' => 'Illegal Staff',
            'email' => 'illegal.staff@clearstay.jp',
            'role' => 'frontdesk',
            'locale' => 'en',
            'hotel_id' => $this->activeHotelB->id, // Another hotel!
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'illegal.staff@clearstay.jp']);
    }

    /**
     * Requirement 4: Staff Governance lists staff, searches by name/email, and filters by role.
     */
    public function test_staff_governance_search_and_filters(): void
    {
        $admin = User::create([
            'name' => 'HQ Auditor',
            'email' => 'hq.auditor@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $user1 = User::create([
            'name' => 'Alice Takahashi',
            'email' => 'alice@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'frontdesk',
            'is_active' => true,
        ]);

        $user2 = User::create([
            'name' => 'Bob Matsumoto',
            'email' => 'bob@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelB->id,
            'role' => 'housekeeping',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        // Search by name
        $searchResponse = $this->get(route('staff.index', ['search' => 'Alice']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Alice Takahashi');
        $searchResponse->assertDontSee('Bob Matsumoto');

        // Filter by role
        $roleResponse = $this->get(route('staff.index', ['role' => 'housekeeping']));
        $roleResponse->assertStatus(200);
        $roleResponse->assertSee('Bob Matsumoto');
        $roleResponse->assertDontSee('Alice Takahashi');

        // Filter by hotel
        $hotelResponse = $this->get(route('staff.index', ['hotel_id' => $this->activeHotelB->id]));
        $hotelResponse->assertStatus(200);
        $hotelResponse->assertSee('Bob Matsumoto');
        $hotelResponse->assertDontSee('Alice Takahashi');
    }

    /**
     * Requirement 4: Admin can update user details and reassign property.
     */
    public function test_admin_can_update_user_details_and_reassign_property(): void
    {
        $admin = User::create([
            'name' => 'Executive Director',
            'email' => 'exec@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Transfer Candidate',
            'email' => 'candidate@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'frontdesk',
            'department' => 'Front Office',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('staff.update_details', $staff), [
            'name' => 'Promoted Lead',
            'email' => 'promoted.lead@clearstay.jp',
            'role' => 'manager',
            'department' => 'Operations Leadership',
            'hotel_id' => $this->activeHotelB->id, // Reassigned to Kyoto!
        ]);

        $response->assertRedirect();
        $staff->refresh();

        $this->assertEquals('Promoted Lead', $staff->name);
        $this->assertEquals('promoted.lead@clearstay.jp', $staff->email);
        $this->assertEquals('manager', $staff->role);
        $this->assertEquals($this->activeHotelB->id, $staff->hotel_id);
    }

    /**
     * Requirement 4: Admin can reset password securely and user can log in with new password.
     */
    public function test_admin_can_reset_password_and_user_can_login_with_new_password(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@clearstay.jp',
            'password' => Hash::make('password123'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Password Reset User',
            'email' => 'reset.me@clearstay.jp',
            'password' => Hash::make('OldPassword123!'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'frontdesk',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('staff.reset_password', $staff), [
            'password' => 'BrandNewPassword2026!',
            'password_confirmation' => 'BrandNewPassword2026!',
        ]);

        $response->assertRedirect();
        $staff->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword2026!', $staff->password));

        // Verify newly reset password allows staff login
        Auth::logout();
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'reset.me@clearstay.jp',
            'password' => 'BrandNewPassword2026!',
            '_cs_token' => 'cs_valid_token',
            '_cs_rendered_at' => (string) (time() - 3),
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    /**
     * Requirement 4: Deactivated or suspended users cannot log in.
     */
    public function test_deactivated_or_suspended_users_cannot_login(): void
    {
        $suspendedUser = User::create([
            'name' => 'Suspended Staff',
            'email' => 'suspended@clearstay.jp',
            'password' => Hash::make('Password123!'),
            'hotel_id' => $this->activeHotelA->id,
            'role' => 'frontdesk',
            'is_active' => false,
            'is_suspended' => true,
            'suspension_reason' => 'Security investigation pending',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'suspended@clearstay.jp',
            'password' => 'Password123!',
            '_cs_token' => 'cs_valid_token',
            '_cs_rendered_at' => (string) (time() - 3),
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\Hotel;
use App\Models\SecurityIncident;
use App\Models\User;
use App\Services\BotDefenseService;
use App\Services\TwoFactorAuthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityAndBotDefenseTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;

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

        $this->manager = User::create([
            'name' => 'SecOps Manager',
            'email' => 'secops@clearstay.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'locale' => 'en',
            'is_active' => true,
        ]);
    }

    /**
     * Helper to forge an HMAC time-lock token for tests.
     */
    protected function makeTimeToken(int $secondsAgo = 3, string $formId = 'contact'): string
    {
        $timestamp = time() - $secondsAgo;
        $salt = Str::random(12);
        $payload = "{$timestamp}:{$salt}:{$formId}";
        $sig = hash_hmac('sha256', $payload, config('app.key'));

        return base64_encode("{$payload}:{$sig}");
    }

    public function test_applies_owasp_security_headers_to_all_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_rejects_bot_form_submissions_with_filled_honeypot(): void
    {
        $response = $this->post(route('public.contact.post'), [
            'name' => 'Bad Spambot',
            'email' => 'spambot@exploit.net',
            'hotel_id' => $this->hotel->id,
            'message' => 'Injecting spam links',
            '_cs_hp_website' => 'https://malicious-spam.ru', // Honeypot filled!
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('security_incidents', [
            'event_type' => 'bot.honeypot_triggered',
            'severity' => 'high',
        ]);
    }

    public function test_rejects_bot_rapid_submissions_faster_than_human_limit(): void
    {
        $rapidToken = $this->makeTimeToken(0, 'contact'); // Submitted in 0 seconds!

        $response = $this->post(route('public.contact.post'), [
            'name' => 'Script Submitter',
            'email' => 'script@botnet.test',
            'hotel_id' => $this->hotel->id,
            'message' => 'Automated post test',
            '_cs_timelock' => $rapidToken,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('security_incidents', [
            'event_type' => 'bot.rapid_submission',
            'severity' => 'medium',
        ]);
    }

    public function test_allows_valid_human_contact_submissions_with_authentic_timelock(): void
    {
        $validToken = $this->makeTimeToken(3, 'contact'); // 3 seconds ago (normal human)

        $response = $this->post(route('public.contact.post'), [
            'name' => 'Alice Guest',
            'email' => 'alice@traveler.test',
            'hotel_id' => $this->hotel->id,
            'message' => 'Hello, I would like to inquire about group bookings.',
            '_cs_timelock' => $validToken,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_locks_account_after_consecutive_failed_logins(): void
    {
        // Attempt 5 incorrect logins
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login.post'), [
                'email' => 'secops@clearstay.test',
                'password' => 'WrongPassword!',
                '_cs_timelock' => $this->makeTimeToken(3, 'login'),
            ]);
        }

        $user = $this->manager->fresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->lockout_until);
        $this->assertTrue($user->isLockedOut());

        // 6th attempt should be blocked by lockout policy
        $blockedAttempt = $this->post(route('login.post'), [
            'email' => 'secops@clearstay.test',
            'password' => 'Secret123!', // Correct password, but account is locked
            '_cs_timelock' => $this->makeTimeToken(3, 'login'),
        ]);

        $blockedAttempt->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_blocks_blacklisted_ips_via_firewall_middleware(): void
    {
        BlockedIp::block('198.51.100.44', 'Automated directory brute-forcer', 'high', 60);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.44'])
            ->get(route('public.home'));

        $response->assertStatus(403);
        $response->assertSee('ClearStay Web Application Firewall (WAF)');
        $response->assertSee('403 Forbidden: Request Blocked');
    }

    public function test_handles_two_factor_authentication_workflow(): void
    {
        $twoFactorService = app(TwoFactorAuthService::class);
        $pin = $twoFactorService->generateSecurityPin();
        $recoveryCodes = $twoFactorService->enable($this->manager, $pin);

        $this->assertTrue($this->manager->fresh()->two_factor_enabled);
        $this->assertNotEmpty($pin);
        $this->assertCount(8, $recoveryCodes);

        // Sign in with 2FA enabled
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'secops@clearstay.test',
            'password' => 'Secret123!',
            '_cs_timelock' => $this->makeTimeToken(3, 'login'),
        ]);

        $loginResponse->assertRedirect(route('login.2fa'));
        $this->assertGuest(); // Not authenticated yet, in 2fa_pending state

        // Verify with one of the single-use recovery codes
        $firstRecoveryCode = $recoveryCodes[0];
        $challengeResponse = $this->post(route('login.2fa.post'), [
            'code' => $firstRecoveryCode,
            '_cs_timelock' => $this->makeTimeToken(3, '2fa'),
        ]);

        $challengeResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->manager);

        // Verify that the used recovery code has been removed/consumed
        $updatedUser = $this->manager->fresh();
        $this->assertNotContains($firstRecoveryCode, $updatedUser->two_factor_recovery_codes);
        $this->assertCount(7, $updatedUser->two_factor_recovery_codes);
    }

    public function test_allows_managers_to_manage_ip_blocks_in_security_center(): void
    {
        $this->actingAs($this->manager);

        // Manually block an IP
        $blockResponse = $this->post(route('security.block_ip'), [
            'ip_address' => '203.0.113.88',
            'reason' => 'Suspicious scanning activity',
            'threat_level' => 'high',
            'duration' => 60,
        ]);

        $blockResponse->assertRedirect();
        $this->assertDatabaseHas('blocked_ips', [
            'ip_address' => '203.0.113.88',
            'reason' => 'Suspicious scanning activity',
        ]);

        $blockedRecord = BlockedIp::where('ip_address', '203.0.113.88')->first();

        // Unblock the IP
        $unblockResponse = $this->delete(route('security.unblock_ip', $blockedRecord->id));
        $unblockResponse->assertRedirect();

        $this->assertDatabaseMissing('blocked_ips', [
            'id' => $blockedRecord->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\PrivacyAccessLog;
use App\Models\User;
use App\Services\Privacy\PrivacyProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyAndDataProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected Guest $guest;
    protected PrivacyProtectionService $privacyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privacyService = app(PrivacyProtectionService::class);

        $this->hotel = Hotel::firstOrCreate(['code' => 'TYO-GNZ'], [
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'currency' => 'JPY',
            'is_active' => true,
        ]);

        $this->manager = User::firstOrCreate(['email' => 'privacy.manager@clearstay.jp'], [
            'name' => 'Privacy Manager',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->guest = Guest::firstOrCreate(['email' => 'taro.yamada@example.com'], [
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'phone' => '+81-90-9999-8888',
        ]);
    }

    public function test_privacy_service_masks_phone_email_and_tokens_by_default(): void
    {
        $maskedPhone = $this->privacyService->maskPhone('+81-90-9999-8888');
        $this->assertStringContainsString('****', $maskedPhone);
        $this->assertStringEndsWith('8888', $maskedPhone);

        $maskedEmail = $this->privacyService->maskEmail('taro.yamada@example.com');
        $this->assertStringContainsString('***', $maskedEmail);
        $this->assertStringEndsWith('@example.com', $maskedEmail);

        $maskedToken = $this->privacyService->maskPaymentToken('4111111111114242');
        $this->assertEquals('************4242', $maskedToken);
    }

    public function test_privacy_centre_hub_renders(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('privacy_centre.index'));

        $response->assertStatus(200);
        $response->assertSee('プライバシー保護・個人情報統制センター');
        $response->assertSee('ClearStay データ分類基準');
        $response->assertSee('インドDPDP法2023');
    }

    public function test_unmask_endpoint_requires_minimum_justification(): void
    {
        // Too short justification (<5 chars) rejected
        $response = $this->actingAs($this->manager)
            ->postJson(route('privacy_centre.unmask', $this->guest->id), [
                'justification' => 'bad',
            ]);

        $response->assertStatus(422);
    }

    public function test_valid_unmask_logs_audit_record_and_returns_pii(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson(route('privacy_centre.unmask', $this->guest->id), [
                'justification' => 'Japanese Ryokan check-in identity verification at front desk.',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('unmasked_phone', '+81-90-9999-8888');
        $response->assertJsonPath('unmasked_email', 'taro.yamada@example.com');

        $this->assertDatabaseHas('privacy_access_logs', [
            'guest_id' => $this->guest->id,
            'user_id' => $this->manager->id,
            'action' => 'unmask',
        ]);
    }

    public function test_audit_report_can_be_exported_as_csv(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('privacy_centre.export_audit'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityAndThemesTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::firstOrCreate(['code' => 'TYO-GNZ'], [
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'currency' => 'JPY',
            'is_active' => true,
        ]);

        $this->user = User::firstOrCreate(['email' => 'a11y.user@clearstay.jp'], [
            'name' => 'Accessibility Tester',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }

    public function test_accessibility_hub_renders_wcag_checklist(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('accessibility.index'));

        $response->assertStatus(200);
        $response->assertSee('ユニバーサル・アクセシビリティセンター');
        $response->assertSee('WCAG 2.2 レベルAA 適合状況監査');
        $response->assertSee('High Contrast Light');
    }

    public function test_user_can_update_accessibility_preferences(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('accessibility.update'), [
                'contrast_theme' => 'high_contrast_light',
                'font_scale' => 150,
                'reduced_motion' => 1,
            ]);

        $response->assertRedirect(route('accessibility.index'));
        $response->assertSessionHas('clearstay_contrast_theme', 'high_contrast_light');
        $response->assertSessionHas('clearstay_font_scale', 150);
        $response->assertSessionHas('clearstay_reduced_motion', true);

        $this->user->refresh();
        $this->assertEquals('high_contrast_light', $this->user->contrast_theme);
        $this->assertEquals(150, $this->user->font_scale);
        $this->assertTrue((bool) $this->user->reduced_motion);
    }

    public function test_layout_renders_skip_link_and_theme_classes(): void
    {
        session([
            'clearstay_contrast_theme' => 'high_contrast_dark',
            'clearstay_font_scale' => 150,
            'clearstay_reduced_motion' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('overview.index'));

        $response->assertStatus(200);
        $response->assertSee('class="skip-link"', false);
        $response->assertSee('theme-high_contrast_dark', false);
        $response->assertSee('font-scale-150', false);
        $response->assertSee('reduced-motion', false);
        $response->assertSee('id="mainContent"', false);
    }
}

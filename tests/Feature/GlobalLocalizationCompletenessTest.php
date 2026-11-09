<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalLocalizationCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Hotel $hotel;

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
            'total_rooms' => 50,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.test',
            'password' => bcrypt('Password123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'locale' => 'en',
            'is_active' => true,
        ]);
    }

    public function test_interview_demo_hub_displays_pure_english_when_english_selected()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'en'])
            ->get('/demo-centre');

        $response->assertStatus(200);
        $response->assertSee('Interview Demonstration Hub');
        $response->assertSee('Demonstrating Full-Stack Engineering Capabilities');
        $response->assertSee('TEMAIRAZU PORTFOLIO DEMONSTRATION');
        $response->assertSee('01 — Multi-Channel Inventory Sync');
        $response->assertSee('02 — Concurrency-Safe Date Change');
        $response->assertSee('04 — Two-Person Approval for High-Risk Elevation');
        $response->assertSee('Run Scenario');
        $response->assertSee('Open Experience Lab');
        $response->assertSee('Easy Mode');

        // Verify absence of hardcoded Japanese strings on English view
        $response->assertDontSee('このシナリオを実演実行する');
        $response->assertDontSee('体験ラボを開く');
    }

    public function test_interview_demo_hub_displays_pure_japanese_when_japanese_selected()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ja'])
            ->get('/demo-centre');

        $response->assertStatus(200);
        $response->assertSee('採用面接実演センター');
        $response->assertSee('01 — マルチチャネル在庫一斉配信');
        $response->assertSee('02 — 排他行ロックによる重複予約防止');
        $response->assertSee('シナリオを実行');
        $response->assertSee('体験ラボを開く');
        $response->assertSee('かんたん操作');
    }

    public function test_locale_switching_endpoint_updates_session_and_persists()
    {
        $response = $this->actingAs($this->user)->get('/lang/ja');
        $response->assertRedirect();
        $this->assertEquals('ja', session('locale'));
        $this->assertEquals('ja', $this->user->fresh()->locale);

        $responseEn = $this->actingAs($this->user)->get('/lang/en');
        $responseEn->assertRedirect();
        $this->assertEquals('en', session('locale'));
        $this->assertEquals('en', $this->user->fresh()->locale);
    }
}

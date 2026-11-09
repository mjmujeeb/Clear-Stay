<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewDemoHubTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;

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

        $this->manager = User::firstOrCreate(['email' => 'interview.manager@clearstay.jp'], [
            'name' => 'Demo Manager',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }

    public function test_interview_demo_hub_renders_all_10_scenarios(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('interview_demo.index'));

        $response->assertStatus(200);
        $response->assertSee('採用面接実演センター');
        $response->assertSee('01 — Multi-Channel Inventory Sync');
        $response->assertSee('02 — Concurrency-Safe Date Change');
        $response->assertSee('03 — Bulk Change Studio & 1-Click Rollback');
        $response->assertSee('04 — Two-Person Approval for High-Risk Elevation');
        $response->assertSee('05 — Blocked Privilege Escalation Attempt');
        $response->assertSee('06 — Audited Guest PII Unmasking');
        $response->assertSee('07 — Easy Mode Senior-Friendly Check-in');
        $response->assertSee('08 — Responsive Experience Lab Scaler');
        $response->assertSee('09 — Rate Parity & Revenue Analytics');
        $response->assertSee('10 — EventTrace Telemetry & Payload Replay');
    }

    public function test_can_execute_interview_scenarios_via_api(): void
    {
        // Scenario 1: Multi-Channel Inventory Sync
        $sc1 = $this->actingAs($this->manager)
            ->postJson(route('interview_demo.execute', 1));

        $sc1->assertStatus(200);
        $sc1->assertJsonPath('success', true);
        $sc1->assertJsonPath('data.title', 'Multi-Channel Universal Broadcast');

        // Scenario 2: Concurrency Lock
        $sc2 = $this->actingAs($this->manager)
            ->postJson(route('interview_demo.execute', 2));

        $sc2->assertStatus(200);
        $sc2->assertJsonPath('data.isolation_level', 'SERIALIZABLE with SELECT ... FOR UPDATE');

        // Scenario 8: Responsive Validation
        $sc8 = $this->actingAs($this->manager)
            ->postJson(route('interview_demo.execute', 8));

        $sc8->assertStatus(200);
        $sc8->assertJsonPath('data.status', 'ALL_PASSED');
    }
}

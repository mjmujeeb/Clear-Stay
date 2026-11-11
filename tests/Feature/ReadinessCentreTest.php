<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\PartnerReadinessReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReadinessCentreTest extends TestCase
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
            'total_rooms' => 20,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Alex Mercer',
            'email' => 'alex.qa@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Integration QA',
            'locale' => 'en',
            'is_active' => true,
        ]);
    }

    public function test_readiness_page_loads_with_zero_partners(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('readiness.index'));

        $response->assertOk();
        $response->assertViewIs('readiness.index');
        $response->assertSee('No integration partners registered in the system.');
        $response->assertSee('No readiness reports generated yet.');
    }

    public function test_readiness_page_loads_with_partner_having_no_reports(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'Booking Express PMS',
            'company' => 'Booking Express Inc.',
            'category' => 'pms',
            'api_base_url' => 'https://api.bookingexpress.com/v1',
            'api_version' => 'v1.2',
            'auth_type' => 'bearer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('readiness.index'));

        $response->assertOk();
        $response->assertSee('Booking Express PMS');
        $response->assertSee('No readiness assessment has been generated yet');
        $response->assertSee('Generate Readiness Report');
    }

    public function test_readiness_page_displays_latest_report_when_partner_has_reports(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'Expedia QuickConnect',
            'company' => 'Expedia Group',
            'category' => 'ota',
            'api_base_url' => 'https://api.expedia.test/v2',
            'api_version' => 'v2.0',
            'auth_type' => 'bearer',
            'status' => 'active',
        ]);

        $firstReport = PartnerReadinessReport::create([
            'partner_id' => $partner->id,
            'report_code' => 'CR-EXPEDIA-OLD-' . Str::ulid(),
            'score_percentage' => 75,
            'overall_status' => 'passed_with_limitations',
            'summary' => 'Initial preliminary audit',
            'evaluated_by' => $this->manager->id,
            'created_at' => now()->subDay(),
        ]);

        $latestReport = PartnerReadinessReport::create([
            'partner_id' => $partner->id,
            'report_code' => 'CR-EXPEDIA-NEW-' . Str::ulid(),
            'score_percentage' => 95,
            'overall_status' => 'internal_readiness_passed',
            'summary' => 'Latest production-ready certification',
            'evaluated_by' => $this->manager->id,
            'created_at' => now(),
        ]);

        // Test Eloquent relationship directly
        $this->assertEquals($latestReport->id, $partner->fresh()->latestReadinessReport?->id);
        $this->assertEquals(2, $partner->fresh()->readinessReports()->count());

        // Test view rendering
        $response = $this->actingAs($this->manager)
            ->get(route('readiness.index'));

        $response->assertOk();
        $response->assertSee('Expedia QuickConnect');
        $response->assertSee('95%');
        $response->assertSee($latestReport->report_code);
        $response->assertSee($firstReport->report_code);
        $response->assertDontSee('No readiness assessment has been generated yet');
    }

    public function test_generate_action_creates_new_report_and_preserves_history(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'Agoda Partner Bridge',
            'company' => 'Agoda Company Pte. Ltd.',
            'category' => 'ota',
            'api_base_url' => 'https://api.agoda.test/v1',
            'api_version' => 'v1.0',
            'auth_type' => 'bearer',
            'status' => 'active',
        ]);

        // First generation
        $resp1 = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $partner));

        $resp1->assertRedirect();
        $resp1->assertSessionHas('success');

        $report1 = PartnerReadinessReport::where('partner_id', $partner->id)->first();
        $this->assertNotNull($report1);

        // Second generation
        $resp2 = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $partner));

        $resp2->assertRedirect();
        $resp2->assertSessionHas('success');

        $reports = PartnerReadinessReport::where('partner_id', $partner->id)->get();
        $this->assertCount(2, $reports);
        $this->assertNotEquals($reports[0]->report_code, $reports[1]->report_code);
    }

    public function test_sidebar_displays_clean_english_when_locale_is_en(): void
    {
        app()->setLocale('en');

        $response = $this->actingAs($this->manager)
            ->get(route('readiness.index'));

        $response->assertOk();
        $response->assertSee('<span>Readiness</span>', false);
        $response->assertDontSee('接続準備 (Readiness)');
    }

    public function test_sidebar_displays_clean_japanese_when_locale_is_ja(): void
    {
        $this->manager->update(['locale' => 'ja']);
        app()->setLocale('ja');

        $response = $this->actingAs($this->manager)
            ->withSession(['locale' => 'ja'])
            ->get(route('readiness.index'));

        $response->assertOk();
        $response->assertSee('<span>接続準備</span>', false);
        $response->assertDontSee('接続準備 (Readiness)');
    }
}

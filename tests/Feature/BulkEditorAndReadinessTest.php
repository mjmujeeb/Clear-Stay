<?php

namespace Tests\Feature;

use App\Models\BulkChangeBatch;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\PartnerReadinessReport;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Inventory\CentralInventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkEditorAndReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected RoomType $roomType;
    protected Channel $channel;

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

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe King Suite',
            'code' => 'DLX-KNG',
            'base_price' => 25000,
            'max_occupancy' => 2,
            'total_rooms' => 10,
        ]);

        $this->channel = Channel::create([
            'name' => 'Booking.com',
            'slug' => 'booking-com',
            'code' => 'BDC',
            'type' => 'ota',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Operations',
            'locale' => 'ja',
            'is_active' => true,
        ]);
    }

    public function test_bulk_editor_page_loads_and_ensures_inventory_exists(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('bulk_editor.index'));

        $response->assertOk();
        $response->assertViewIs('bulk_editor.index');
        $response->assertSee('Bulk Change Studio');

        // CentralInventory should be initialized for 90 days
        $count = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->count();
        $this->assertGreaterThanOrEqual(30, $count);
    }

    public function test_bulk_editor_preview_returns_json_for_ajax_request(): void
    {
        // First ensure inventory exists
        $today = Carbon::today();
        CentralInventory::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'date' => $today->toDateString(),
            'total_units' => 10,
            'available_units' => 10,
            'reserved_units' => 0,
            'maintenance_units' => 0,
            'base_rate' => 25000,
            'currency' => 'JPY',
            'min_stay' => 1,
            'version' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('bulk_editor.preview'), [
                'start_date' => $today->toDateString(),
                'end_date' => $today->copy()->addDays(5)->toDateString(),
                'room_type_ids' => [$this->roomType->id],
                'price_delta' => 3000,
                'min_stay' => 2,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'preview' => [
                'total_days',
                'total_records_affected',
                'channels',
                'sample_items',
                'changes_summary',
                'warnings',
                'room_types_affected',
            ],
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['preview']['room_types_affected']);
        $this->assertEquals(6, $data['preview']['total_days']);
    }

    public function test_bulk_editor_preview_redirects_for_regular_browser_request(): void
    {
        // Direct GET navigation to /bulk-editor/preview should safely redirect to bulk editor index
        $response = $this->actingAs($this->manager)
            ->get(route('bulk_editor.preview'));

        $response->assertRedirect(route('bulk_editor.index'));
        $response->assertSessionHas('info');
    }

    public function test_bulk_editor_preview_validation_fails_for_invalid_dates(): void
    {
        $today = Carbon::today();

        // 1. end_date before start_date fails with 422 JSON validation error
        $response = $this->actingAs($this->manager)
            ->postJson(route('bulk_editor.preview'), [
                'start_date' => $today->toDateString(),
                'end_date' => $today->copy()->subDays(2)->toDateString(),
                'price_delta' => 1000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);

        // 2. Date range exceeding 90 days returns warnings in preview
        app(CentralInventoryService::class)->ensureUpcomingInventory($this->hotel->id, 95);

        $response90 = $this->actingAs($this->manager)
            ->postJson(route('bulk_editor.preview'), [
                'start_date' => $today->toDateString(),
                'end_date' => $today->copy()->addDays(92)->toDateString(),
                'price_delta' => 1000,
            ]);

        $response90->assertOk();
        $data = $response90->json();
        $this->assertNotEmpty($data['preview']['warnings']);
        $has90DayWarning = collect($data['preview']['warnings'])->contains(fn($w) => str_contains($w, '90 days'));
        $this->assertTrue($has90DayWarning);
    }

    public function test_bulk_editor_commit_applies_changes_and_records_batch(): void
    {
        $today = Carbon::today();
        CentralInventory::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'date' => $today->toDateString(),
            'total_units' => 10,
            'available_units' => 10,
            'reserved_units' => 0,
            'maintenance_units' => 0,
            'base_rate' => 20000,
            'currency' => 'JPY',
            'min_stay' => 1,
            'version' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('bulk_editor.commit'), [
                'start_date' => $today->toDateString(),
                'end_date' => $today->copy()->addDays(2)->toDateString(),
                'room_type_ids' => [$this->roomType->id],
                'price_delta' => 5000,
                'min_stay' => 2,
                'reason' => 'Golden Week high season price revision',
            ]);

        $response->assertRedirect(route('bulk_editor.index'));
        $response->assertSessionHas('success');

        // Verify Batch was recorded with status applied and reason included in summary
        $batch = BulkChangeBatch::where('hotel_id', $this->hotel->id)->first();
        $this->assertNotNull($batch);
        $this->assertEquals('applied', $batch->status);
        $this->assertStringContainsString('Golden Week high season price revision', $batch->summary);

        // Verify CentralInventory base_rate increased
        $updatedInv = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->whereDate('date', $today->toDateString())
            ->first();

        $this->assertEquals(25000, $updatedInv->base_rate);
        $this->assertEquals(2, $updatedInv->min_stay);
    }

    public function test_bulk_editor_seed_demo_inventory_endpoint(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('bulk_editor.seed_demo'));

        $response->assertRedirect(route('bulk_editor.index'));
        $response->assertSessionHas('success');

        $count = CentralInventory::where('hotel_id', $this->hotel->id)->count();
        $this->assertGreaterThanOrEqual(90, $count);
    }

    public function test_partner_readiness_show_renders_safely_with_nested_array_category_breakdown(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'Rakuten Travel Direct API',
            'company' => 'Rakuten Group, Inc.',
            'category' => 'ota',
            'api_base_url' => 'https://api.rakuten.co.jp/travel/v2',
            'status' => 'active',
        ]);

        // Report with nested array categories (the exact structure that caused BUG 2)
        $report = PartnerReadinessReport::create([
            'partner_id' => $partner->id,
            'report_code' => 'REP-RAKUTEN-001',
            'summary' => 'Rakuten Travel comprehensive integration certification report.',
            'overall_status' => 'internal_readiness_passed',
            'score_percentage' => 92,
            'category_breakdown' => [
                'api_contract' => ['score' => 95, 'status' => 'COMPLIANT'],
                'rate_parity' => ['score' => 90, 'status' => 'COMPLIANT'],
                'sync_latency' => ['score' => 92, 'status' => 'COMPLIANT'],
            ],
            'outstanding_issues' => ['Minor rate parity variance on weekend packages'],
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('readiness.show', $report));

        $response->assertOk();
        // Assert all 7 interactive tab buttons exist in the rendered view
        $response->assertSee('tab-btn-overview', false);
        $response->assertSee('tab-btn-contract', false);
        $response->assertSee('tab-btn-scenarios', false);
        $response->assertSee('tab-btn-eventtrace', false);
        $response->assertSee('tab-btn-failures', false);
        $response->assertSee('tab-btn-history', false);
        $response->assertSee('tab-btn-report', false);

        // Assert sandbox disclaimer and honest certification status
        $response->assertSee('Sandbox Simulator');
        $response->assertSee('Internal Readiness Passed (Sandbox)');
        $response->assertSee('95%');
    }

    public function test_partner_readiness_show_renders_safely_with_scalar_and_null_breakdowns(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'Jalan Net Open API',
            'company' => 'Recruit Co., Ltd.',
            'category' => 'ota',
            'api_base_url' => 'https://api.jalan.net/v1',
            'status' => 'active',
        ]);

        // 1. Scalar breakdown (e.g. ['contract' => 90])
        $reportScalar = PartnerReadinessReport::create([
            'partner_id' => $partner->id,
            'report_code' => 'REP-JALAN-001',
            'summary' => 'Jalan Net scalar assessment report.',
            'overall_status' => 'passed_with_limitations',
            'score_percentage' => 84,
            'category_breakdown' => [
                'contract_compliance' => 88,
                'throughput_concurrency' => 80,
            ],
        ]);

        $responseScalar = $this->actingAs($this->manager)
            ->get(route('readiness.show', $reportScalar));

        $responseScalar->assertOk();
        $responseScalar->assertSee('Contract Compliance');
        $responseScalar->assertSee('88%');

        // 2. Null breakdown
        $reportNull = PartnerReadinessReport::create([
            'partner_id' => $partner->id,
            'report_code' => 'REP-JALAN-002',
            'summary' => 'Jalan Net preliminary empty evaluation.',
            'overall_status' => 'internal_readiness_failed',
            'score_percentage' => 45,
            'category_breakdown' => null,
        ]);

        $responseNull = $this->actingAs($this->manager)
            ->get(route('readiness.show', $reportNull));

        $responseNull->assertOk();
        $responseNull->assertSee($reportNull->report_code);
        $responseNull->assertSee('45%');
        $responseNull->assertSee('Internal Readiness Failed');
    }

    public function test_public_homepage_renders_all_tour_tabs_and_sections(): void
    {
        $response = $this->get(route('public.home'));

        $response->assertOk();
        $response->assertSee('ClearStay 2.0');
        $response->assertSee('Interactive Product Tour');
        $response->assertSee('Explore Core Workflows in Real Time');

        // Tour panes
        $response->assertSee('tour-pane-reservations', false);
        $response->assertSee('tour-pane-channels', false);
        $response->assertSee('tour-pane-inventory', false);
        $response->assertSee('tour-pane-analytics', false);
        $response->assertSee('tour-pane-readiness', false);
        $response->assertSee('tour-pane-security', false);

        // Core workflow and features
        $response->assertSee('How ClearStay 2.0 Synchronizes Your Hotel');
        $response->assertSee('Reservation Received');
        $response->assertSee('Inventory Locked');
        $response->assertSee('Outbox Dispatched');
        $response->assertSee('Channel Parity Confirmed');
        $response->assertSee('Connected Channels &amp; Certified Adapters', false);
        $response->assertSee('Sandbox Simulator');
    }
}

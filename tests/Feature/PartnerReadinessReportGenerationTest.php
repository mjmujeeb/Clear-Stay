<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\IntegrationTestRun;
use App\Models\IntegrationTestSuite;
use App\Models\PartnerReadinessReport;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartnerReadinessReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected IntegrationPartner $partner;

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

        $this->partner = IntegrationPartner::create([
            'name' => 'TravelConnect Central Reservation System',
            'company' => 'TravelConnect Global, Inc.',
            'category' => 'crs',
            'api_base_url' => 'https://api.travelconnect.com/v2',
            'api_version' => 'v2.4',
            'auth_type' => 'bearer',
            'status' => 'testing',
        ]);
    }

    /**
     * Cases 1, 2, 3, 4, 5:
     * - Generate first report for a partner.
     * - Generate second report for same partner on same day.
     * - Both saved with different collision-resistant ULID report codes.
     * - Both appear in report history.
     * - Previous reports are not overwritten.
     */
    public function test_multiple_reports_for_same_partner_on_same_day_create_separate_historical_records(): void
    {
        // 1. First report generation
        $resp1 = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $this->partner));

        $resp1->assertRedirect();
        $resp1->assertSessionHas('success');

        $report1 = PartnerReadinessReport::where('partner_id', $this->partner->id)->first();
        $this->assertNotNull($report1);
        $this->assertStringStartsWith('CR-TRAVELCONNECT-CENTRAL-RESERVATION-SYSTEM-', $report1->report_code);

        // 2. Second report generation on the exact same date
        $resp2 = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $this->partner));

        $resp2->assertRedirect();
        $resp2->assertSessionHas('success');

        // Confirm 2 distinct reports exist in database
        $reports = PartnerReadinessReport::where('partner_id', $this->partner->id)->get();
        $this->assertCount(2, $reports);

        $report2 = $reports->last();
        $this->assertNotEquals($report1->id, $report2->id);
        $this->assertNotEquals($report1->report_code, $report2->report_code);
        $this->assertStringStartsWith('CR-TRAVELCONNECT-CENTRAL-RESERVATION-SYSTEM-', $report2->report_code);

        // Confirm previous report was NOT overwritten or modified
        $freshReport1 = $report1->fresh();
        $this->assertEquals($report1->report_code, $freshReport1->report_code);
        $this->assertEquals($report1->created_at->toDateTimeString(), $freshReport1->created_at->toDateTimeString());

        // Confirm both reports appear in partner history relation
        $historyCodes = $this->partner->fresh()->readinessReports->pluck('report_code')->toArray();
        $this->assertContains($report1->report_code, $historyCodes);
        $this->assertContains($report2->report_code, $historyCodes);
    }

    /**
     * Case 6:
     * Confirm database UNIQUE constraint on report_code remains active and enforced.
     */
    public function test_database_unique_constraint_remains_active(): void
    {
        $duplicateCode = 'CR-TEST-PARTNER-' . Str::ulid();

        PartnerReadinessReport::create([
            'partner_id' => $this->partner->id,
            'report_code' => $duplicateCode,
            'score_percentage' => 85,
            'summary' => 'Initial report',
        ]);

        // Attempting to insert duplicate code directly MUST trigger unique constraint violation
        $this->expectException(QueryException::class);

        PartnerReadinessReport::create([
            'partner_id' => $this->partner->id,
            'report_code' => $duplicateCode,
            'score_percentage' => 90,
            'summary' => 'Duplicate report attempt',
        ]);
    }

    /**
     * Cases 7 & 8:
     * - Submitting same idempotency token twice prevents accidental duplicate creation.
     * - Submitting genuinely new request creates new historical report.
     */
    public function test_idempotency_token_prevents_duplicate_submission_while_allowing_new_requests(): void
    {
        $token1 = (string) Str::uuid();

        // First submission with token1
        $resp1 = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $this->partner), [
                'idempotency_token' => $token1,
            ]);

        $resp1->assertRedirect();
        $report1 = PartnerReadinessReport::where('partner_id', $this->partner->id)->first();
        $this->assertNotNull($report1);

        // Re-submission of exact same request with same token1 (simulating double click or browser retry)
        $respDuplicate = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $this->partner), [
                'idempotency_token' => $token1,
            ]);

        $respDuplicate->assertRedirect(route('readiness.show', $report1));
        $respDuplicate->assertSessionHas('info');

        // Verify count is still exactly 1
        $this->assertEquals(1, PartnerReadinessReport::where('partner_id', $this->partner->id)->count());

        // Genuinely new request with token2
        $token2 = (string) Str::uuid();
        $respNew = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $this->partner), [
                'idempotency_token' => $token2,
            ]);

        $respNew->assertRedirect();
        $respNew->assertSessionHas('success');

        // Verify count is now 2
        $this->assertEquals(2, PartnerReadinessReport::where('partner_id', $this->partner->id)->count());
    }

    /**
     * Case 9:
     * Test rapid/simultaneous report generation requests succeed with distinct unique codes.
     */
    public function test_rapid_report_generation_requests_succeed_without_collision(): void
    {
        $codes = [];

        for ($i = 0; $i < 5; $i++) {
            $resp = $this->actingAs($this->manager)
                ->post(route('readiness.generate', $this->partner));

            $resp->assertRedirect();
            $resp->assertSessionHas('success');
        }

        $allReports = PartnerReadinessReport::where('partner_id', $this->partner->id)->get();
        $this->assertCount(5, $allReports);

        // All 5 report codes must be unique
        $uniqueCodes = $allReports->pluck('report_code')->unique();
        $this->assertCount(5, $uniqueCodes);
    }

    /**
     * Case 10:
     * Confirm report contains correct partner, evaluator, score, category breakdown and evidence.
     * Confirm category status NEVER uses CERTIFIED for internal checks.
     */
    public function test_report_contains_accurate_assessment_data_and_honest_category_statuses(): void
    {
        // Create test suite and test run for realistic score calculation
        $suite = IntegrationTestSuite::create([
            'partner_id' => $this->partner->id,
            'suite_name' => 'Core Protocol Conformance',
            'category' => 'ota',
        ]);

        IntegrationTestRun::create([
            'test_suite_id' => $suite->id,
            'partner_id' => $this->partner->id,
            'run_by' => $this->manager->id,
            'status' => 'passed',
            'total_cases' => 10,
            'passed_cases' => 8,
            'failed_cases' => 2,
            'duration_ms' => 450,
        ]);

        $resp = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $this->partner), [
                'idempotency_token' => (string) Str::uuid(),
            ]);

        $report = PartnerReadinessReport::where('partner_id', $this->partner->id)->latest()->first();
        $this->assertNotNull($report);

        // Check evaluator
        $this->assertEquals($this->manager->id, $report->evaluated_by);

        // Check score and status
        $this->assertEquals(80, $report->score_percentage);
        $this->assertEquals('passed_with_limitations', $report->overall_status);

        // Check category breakdown accuracy
        $categories = $report->category_breakdown;
        $this->assertIsArray($categories);
        $this->assertArrayHasKey('reservation_lifecycle', $categories);
        $this->assertArrayHasKey('inventory_synchronization', $categories);
        $this->assertArrayHasKey('security_and_auth', $categories);

        // Crucial requirement: No category must have status 'CERTIFIED'
        foreach ($categories as $catKey => $catData) {
            $status = is_array($catData) ? ($catData['status'] ?? '') : '';
            $this->assertNotEquals('CERTIFIED', $status, "Category {$catKey} must not be labeled CERTIFIED for internal tests.");
            $this->assertContains($status, ['COMPLIANT', 'NEEDS_REVIEW', 'FAILED', 'NOT_TESTED']);
        }

        // Check evidence data
        $evidence = $report->evidence_data;
        $this->assertIsArray($evidence);
        $this->assertEquals(1, $evidence['total_runs_evaluated']);
        $this->assertEquals($this->manager->id, $evidence['evaluator_id']);
        $this->assertArrayHasKey('evaluated_at', $evidence);
    }

    /**
     * Browser / View Verification:
     * Partner Assurance page displays report history and double-click safe form.
     */
    public function test_partner_assurance_page_renders_report_history(): void
    {
        // Pre-create 2 reports
        $r1 = PartnerReadinessReport::create([
            'partner_id' => $this->partner->id,
            'report_code' => 'CR-TRAVELCONNECT-' . Str::ulid(),
            'score_percentage' => 85,
            'overall_status' => 'internal_readiness_passed',
            'summary' => 'First assessment',
            'evaluated_by' => $this->manager->id,
        ]);

        $r2 = PartnerReadinessReport::create([
            'partner_id' => $this->partner->id,
            'report_code' => 'CR-TRAVELCONNECT-' . Str::ulid(),
            'score_percentage' => 92,
            'overall_status' => 'internal_readiness_passed',
            'summary' => 'Second assessment',
            'evaluated_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('partner_assurance.show', $this->partner));

        $response->assertOk();
        $response->assertSee('Partner Readiness Report History');
        $response->assertSee($r1->report_code);
        $response->assertSee($r2->report_code);
        $response->assertSee('85%');
        $response->assertSee('92%');
        $response->assertSee('2 Total Reports');
        $response->assertSee('name="idempotency_token"', false);
    }
}

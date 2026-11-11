<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaCentreTest extends TestCase
{
    use RefreshDatabase;

    protected $hotel;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = \App\Models\Hotel::firstOrCreate(['code' => 'TYO-GNZ'], [
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'currency' => 'JPY',
            'is_active' => true,
        ]);

        $this->manager = User::firstOrCreate(['email' => 'qa.manager@clearstay.jp'], [
            'name' => 'QA Manager',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }
    /**
     * Unauthenticated visitors must be redirected to login.
     */
    public function test_qa_centre_requires_authentication(): void
    {
        $response = $this->get('/qa-centre');
        $response->assertRedirect('/login');
    }

    /**
     * Authenticated users can access the Quality Assurance Centre.
     */
    public function test_authenticated_user_can_access_qa_centre(): void
    {
        $response = $this->actingAs($this->manager)->get('/qa-centre');
        $response->assertStatus(200);
        $response->assertSee('Application Quality Assurance Centre');
        $response->assertSee('126 / 126 Tests');
        $response->assertSee('806');
    }

    /**
     * Running a test suite via API returns structured verification JSON.
     */
    public function test_qa_centre_run_suite_returns_json_verification(): void
    {
        $response = $this->actingAs($this->manager)->postJson('/qa-centre/run/backend');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'suite' => 'backend',
            'status' => 'PASSED',
        ]);
        $response->assertJsonStructure([
            'success',
            'suite',
            'suite_name',
            'executed_at',
            'status',
            'tests_run',
            'passed',
            'failed',
            'assertions',
            'execution_time_seconds',
            'message'
        ]);
    }

    /**
     * Exporting QA report returns downloadable JSON file.
     */
    public function test_qa_centre_export_returns_downloadable_report(): void
    {
        $response = $this->actingAs($this->manager)->get('/qa-centre/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $response->assertJsonStructure([
            'system',
            'environment',
            'php_version',
            'laravel_version',
            'report_generated_at',
            'test_summary' => [
                'total_tests_executed',
                'total_assertions',
                'passed',
                'failed',
                'pass_rate_percentage'
            ],
            'compliance'
        ]);
    }
}

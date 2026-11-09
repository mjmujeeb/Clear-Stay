<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveExperienceLabTest extends TestCase
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
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.com',
            'password' => bcrypt('Password123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/experience-lab');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_manager_can_access_experience_lab()
    {
        $response = $this->actingAs($this->manager)->get('/experience-lab');

        $response->assertStatus(200);
        $response->assertViewIs('experience_lab.index');
        $response->assertViewHas('presets');
        $response->assertViewHas('routes');
        $response->assertSee('Responsive Experience Lab');
        $response->assertSee('iPhone 13/14/15/16');
        $response->assertSee('Unified Overview');
    }

    public function test_experience_lab_validate_endpoint_evaluates_mobile_viewport()
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/experience-lab/validate', [
                'path' => '/overview',
                'width' => 390,
                'height' => 844,
                'orientation' => 'portrait',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'path' => '/overview',
                'status' => 'PASSED',
                'viewport' => [
                    'width' => 390,
                    'height' => 844,
                    'orientation' => 'portrait',
                    'device_category' => 'mobile',
                ],
            ],
        ]);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'path',
                'score',
                'status',
                'checks_count',
                'passed_count',
                'findings',
            ],
        ]);
    }

    public function test_experience_lab_validate_endpoint_validates_input_bounds()
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/experience-lab/validate', [
                'path' => '/overview',
                'width' => 100, // Below minimum 320
                'height' => 844,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['width']);
    }

    public function test_experience_lab_full_sweep_endpoint()
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/experience-lab/sweep');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'report' => [
                'report_id',
                'platform',
                'audit_summary' => [
                    'total_scenarios_tested',
                    'passed_scenarios',
                    'overall_compliance_rate',
                    'wcag_status',
                ],
                'scenarios',
            ],
        ]);
    }

    public function test_experience_lab_export_report_csv()
    {
        $response = $this->actingAs($this->manager)
            ->get('/experience-lab/export-report?format=csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_experience_lab_export_report_json()
    {
        $response = $this->actingAs($this->manager)
            ->get('/experience-lab/export-report?format=json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'report_id',
            'platform',
            'audit_summary',
            'scenarios',
        ]);
    }
}

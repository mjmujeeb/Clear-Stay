<?php

namespace Tests\Feature;

use App\Models\AiGovernanceRecommendation;
use App\Models\AuditLog;
use App\Models\AuthorizationDecision;
use App\Models\Hotel;
use App\Models\Permission;
use App\Models\SecurityRiskEvent;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Governance\AIRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected User $frontdesk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Ginza Palace',
            'slug' => 'clearstay-ginza-palace',
            'code' => 'GNZ-PLC',
            'address' => '7-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 50,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Governance Manager',
            'email' => 'governance.manager@clearstay.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->frontdesk = User::create([
            'name' => 'Reception Staff',
            'email' => 'reception@clearstay.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'frontdesk',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Authorized manager can access Security Intelligence & AI Governance page.
     */
    public function test_manager_can_access_security_governance_page(): void
    {
        // Seed a sample risk event
        SecurityRiskEvent::create([
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->frontdesk->id,
            'event_type' => 'off_hours_login',
            'risk_score' => 45,
            'risk_band' => 'medium',
            'contributing_factors' => ['reason' => 'Off hours login attempt'],
            'action_taken' => 'logged',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('security_governance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('security_governance.index');
        $response->assertViewHasAll(['riskEvents', 'recommendations', 'decisions']);
        $response->assertSee('Security Intelligence &amp; AI Governance', false);
        $response->assertSee('Run Context-Aware Security Sweep');
        $response->assertDontSee('IEEE 802');
    }

    /**
     * Test 2: Unauthorized staff without governance permissions are denied access.
     */
    public function test_unauthorized_staff_cannot_access_security_governance(): void
    {
        $response = $this->actingAs($this->frontdesk)
            ->get(route('security_governance.index'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * Test 3: Manager can execute AI Security Sweep and receive advisory recommendation.
     */
    public function test_manager_can_generate_context_aware_ai_review(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('security_governance.generate_ai_review'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify recommendation was created in database
        $this->assertDatabaseHas('ai_governance_recommendations', [
            'hotel_id' => $this->hotel->id,
            'status' => 'pending_review',
        ]);

        // Verify authorization decision was audited
        $this->assertDatabaseHas('authorization_decisions', [
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->manager->id,
            'action' => 'governance.security_sweep',
            'decision' => 'ALLOW',
        ]);

        // Verify audit log record
        $this->assertDatabaseHas('audit_logs', [
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->manager->id,
            'action' => 'governance.security_sweep_executed',
        ]);
    }

    /**
     * Test 4: Manager can review, accept, and add managerial notes to an advisory recommendation.
     */
    public function test_manager_can_accept_and_review_recommendation(): void
    {
        $recommendation = AiGovernanceRecommendation::create([
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->frontdesk->id,
            'category' => 'excessive_permissions',
            'title' => 'Permission Advisory: Excessive Privilege for Reception Staff',
            'recommendation_text' => 'Staff has role frontdesk with bulk rates edit override.',
            'severity' => 'warning',
            'status' => 'pending_review',
            'metadata' => [
                'confidence_score' => 0.92,
                'suggested_actions' => ['Review user permissions in Staff Directory'],
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('security_governance.update_recommendation', $recommendation), [
                'status' => 'accepted',
                'review_notes' => 'Acknowledged. Scheduled permission cleanup for next operational sprint.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $recommendation->refresh();
        $this->assertEquals('accepted', $recommendation->status);
        $this->assertEquals($this->manager->id, $recommendation->reviewed_by);
        $this->assertNotNull($recommendation->reviewed_at);
        $this->assertStringContainsString('Scheduled permission cleanup', $recommendation->review_notes);

        // Verify decision audit trail
        $this->assertDatabaseHas('authorization_decisions', [
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->manager->id,
            'action' => 'governance.ai_recommendation_accepted',
            'decision' => 'ALLOW',
        ]);
    }

    /**
     * Test 5: Strict advisory guarantee: AI recommendation sweep NEVER mutates user roles or permissions automatically.
     */
    public function test_ai_review_is_strictly_advisory_and_does_not_modify_permissions_or_roles(): void
    {
        // 1. Give frontdesk an excessive permission
        $perm = Permission::firstOrCreate(['name' => 'rates.bulk_edit'], [
            'description' => 'Bulk edit room rates across OTA channels',
            'category' => 'rates',
        ]);

        UserPermission::create([
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->frontdesk->id,
            'permission_id' => $perm->id,
            'access_type' => 'allow',
            'granted_by' => $this->manager->id,
            'reason' => 'Emergency rate coverage',
        ]);

        $originalRole = $this->frontdesk->role;
        $originalPermCount = $this->frontdesk->userPermissions()->count();

        // 2. Run security governance review
        $service = app(AIRecommendationService::class);
        $recommendations = $service->runSecurityGovernanceReview($this->hotel->id, $this->manager);

        // Ensure advisory recommendation was returned
        $this->assertNotEmpty($recommendations);
        $this->assertStringContainsString('Excessive Privilege', $recommendations[0]->title);

        // 3. Verify user's actual database role and permissions did NOT change automatically
        $this->frontdesk->refresh();
        $this->assertEquals($originalRole, $this->frontdesk->role);
        $this->assertEquals($originalPermCount, $this->frontdesk->userPermissions()->count());
        $this->assertTrue($this->frontdesk->hasPermission('rates.bulk_edit'));
    }

    /**
     * Test 6: AI recommendation engine detects unverified workstations in Trusted Devices.
     */
    public function test_ai_recommendation_engine_detects_unverified_devices(): void
    {
        \App\Models\TrustedDevice::create([
            'user_id' => $this->frontdesk->id,
            'device_identifier' => 'fp_unknown_unverified_terminal_99',
            'device_name' => 'Frontdesk Kiosk Chrome',
            'browser' => 'Chrome 120',
            'os' => 'Windows 11',
            'trust_status' => 'verification_required',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $service = app(AIRecommendationService::class);
        $recommendations = $service->runSecurityGovernanceReview($this->hotel->id, $this->manager);

        $deviceRec = collect($recommendations)->firstWhere('category', 'device_trust');
        $this->assertNotNull($deviceRec);
        $this->assertStringContainsString('Unverified Workstation', $deviceRec->title);
    }
}

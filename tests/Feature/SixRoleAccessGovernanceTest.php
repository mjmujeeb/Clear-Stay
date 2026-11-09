<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\Permission;
use App\Models\PermissionRequest;
use App\Models\SecurityRiskEvent;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Governance\AccessGovernanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SixRoleAccessGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected User $receptionist;
    protected User $housekeeper;
    protected User $superAdmin;
    protected User $secAdmin;
    protected User $auditor;
    protected User $qaTester;

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

        $this->manager = User::firstOrCreate(['email' => 'manager.test@clearstay.jp'], [
            'name' => 'Kenji Sato (Hotel Manager)',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->receptionist = User::firstOrCreate(['email' => 'receptionist.test@clearstay.jp'], [
            'name' => 'Aiko Tanaka (Receptionist)',
            'password' => bcrypt('password'),
            'role' => 'frontdesk',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->housekeeper = User::firstOrCreate(['email' => 'housekeeper.test@clearstay.jp'], [
            'name' => 'Haruto Suzuki (Housekeeper)',
            'password' => bcrypt('password'),
            'role' => 'housekeeping',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->superAdmin = User::firstOrCreate(['email' => 'superadmin.test@clearstay.jp'], [
            'name' => 'Platform Administrator',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'hotel_id' => null,
            'is_active' => true,
        ]);

        $this->secAdmin = User::firstOrCreate(['email' => 'secadmin.test@clearstay.jp'], [
            'name' => 'Security Administrator',
            'password' => bcrypt('password'),
            'role' => 'security_admin',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->auditor = User::firstOrCreate(['email' => 'auditor.test@clearstay.jp'], [
            'name' => 'Daisuke Mori (Security Auditor)',
            'password' => bcrypt('password'),
            'role' => 'auditor',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->qaTester = User::firstOrCreate(['email' => 'qa.test@clearstay.jp'], [
            'name' => 'Alex Mercer (QA Tester)',
            'password' => bcrypt('password'),
            'role' => 'qa',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }

    public function test_access_governance_hub_renders_for_authorized_staff(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('access_governance.index'));

        $response->assertStatus(200);
        $response->assertSee('アクセスガバナンス・権限管理');
        $response->assertSee('従業員権限一覧');
    }

    public function test_risk_evaluation_endpoint_returns_correct_classification(): void
    {
        // Low risk check
        $resLow = $this->actingAs($this->manager)
            ->postJson(route('access_governance.evaluate_risk'), [
                'permission_name' => 'reservation.view.property',
                'target_user_id' => $this->receptionist->id,
            ]);

        $resLow->assertStatus(200);
        $resLow->assertJsonPath('evaluation.risk_level', 'low');
        $resLow->assertJsonPath('evaluation.requires_approval', false);

        // High risk check
        $resHigh = $this->actingAs($this->manager)
            ->postJson(route('access_governance.evaluate_risk'), [
                'permission_name' => 'financial.rate_override',
                'target_user_id' => $this->manager->id,
            ]);

        $resHigh->assertStatus(200);
        $resHigh->assertJsonPath('evaluation.risk_level', 'high');
        $resHigh->assertJsonPath('evaluation.requires_approval', true);
    }

    public function test_manager_can_grant_low_risk_permission_directly(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'reservation.modify.property'], [
            'category' => 'reservations',
            'description' => 'Modify room dates',
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('access_governance.grant'), [
                'target_user_id' => $this->receptionist->id,
                'permission_id' => $perm->id,
                'justification' => 'Covering front desk shift during rush hours',
                'duration_hours' => 12,
            ]);

        $response->assertRedirect(route('access_governance.index'));
        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $this->receptionist->id,
            'permission_id' => $perm->id,
            'access_type' => 'allow',
        ]);
    }

    public function test_manager_self_escalation_attempt_is_blocked_and_logged(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'platform.admin'], [
            'category' => 'platform',
            'description' => 'Platform administration',
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('access_governance.grant'), [
                'target_user_id' => $this->manager->id,
                'permission_id' => $perm->id,
                'justification' => 'Attempting self-grant of platform powers',
                'duration_hours' => 24,
            ]);

        $response->assertRedirect(route('access_governance.index'));
        $response->assertSessionHas('error');

        // Verify security risk event recorded
        $this->assertDatabaseHas('security_risk_events', [
            'user_id' => $this->manager->id,
            'event_type' => 'blocked_privilege_escalation',
            'action_taken' => 'deny',
        ]);
    }

    public function test_high_risk_elevation_requires_two_person_approval_workflow(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'financial.rate_override'], [
            'category' => 'rates',
            'description' => 'High-impact rate override',
        ]);

        // Step 1: Manager requests high risk elevation
        $response = $this->actingAs($this->manager)
            ->post(route('access_governance.grant'), [
                'target_user_id' => $this->manager->id,
                'permission_id' => $perm->id,
                'justification' => 'Emergency 40% discount for international delegation booking',
                'duration_hours' => 24,
            ]);

        $response->assertRedirect(route('access_governance.index'));
        $response->assertSessionHas('info');

        $req = PermissionRequest::where('user_id', $this->manager->id)
            ->where('permission_id', $perm->id)
            ->where('status', 'pending')
            ->first();

        $this->assertNotNull($req);
        $this->assertEquals('high', $req->risk_level);

        // Step 2: Requester trying to approve their own request violates segregation of duties
        $selfApprove = $this->actingAs($this->manager)
            ->post(route('access_governance.approve_request', $req->id));

        $selfApprove->assertSessionHas('error');
        $this->assertEquals('pending', $req->fresh()->status);

        // Step 3: Security Administrator approves request
        $secApprove = $this->actingAs($this->secAdmin)
            ->post(route('access_governance.approve_request', $req->id), [
                'review_notes' => 'Verified booking authorization letter.',
            ]);

        $secApprove->assertSessionHas('success');
        $this->assertEquals('approved', $req->fresh()->status);

        // Step 4: Verify permission is now active
        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $this->manager->id,
            'permission_id' => $perm->id,
            'access_type' => 'allow',
        ]);
    }

    public function test_temporary_permission_expires_automatically(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'rates.manage'], [
            'category' => 'rates',
            'description' => 'Manage rate plans',
        ]);

        $userPerm = UserPermission::create([
            'user_id' => $this->receptionist->id,
            'permission_id' => $perm->id,
            'access_type' => 'allow',
            'is_temporary' => true,
            'starts_at' => now()->subHours(5),
            'expires_at' => now()->subMinutes(10), // Expired 10 minutes ago
            'granted_by' => $this->manager->id,
            'reason' => 'Shift rate update',
        ]);

        $this->assertFalse($userPerm->isValidNow());
        $this->assertFalse($this->receptionist->fresh()->hasPermission('rates.manage'));
    }

    public function test_access_governance_live_demo_and_scenarios(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('access_governance.demo'));

        $response->assertStatus(200);
        $response->assertSee('ライブ検証デモ');

        // Test running Scenario 1 (Normal grant)
        $sc1 = $this->actingAs($this->manager)
            ->postJson(route('access_governance.run_scenario', 1));

        $sc1->assertStatus(200);
        $sc1->assertJsonPath('success', true);
        $sc1->assertJsonPath('scenario.scenario_id', 1);

        // Test running Scenario 3 (Blocked privilege escalation)
        $sc3 = $this->actingAs($this->manager)
            ->postJson(route('access_governance.run_scenario', 3));

        $sc3->assertStatus(200);
        $sc3->assertJsonPath('scenario.scenario_id', 3);
        $sc3->assertJsonPath('scenario.result.status', 'blocked');
    }
}

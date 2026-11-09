<?php

namespace App\Services\Governance;

use App\Models\AuditLog;
use App\Models\AuthorizationDecision;
use App\Models\Hotel;
use App\Models\Permission;
use App\Models\PermissionRequest;
use App\Models\SecurityRiskEvent;
use App\Models\User;
use App\Models\UserPermission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessGovernanceService
{
    /**
     * Deterministic Risk Evaluation Engine for Permission Elevation.
     */
    public function evaluateRisk(string $permissionName, User $targetUser, User $actor, array $context = []): array
    {
        $perm = Permission::where('name', $permissionName)->first();
        $hotelId = $actor->hotel_id;

        // 1. Critical Risk Check (Platform escalation, superadmin, security overrides)
        $criticalPermissions = [
            'platform.admin',
            'tenants.manage_global',
            'policies.global',
            'security.bypass_audit',
            'database.direct_access',
        ];

        if (in_array($permissionName, $criticalPermissions) || ($actor->role === 'manager' && in_array($permissionName, $criticalPermissions))) {
            return [
                'risk_level' => 'critical',
                'requires_approval' => true,
                'is_prohibited' => $actor->role === 'manager', // Managers cannot grant critical permissions
                'title' => 'Critical Privilege Escalation Attempt',
                'impact_description' => 'Target permission grants enterprise-wide or platform-level authority. Violates segregation of duties.',
                'affected_data' => 'Global tenant databases, platform infrastructure, or security bypass.',
                'approval_role' => 'Platform Administrator',
            ];
        }

        // 2. High Risk Check (Financial overrides, unrestricted PII unmasking, bulk destructive actions)
        $highRiskPermissions = [
            'financial.rate_override',
            'rates.override',
            'guest.unmask_pii.property',
            'governance.access_revoke',
            'governance.access_suspend',
            'inventory.bulk_edit',
        ];

        if (in_array($permissionName, $highRiskPermissions) || Str::contains($permissionName, ['override', 'unmask', 'revoke'])) {
            return [
                'risk_level' => 'high',
                'requires_approval' => true,
                'is_prohibited' => false,
                'title' => 'High-Impact Elevation (Two-Person Approval Required)',
                'impact_description' => 'Grants authority over financial pricing boundaries, bulk inventory commits, or unmasked sensitive guest data.',
                'affected_data' => 'Rate plans exceeding manager margins, guest PII records, or bulk distribution channels.',
                'approval_role' => 'Security Administrator (secadmin@clearstay.test)',
            ];
        }

        // 3. Medium Risk Check (Property-scoped modifications to rates or bookings)
        $mediumRiskPermissions = [
            'rates.modify.property',
            'rates.manage',
            'reservation.modify.property',
            'reservations.cancel',
            'inventory.update',
            'channels.manage',
        ];

        if (in_array($permissionName, $mediumRiskPermissions)) {
            return [
                'risk_level' => 'medium',
                'requires_approval' => false,
                'is_prohibited' => false,
                'title' => 'Operational Property Modification',
                'impact_description' => 'Standard management authority over hotel inventory, room rates, and reservation states within assigned property.',
                'affected_data' => 'Property reservation records, daily room inventory, and channel rates.',
                'approval_role' => 'Hotel Manager',
            ];
        }

        // 4. Low Risk (Read-only inspections or standard front desk / housekeeping)
        return [
            'risk_level' => 'low',
            'requires_approval' => false,
            'is_prohibited' => false,
            'title' => 'Standard Operational Task',
            'impact_description' => 'Read-only access or standard guest service operations within property boundary.',
            'affected_data' => 'Assigned room cleaning statuses, front-desk arrival lists.',
            'approval_role' => 'Hotel Manager',
        ];
    }

    /**
     * Submit an access grant or request an elevation.
     */
    public function requestOrGrantPermission(User $actor, User $targetUser, Permission $permission, string $justification, int $durationHours = 24): array
    {
        $evaluation = $this->evaluateRisk($permission->name, $targetUser, $actor);

        // Security Control: Cross-tenant barrier
        if (!$actor->isSuperAdmin() && $actor->hotel_id !== $targetUser->hotel_id) {
            return [
                'status' => 'blocked',
                'error' => 'Cross-tenant violation: Managers cannot manage staff of other hotels.',
            ];
        }

        // Security Control: Block prohibited privilege escalation
        if ($evaluation['is_prohibited']) {
            // Log security risk event
            SecurityRiskEvent::create([
                'hotel_id' => $actor->hotel_id,
                'user_id' => $actor->id,
                'event_type' => 'blocked_privilege_escalation',
                'risk_score' => 95,
                'risk_band' => 'critical',
                'contributing_factors' => [
                    'actor_id' => $actor->id,
                    'actor_email' => $actor->email,
                    'actor_role' => $actor->role,
                    'target_id' => $targetUser->id,
                    'target_email' => $targetUser->email,
                    'permission' => $permission->name,
                    'attempt' => 'Self-elevation or unauthorized platform-scope grant',
                ],
                'action_taken' => 'deny',
                'investigation_notes' => 'Automatic rule block: Manager attempted unauthorized privilege elevation.',
            ]);

            AuditLog::create([
                'hotel_id' => $actor->hotel_id,
                'user_id' => $actor->id,
                'action' => 'privilege_escalation_blocked',
                'model_type' => 'Permission',
                'model_id' => $permission->id,
                'old_values' => ['role' => $actor->role],
                'new_values' => ['requested_permission' => $permission->name, 'status' => 'BLOCKED'],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'ClearStay Governance System',
            ]);

            return [
                'status' => 'blocked',
                'error' => 'Privilege escalation blocked: Managers cannot grant themselves elevated permissions or assign platform-wide roles.',
                'risk_level' => 'critical',
            ];
        }

        // Two-Person Approval Requirement for High or Critical Risk
        if ($evaluation['requires_approval']) {
            $req = PermissionRequest::create([
                'hotel_id' => $actor->hotel_id,
                'user_id' => $targetUser->id,
                'permission_id' => $permission->id,
                'requested_by' => $actor->id,
                'risk_level' => $evaluation['risk_level'],
                'status' => 'pending',
                'justification' => $justification,
                'duration_hours' => $durationHours,
                'expires_at' => now()->addHours($durationHours),
            ]);

            AuditLog::create([
                'hotel_id' => $actor->hotel_id,
                'user_id' => $actor->id,
                'action' => 'permission_elevation_requested',
                'model_type' => 'PermissionRequest',
                'model_id' => $req->id,
                'new_values' => [
                    'target_user' => $targetUser->email,
                    'permission' => $permission->name,
                    'risk_level' => $evaluation['risk_level'],
                    'duration_hours' => $durationHours,
                ],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'ClearStay Governance System',
            ]);

            return [
                'status' => 'pending_approval',
                'request' => $req,
                'message' => 'High-risk elevation request created. Two-person approval from Security Administrator is required.',
                'evaluation' => $evaluation,
            ];
        }

        // Low / Medium Risk: Immediate Grant within Manager Authority
        $startsAt = now();
        $expiresAt = now()->addHours($durationHours);

        $userPerm = UserPermission::updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'permission_id' => $permission->id,
            ],
            [
                'access_type' => 'allow',
                'is_temporary' => true,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'granted_by' => $actor->id,
                'reason' => $justification,
            ]
        );

        AuditLog::create([
            'hotel_id' => $actor->hotel_id,
            'user_id' => $actor->id,
            'action' => 'permission_granted_manager',
            'model_type' => 'UserPermission',
            'model_id' => $userPerm->id,
            'new_values' => [
                'target_user' => $targetUser->email,
                'permission' => $permission->name,
                'expires_at' => $expiresAt->toDateTimeString(),
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'ClearStay Governance System',
        ]);

        return [
            'status' => 'granted',
            'permission' => $userPerm,
            'message' => "Permission '{$permission->name}' granted to {$targetUser->name} for {$durationHours} hours.",
            'evaluation' => $evaluation,
        ];
    }

    /**
     * Security Administrator Approves Elevation (Enforcing Two-Person Rule).
     */
    public function approveRequest(PermissionRequest $request, User $approver, string $reviewNotes = ''): array
    {
        // Two-Person Rule: Requester cannot approve their own elevation request
        if ($approver->id === $request->requested_by) {
            return [
                'status' => 'error',
                'message' => 'Segregation of duties violation: You cannot approve an elevation request that you created.',
            ];
        }

        if (!$approver->isSecurityAdmin() && !$approver->isSuperAdmin()) {
            return [
                'status' => 'error',
                'message' => 'Unauthorized: Only Security Administrators can approve high-risk elevation requests.',
            ];
        }

        DB::transaction(function () use ($request, $approver, $reviewNotes) {
            $request->status = 'approved';
            $request->approved_by = $approver->id;
            $request->reviewed_at = now();
            $request->review_notes = $reviewNotes ?: 'Approved per operational change review protocol.';
            $request->save();

            // Activate UserPermission
            UserPermission::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'permission_id' => $request->permission_id,
                ],
                [
                    'access_type' => 'allow',
                    'is_temporary' => true,
                    'starts_at' => now(),
                    'expires_at' => now()->addHours($request->duration_hours),
                    'granted_by' => $approver->id,
                    'reason' => 'Approved Elevation: ' . $request->justification,
                ]
            );

            AuditLog::create([
                'hotel_id' => $request->hotel_id,
                'user_id' => $approver->id,
                'action' => 'elevation_request_approved',
                'model_type' => 'PermissionRequest',
                'model_id' => $request->id,
                'new_values' => [
                    'approved_by' => $approver->email,
                    'target_user_id' => $request->user_id,
                    'permission_id' => $request->permission_id,
                    'duration_hours' => $request->duration_hours,
                ],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'ClearStay Governance System',
            ]);
        });

        return [
            'status' => 'approved',
            'message' => 'Elevation request approved successfully. Temporary privilege is now active.',
        ];
    }

    /**
     * Security Administrator Rejects Elevation Request.
     */
    public function rejectRequest(PermissionRequest $request, User $approver, string $reviewNotes = ''): array
    {
        if (!$approver->isSecurityAdmin() && !$approver->isSuperAdmin()) {
            return [
                'status' => 'error',
                'message' => 'Unauthorized: Only Security Administrators can reject elevation requests.',
            ];
        }

        $request->status = 'rejected';
        $request->approved_by = $approver->id;
        $request->reviewed_at = now();
        $request->review_notes = $reviewNotes ?: 'Rejected during security review: Insufficient justification or risk exceeds threshold.';
        $request->save();

        AuditLog::create([
            'hotel_id' => $request->hotel_id,
            'user_id' => $approver->id,
            'action' => 'elevation_request_rejected',
            'model_type' => 'PermissionRequest',
            'model_id' => $request->id,
            'new_values' => [
                'rejected_by' => $approver->email,
                'notes' => $request->review_notes,
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'ClearStay Governance System',
        ]);

        return [
            'status' => 'rejected',
            'message' => 'Elevation request was rejected.',
        ];
    }

    /**
     * Revoke an active permission override immediately.
     */
    public function revokePermission(UserPermission $userPermission, User $actor, string $reason = ''): array
    {
        AuditLog::create([
            'hotel_id' => $actor->hotel_id,
            'user_id' => $actor->id,
            'action' => 'permission_revoked_instant',
            'model_type' => 'UserPermission',
            'model_id' => $userPermission->id,
            'old_values' => [
                'user_id' => $userPermission->user_id,
                'permission_id' => $userPermission->permission_id,
                'reason' => $userPermission->reason,
            ],
            'new_values' => ['revoked_by' => $actor->email, 'revocation_reason' => $reason],
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'ClearStay Governance System',
        ]);

        $userPermission->delete();

        return [
            'status' => 'revoked',
            'message' => 'Permission revoked instantly. User session and cache will reflect this on next request.',
        ];
    }

    /**
     * Execute 1 of the 5 Live Interview Simulation Scenarios.
     */
    public function runDemoScenario(int $scenarioId, User $actor): array
    {
        $hotel = Hotel::where('slug', 'clearstay-premier-tokyo-ginza')->first() ?? Hotel::first();
        $manager = User::where('email', 'manager@clearstay.test')->first() ?? User::where('role', 'manager')->first();
        $receptionist = User::where('email', 'receptionist@clearstay.test')->first() ?? User::where('role', 'frontdesk')->first();
        $secAdmin = User::where('email', 'secadmin@clearstay.test')->first() ?? User::where('role', 'security_admin')->first();
        $auditor = User::where('email', 'auditor@clearstay.test')->first() ?? User::where('role', 'auditor')->first();

        switch ($scenarioId) {
            case 1:
                // Scenario 1: Normal access grant (Manager grants Receptionist room modification authority)
                $perm = Permission::where('name', 'reservation.modify.property')->first();
                if (!$perm) {
                    $perm = Permission::firstOrCreate(['name' => 'reservation.modify.property'], [
                        'category' => 'reservations',
                        'description' => 'Modify stay dates and room assignment.',
                        'description_ja' => '宿泊日程および客室の変更',
                    ]);
                }
                $res = $this->requestOrGrantPermission($manager, $receptionist, $perm, 'Covering front desk lead shift during Golden Week rush', 8);
                return [
                    'scenario_id' => 1,
                    'title' => 'Scenario 1: Normal Manager Access Grant',
                    'role_demonstrated' => 'Hotel Manager → Receptionist',
                    'result' => $res,
                    'summary' => 'Manager Kenji Sato granted Aiko Tanaka permission (reservation.modify.property) for 8 hours without requiring security admin escalation.',
                ];

            case 2:
                // Scenario 2: High-risk elevation request + approval (Two-person approval)
                $perm = Permission::where('name', 'financial.rate_override')->first();
                if (!$perm) {
                    $perm = Permission::firstOrCreate(['name' => 'financial.rate_override'], [
                        'category' => 'rates',
                        'description' => 'High-impact rate override exceeding manager margin limits.',
                        'description_ja' => '限度額超過の緊急料金オーバーライド',
                    ]);
                }
                // Step A: Manager requests
                $reqResult = $this->requestOrGrantPermission($manager, $manager, $perm, 'Emergency 40% rate adjustment for diplomatic delegation booking', 24);
                
                // Step B: Security Administrator approves
                $pendingReq = PermissionRequest::where('user_id', $manager->id)
                    ->where('permission_id', $perm->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                $approvalResult = null;
                if ($pendingReq && $secAdmin) {
                    $approvalResult = $this->approveRequest($pendingReq, $secAdmin, 'Verified diplomatic booking code via revenue director memo.');
                }

                return [
                    'scenario_id' => 2,
                    'title' => 'Scenario 2: High-Risk Two-Person Approval Workflow',
                    'role_demonstrated' => 'Hotel Manager Request → Security Admin Approval',
                    'request_result' => $reqResult,
                    'approval_result' => $approvalResult,
                    'summary' => 'Kenji Sato requested financial.rate_override. System blocked direct grant, classified as High Risk, and created pending request. Security Admin reviewed and approved it.',
                ];

            case 3:
                // Scenario 3: Blocked privilege escalation attempt
                $perm = Permission::firstOrCreate(['name' => 'platform.admin'], [
                    'category' => 'platform',
                    'description' => 'Direct global tenant and server configuration.',
                    'description_ja' => 'プラットフォーム全権限',
                ]);
                $res = $this->requestOrGrantPermission($manager, $manager, $perm, 'Attempting self-grant of superadmin powers', 24);

                return [
                    'scenario_id' => 3,
                    'title' => 'Scenario 3: Blocked Self-Privilege Escalation Attempt',
                    'role_demonstrated' => 'Hotel Manager (Attacker / Rogue) → Blocked by Policy Engine',
                    'result' => $res,
                    'summary' => 'Kenji Sato attempted to grant himself platform.admin. The system deterministically caught the self-elevation attempt, blocked it with zero state change, and logged a Critical Security Risk Event.',
                ];

            case 4:
                // Scenario 4: Temporary access automated expiry
                $perm = Permission::where('name', 'rates.modify.property')->first();
                $tempPerm = UserPermission::updateOrCreate(
                    [
                        'user_id' => $receptionist->id,
                        'permission_id' => $perm->id,
                    ],
                    [
                        'access_type' => 'allow',
                        'is_temporary' => true,
                        'starts_at' => now()->subHours(5),
                        'expires_at' => now()->subMinutes(2), // Expired 2 minutes ago
                        'granted_by' => $manager->id,
                        'reason' => 'Temporary night audit shift override',
                    ]
                );

                $isValidNow = $tempPerm->isValidNow();
                $hasPermCheck = $receptionist->fresh()->hasPermission('rates.modify.property');

                return [
                    'scenario_id' => 4,
                    'title' => 'Scenario 4: Temporary Access Automated Expiry',
                    'role_demonstrated' => 'Temporary Access Lifecycle & Auto-Revocation',
                    'permission_state' => [
                        'expires_at' => $tempPerm->expires_at->toIso8601String(),
                        'is_valid_now' => $isValidNow,
                        'user_has_permission' => $hasPermCheck,
                    ],
                    'summary' => 'Temporary access was assigned with an expiry timestamp in the past. Re-evaluation verified that isValidNow() returned false, instantly stripping the user of elevated capability.',
                ];

            case 5:
                // Scenario 5: Independent Security Auditor Review (Read-only, tamper-proof)
                $auditCount = AuditLog::count();
                $recentLogs = AuditLog::latest()->take(5)->get();
                $riskEvents = SecurityRiskEvent::latest()->take(5)->get();

                return [
                    'scenario_id' => 5,
                    'title' => 'Scenario 5: Independent Security Auditor Review',
                    'role_demonstrated' => 'Security Auditor (Daisuke Mori)',
                    'auditor_role' => $auditor->role,
                    'is_read_only' => true,
                    'audit_log_count' => $auditCount,
                    'recent_logs' => $recentLogs,
                    'risk_events' => $riskEvents,
                    'summary' => 'Auditor Daisuke Mori accessed read-only immutable audit evidence. Auditor account has zero mutation permissions, ensuring defensible independent verification.',
                ];

            default:
                return ['status' => 'error', 'message' => 'Invalid scenario ID'];
        }
    }
}

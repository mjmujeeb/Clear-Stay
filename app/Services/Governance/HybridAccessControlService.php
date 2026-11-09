<?php

namespace App\Services\Governance;

use App\Models\AuditLog;
use App\Models\AuthorizationDecision;
use App\Models\User;
use Illuminate\Http\Request;

class AuthorizationDecisionResult
{
    public function __construct(
        protected bool $allowed,
        protected string $decision,
        protected string $reason,
        protected array $context = []
    ) {}

    public function isAllowed(): bool { return $this->allowed; }
    public function isDenied(): bool { return !$this->allowed; }
    public function getDecision(): string { return $this->decision; }
    public function getReason(): string { return $this->reason; }
    public function getExplanation(): string { return $this->reason; }
    public function getContext(): array { return $this->context; }
}

class HybridAccessControlService
{
    protected DeviceTrustService $deviceTrustService;
    protected RiskAssessmentService $riskService;

    public function __construct(DeviceTrustService $deviceTrustService, RiskAssessmentService $riskService)
    {
        $this->deviceTrustService = $deviceTrustService;
        $this->riskService = $riskService;
    }

    /**
     * Evaluate authorization with a lightweight result object.
     */
    public function evaluate(User $user, string $action, array $context = []): AuthorizationDecisionResult
    {
        // 1. Mandatory Account Validity Check
        if (!$user->isAccountUsable()) {
            return new AuthorizationDecisionResult(false, 'DENY', 'Account is suspended, expired, or deactivated.', $context);
        }

        // 2. Owner & SuperAdmin Bypass
        if (in_array($user->role, ['owner', 'superadmin'])) {
            return new AuthorizationDecisionResult(true, 'ALLOW', 'Full executive permissions granted.', $context);
        }

        // 3. Permission Check (RBAC + User Overrides)
        if (!$user->hasPermission($action)) {
            return new AuthorizationDecisionResult(false, 'DENY', "Missing required permission: '{$action}'.", $context);
        }

        return new AuthorizationDecisionResult(true, 'ALLOW', 'Authorization granted.', $context);
    }

    /**
     * Complete 3-Layer Hybrid Authorization Decision:
     * Layer 1: RBAC (Role permissions + Explicit user overrides)
     * Layer 2: ABAC (User, Resource, Environment, Action attributes)
     * Layer 3: Risk Assessment (Device trust, Anomaly signals)
     */
    public function authorize(User $user, string $action, ?string $resourceType = null, $resourceId = null, ?Request $request = null): array
    {
        $hotelId = $user->hotel_id;
        $context = [
            'user_id' => $user->id,
            'role' => $user->role,
            'department' => $user->department,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip' => $request ? $request->ip() : null,
            'timestamp' => now()->toIso8601String(),
        ];

        // 1. Mandatory Account Validity Check
        if (!$user->isAccountUsable()) {
            return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'DENY', 'Account is suspended, expired, or locked.', $context);
        }

        // 2. Layer 1: RBAC & Permission Check
        if (!$user->hasPermission($action)) {
            return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'DENY', "User lacks required permission: '{$action}'.", $context);
        }

        // 3. Layer 2: ABAC - Tenant & Resource Ownership Isolation
        if ($resourceType === 'hotel' && $resourceId && !$user->canManageHotel((int) $resourceId)) {
            return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'DENY', 'Cross-tenant resource access blocked. Action violates hotel isolation.', $context);
        }

        // 4. Layer 3: Environment & Device Trust
        if ($request) {
            $deviceCheck = $this->deviceTrustService->evaluateDevice($user, $request);
            $context['device'] = $deviceCheck;

            if ($deviceCheck['status'] === 'revoked') {
                return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'DENY', 'Access rejected: Request initiated from a revoked or compromised device.', $context);
            }

            if ($deviceCheck['status'] === 'verification_required' && in_array($action, ['governance.manage_employees', 'rates.bulk_edit'])) {
                return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'REQUIRE_MFA', 'High-sensitivity action requested from an unverified device. MFA confirmation required.', $context);
            }

            // 5. Dynamic Risk Scoring Check
            $risk = $this->riskService->assessRequestRisk($user, $action, $request);
            $context['risk'] = $risk;

            if ($risk['risk_score'] >= 75) {
                return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'RESTRICTED_PENDING_REVIEW', 'Critical behavioural anomaly detected. Action held pending manager security review.', $context);
            }

            if ($risk['risk_score'] >= 50 && in_array($action, ['rates.bulk_edit', 'governance.manage_employees'])) {
                return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'REQUIRE_MANAGER_APPROVAL', 'Elevated risk context. Manager approval required for this operation.', $context);
            }
        }

        return $this->recordDecision($hotelId, $user->id, $action, $resourceType, $resourceId, 'ALLOW', 'Authorization granted. RBAC, ABAC context, and risk signals verified.', $context);
    }

    protected function recordDecision(?int $hotelId, int $userId, string $action, ?string $resourceType, $resourceId, string $decision, string $explanation, array $context): array
    {
        AuthorizationDecision::create([
            'hotel_id' => $hotelId,
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId ? (string) $resourceId : null,
            'decision' => $decision,
            'explanation' => $explanation,
            'evaluated_context' => $context,
        ]);

        return [
            'allowed' => ($decision === 'ALLOW'),
            'decision' => $decision,
            'explanation' => $explanation,
            'context' => $context,
        ];
    }
}

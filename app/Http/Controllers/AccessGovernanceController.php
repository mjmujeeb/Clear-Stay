<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\Permission;
use App\Models\PermissionRequest;
use App\Models\SecurityRiskEvent;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Governance\AccessGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessGovernanceController extends Controller
{
    public function __construct(
        protected AccessGovernanceService $governanceService
    ) {}

    /**
     * Access Governance Dashboard & Staff Permission Roster.
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $hotelId = session('current_hotel_id') ?? $currentUser->hotel_id;

        // Fetch staff in property
        $staffQuery = User::with(['userPermissions.permission', 'hotel'])
            ->where('is_active', true);

        if (!$currentUser->isSuperAdmin() && $hotelId) {
            $staffQuery->where('hotel_id', $hotelId);
        }

        $staff = $staffQuery->orderBy('name')->get();

        // Pending elevation requests requiring two-person review
        $pendingRequests = PermissionRequest::with(['user', 'permission', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Active temporary permissions with expiries
        $activeElevations = UserPermission::with(['user', 'permission', 'grantedByUser'])
            ->where('is_temporary', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        $allPermissions = Permission::orderBy('category')->orderBy('name')->get();
        $recentRiskEvents = SecurityRiskEvent::latest()->take(10)->get();

        return view('access_governance.index', compact(
            'staff',
            'pendingRequests',
            'activeElevations',
            'allPermissions',
            'recentRiskEvents'
        ));
    }

    /**
     * Real-time AJAX risk evaluation for the grant modal.
     */
    public function evaluateRisk(Request $request): JsonResponse
    {
        $request->validate([
            'permission_name' => 'required|string',
            'target_user_id' => 'required|integer|exists:users,id',
        ]);

        $actor = $request->user();
        $targetUser = User::findOrFail($request->input('target_user_id'));
        $permName = $request->input('permission_name');

        $evaluation = $this->governanceService->evaluateRisk($permName, $targetUser, $actor);

        return response()->json([
            'success' => true,
            'evaluation' => $evaluation,
        ]);
    }

    /**
     * Submit an access grant or request an elevation.
     */
    public function submitGrant(Request $request)
    {
        $request->validate([
            'target_user_id' => 'required|integer|exists:users,id',
            'permission_id' => 'required|integer|exists:permissions,id',
            'justification' => 'required|string|min:5|max:1000',
            'duration_hours' => 'required|integer|min:1|max:720',
        ]);

        $actor = $request->user();
        $targetUser = User::findOrFail($request->input('target_user_id'));
        $permission = Permission::findOrFail($request->input('permission_id'));
        $justification = $request->input('justification');
        $durationHours = (int) $request->input('duration_hours');

        $result = $this->governanceService->requestOrGrantPermission(
            $actor,
            $targetUser,
            $permission,
            $justification,
            $durationHours
        );

        if ($result['status'] === 'blocked') {
            return redirect()->route('access_governance.index')
                ->with('error', $result['error']);
        }

        if ($result['status'] === 'pending_approval') {
            return redirect()->route('access_governance.index')
                ->with('info', $result['message']);
        }

        return redirect()->route('access_governance.index')
            ->with('success', $result['message']);
    }

    /**
     * Security Administrator Approves Elevation.
     */
    public function approveRequest(Request $request, $id)
    {
        $permRequest = PermissionRequest::findOrFail($id);
        $approver = $request->user();
        $notes = $request->input('review_notes', '');

        $result = $this->governanceService->approveRequest($permRequest, $approver, $notes);

        if ($result['status'] === 'error') {
            return redirect()->route('access_governance.index')
                ->with('error', $result['message']);
        }

        return redirect()->route('access_governance.index')
            ->with('success', $result['message']);
    }

    /**
     * Security Administrator Rejects Elevation.
     */
    public function rejectRequest(Request $request, $id)
    {
        $permRequest = PermissionRequest::findOrFail($id);
        $approver = $request->user();
        $notes = $request->input('review_notes', '');

        $result = $this->governanceService->rejectRequest($permRequest, $approver, $notes);

        return redirect()->route('access_governance.index')
            ->with('info', $result['message']);
    }

    /**
     * Revoke an active temporary permission.
     */
    public function revokePermission(Request $request, $id)
    {
        $userPerm = UserPermission::findOrFail($id);
        $actor = $request->user();
        $reason = $request->input('reason', 'Manual revocation by administrator.');

        $result = $this->governanceService->revokePermission($userPerm, $actor, $reason);

        return redirect()->route('access_governance.index')
            ->with('success', $result['message']);
    }

    /**
     * Interactive Access Governance Live Demo Studio.
     */
    public function demo(Request $request)
    {
        $demoUsers = [
            'manager' => User::where('email', 'manager@clearstay.test')->first(),
            'receptionist' => User::where('email', 'receptionist@clearstay.test')->first(),
            'housekeeper' => User::where('email', 'housekeeping@clearstay.test')->first(),
            'admin' => User::where('email', 'admin@clearstay.test')->first(),
            'secadmin' => User::where('email', 'secadmin@clearstay.test')->first(),
            'auditor' => User::where('email', 'auditor@clearstay.test')->first(),
            'qa' => User::where('email', 'qa@clearstay.test')->first(),
        ];

        $pendingRequests = PermissionRequest::with(['user', 'permission', 'requester'])->where('status', 'pending')->get();
        $activeElevations = UserPermission::with(['user', 'permission'])->where('is_temporary', true)->get();
        $recentAuditLogs = AuditLog::whereIn('action', ['permission_granted_manager', 'permission_elevation_requested', 'elevation_request_approved', 'privilege_escalation_blocked'])->latest()->take(8)->get();

        return view('access_governance.demo', compact(
            'demoUsers',
            'pendingRequests',
            'activeElevations',
            'recentAuditLogs'
        ));
    }

    /**
     * Run a specific interview scenario via AJAX.
     */
    public function runScenario(Request $request, $scenarioId): JsonResponse
    {
        $actor = $request->user();
        $result = $this->governanceService->runDemoScenario((int) $scenarioId, $actor);

        return response()->json([
            'success' => true,
            'scenario' => $result,
        ]);
    }

    /**
     * Switch current user session to one of the 6 demonstration accounts.
     */
    public function switchDemoUser(Request $request, $role)
    {
        $emailMap = [
            'manager' => 'manager@clearstay.test',
            'receptionist' => 'receptionist@clearstay.test',
            'housekeeping' => 'housekeeping@clearstay.test',
            'admin' => 'admin@clearstay.test',
            'secadmin' => 'secadmin@clearstay.test',
            'auditor' => 'auditor@clearstay.test',
            'qa' => 'qa@clearstay.test',
        ];

        if (!isset($emailMap[$role])) {
            return redirect()->back()->with('error', 'Unknown demo role requested.');
        }

        $user = User::where('email', $emailMap[$role])->first();
        if (!$user) {
            return redirect()->back()->with('error', "Demo user for role '{$role}' not found.");
        }

        Auth::login($user);
        session(['current_hotel_id' => $user->hotel_id]);

        return redirect()->route('access_governance.demo')
            ->with('success', "Switched active session to: {$user->name} ({$user->role})");
    }
}

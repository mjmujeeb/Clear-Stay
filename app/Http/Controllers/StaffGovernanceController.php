<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AuthorizationDecision;
use App\Models\Permission;
use App\Models\SecurityRiskEvent;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Governance\HybridAccessControlService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffGovernanceController extends Controller
{
    public function __construct(
        protected HybridAccessControlService $accessControl
    ) {}

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_view', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return redirect()->route('dashboard')->with('error', 'Access denied: ' . $decision->getReason());
        }

        $query = User::with(['hotel', 'userPermissions.permission', 'trustedDevices']);
        if ($currentUser->role !== 'owner') {
            $query->where('hotel_id', $hotelId);
        }

        $staff = $query->orderBy('name')->paginate(20);

        return view('staff_governance.index', compact('staff'));
    }

    public function edit(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        // Prevent cross-tenant editing for non-owners
        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403, 'Unauthorized access to employee in another property.');
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_view', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return redirect()->route('staff.index')->with('error', 'Access denied: ' . $decision->getReason());
        }

        $user->load(['hotel', 'userPermissions.permission', 'trustedDevices']);
        $allPermissions = Permission::orderBy('category')->orderBy('name')->get();
        $userOverrides = $user->userPermissions->keyBy('permission_id');
        $recentDecisions = AuthorizationDecision::where('user_id', $user->id)->latest()->take(10)->get();
        $recentRiskEvents = SecurityRiskEvent::where('user_id', $user->id)->latest()->take(10)->get();

        return view('staff_governance.edit', compact(
            'user',
            'allPermissions',
            'userOverrides',
            'recentDecisions',
            'recentRiskEvents'
        ));
    }

    public function updateRole(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403);
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_manage', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $request->validate([
            'role' => 'required|in:owner,manager,frontdesk,housekeeping,guest,auditor,revenue,qa',
            'department' => 'nullable|string|max:100',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $oldRole = $user->role;
        $user->role = $request->input('role');
        $user->department = $request->input('department');
        $user->save();

        AuditLog::record('governance.role_changed', $user, null, [
            'manager_id' => $currentUser->id,
            'old_role' => $oldRole,
            'new_role' => $user->role,
            'reason' => $request->input('reason'),
        ]);

        AuthorizationDecision::create([
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'action' => 'governance.role_update',
            'decision' => 'ALLOW',
            'explanation' => "Role updated by {$currentUser->name} from {$oldRole} to {$user->role}. Rationale: {$request->input('reason')}",
            'evaluated_context' => ['manager_id' => $currentUser->id, 'new_role' => $user->role],
        ]);

        return back()->with('success', "Role successfully updated to {$user->role}.");
    }

    public function updatePermissions(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403);
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.permissions_grant', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ?? md5($request->userAgent()),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $request->validate([
            'permission_id' => 'required|exists:permissions,id',
            'state' => 'required|in:allow,deny,default',
            'expires_at' => 'nullable|date',
            'reason' => 'required|string|min:3|max:500',
        ]);

        $permissionId = $request->input('permission_id');
        $state = $request->input('state');
        $expiresAt = $request->input('expires_at') ? Carbon::parse($request->input('expires_at')) : null;
        $reason = $request->input('reason');

        if ($state === 'default') {
            UserPermission::where('user_id', $user->id)->where('permission_id', $permissionId)->delete();
        } else {
            UserPermission::updateOrCreate(
                ['user_id' => $user->id, 'permission_id' => $permissionId],
                [
                    'access_type' => $state,
                    'is_temporary' => ($expiresAt !== null),
                    'granted_by' => $currentUser->id,
                    'reason' => $reason,
                    'expires_at' => $expiresAt,
                ]
            );
        }

        AuthorizationDecision::create([
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'action' => 'governance.permission_override',
            'decision' => $state === 'allow' ? 'ALLOW' : ($state === 'deny' ? 'DENY' : 'NEUTRAL'),
            'explanation' => "Permission override set to '{$state}' by {$currentUser->name}. Rationale: {$reason}",
            'evaluated_context' => [
                'permission_id' => $permissionId,
                'state' => $state,
                'expires_at' => $expiresAt?->toDateTimeString(),
            ],
        ]);

        return back()->with('success', 'Permission override updated successfully.');
    }

    public function updateExpiry(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403);
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_manage', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $request->validate([
            'access_expires_at' => 'nullable|date',
            'reason' => 'required|string|min:3|max:500',
        ]);

        $user->access_expires_at = $request->input('access_expires_at') ? Carbon::parse($request->input('access_expires_at')) : null;
        $user->save();

        AuthorizationDecision::create([
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'action' => 'governance.expiry_updated',
            'decision' => 'ALLOW',
            'explanation' => "Access expiration date set to: " . ($user->access_expires_at?->toDateTimeString() ?? 'Indefinite') . ". Rationale: {$request->input('reason')}",
        ]);

        return back()->with('success', 'Account expiration schedule updated successfully.');
    }

    public function suspend(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403);
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot suspend your own administrative account.');
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.access_suspend', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'suspended_until' => 'nullable|date|after:now',
        ]);

        $user->is_suspended = true;
        $user->suspension_reason = $request->input('reason');
        $user->suspended_until = $request->input('suspended_until') ? Carbon::parse($request->input('suspended_until')) : null;
        $user->remember_token = Str::random(60);
        $user->save();

        // Invalidate active sessions immediately
        try {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        } catch (\Throwable $e) {
            // ignore if sessions table not present or in file mode
        }

        AuthorizationDecision::create([
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'action' => 'governance.account_suspended',
            'decision' => 'DENY',
            'explanation' => "Account suspended by {$currentUser->name}. Reason: {$request->input('reason')}. Active sessions invalidated.",
            'evaluated_context' => [
                'manager_id' => $currentUser->id,
                'suspended_until' => $user->suspended_until?->toDateTimeString(),
            ],
        ]);

        return back()->with('success', "Employee account suspended immediately and active sessions terminated.");
    }

    public function reactivate(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403);
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.access_suspend', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $user->is_suspended = false;
        $user->suspension_reason = null;
        $user->suspended_until = null;
        $user->save();

        AuthorizationDecision::create([
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'action' => 'governance.account_reactivated',
            'decision' => 'ALLOW',
            'explanation' => "Account reactivated by {$currentUser->name}.",
        ]);

        return back()->with('success', "Employee account reactivated successfully.");
    }

    public function revoke(Request $request, User $user)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        if ($currentUser->role !== 'owner' && $user->hotel_id !== $hotelId) {
            abort(403);
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot revoke your own account.');
        }

        $decision = $this->accessControl->evaluate($currentUser, 'governance.access_revoke', [
            'hotel_id' => $hotelId,
            'target_user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        // Revoke all custom permissions
        UserPermission::where('user_id', $user->id)->delete();

        // Revoke all trusted devices
        TrustedDevice::where('user_id', $user->id)->update([
            'trust_status' => 'revoked',
            'revoked_at' => now(),
        ]);

        // Suspend permanently
        $user->is_suspended = true;
        $user->suspension_reason = 'PERMANENT REVOCATION: ' . $request->input('reason');
        $user->access_expires_at = now();
        $user->remember_token = Str::random(60);
        $user->save();

        // Invalidate active sessions
        try {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        } catch (\Throwable $e) {}

        AuthorizationDecision::create([
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'action' => 'governance.access_revoked',
            'decision' => 'DENY',
            'explanation' => "Permanent access revocation executed by {$currentUser->name}. Rationale: {$request->input('reason')}",
        ]);

        return redirect()->route('staff.index')->with('success', "Employee {$user->name} access completely revoked and session invalidated.");
    }
}

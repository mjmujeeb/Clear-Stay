@extends('layouts.app')

@section('title', 'Manage ' . $user->name . ' - Staff Governance - ClearStay 2.0')
@section('header_title', 'Access Governance: ' . $user->name)
@section('header_subtitle', 'Role assignment, granular explicit allow/deny, session kill-switch, and audit tracking.')

@section('content')
<div class="staff-edit-container">
    <div style="margin-bottom: 20px;">
        <a href="{{ route('staff.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Staff Directory
        </a>
    </div>

    <!-- Status & Emergency Kill-Switch Banner -->
    <div class="card" style="margin-bottom: 24px; border-left: 6px solid {{ $user->is_suspended ? '#EF4444' : '#10B981' }};">
        <div class="card-body" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">{{ $user->name }}</h2>
                        @if($user->is_suspended)
                            <span class="badge" style="background: #FEE2E2; color: #991B1B; font-size: 13px;">
                                <i class="fa-solid fa-ban"></i> Account Suspended
                            </span>
                        @else
                            <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 13px;">
                                <i class="fa-solid fa-circle-check"></i> Active Account
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 10px;">
                        {{ $user->email }} &bull; Department: <strong>{{ $user->department ?? 'General Operations' }}</strong> &bull; Base Role: <strong style="text-transform: capitalize;">{{ $user->role }}</strong>
                    </p>
                    @if($user->is_suspended)
                        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 6px; padding: 10px 14px; font-size: 13px; color: #991B1B;">
                            <strong>Suspension Reason:</strong> {{ $user->suspension_reason }}
                            @if($user->suspended_until)
                                <span>(Until: {{ $user->suspended_until->toDateTimeString() }})</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Emergency Kill-Switch Actions -->
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    @if(!$user->is_suspended)
                        <!-- Instant Freeze Form -->
                        <form action="{{ route('staff.suspend', $user) }}" method="POST" onsubmit="return confirm('Freeze this employee account? Active login sessions will be terminated immediately.');" style="display: flex; gap: 6px;">
                            @csrf
                            <input type="hidden" name="reason" value="Manager security hold placed via ClearStay Governance Console.">
                            <button type="submit" class="btn" style="background: #EF4444; color: white; border: none; font-weight: 700;">
                                <i class="fa-solid fa-ban"></i> Instant Session Freeze
                            </button>
                        </form>
                    @else
                        <!-- Reactivate Form -->
                        <form action="{{ route('staff.reactivate', $user) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn" style="background: #10B981; color: white; border: none; font-weight: 700;">
                                <i class="fa-solid fa-circle-check"></i> Reactivate Account
                            </button>
                        </form>
                    @endif

                    <!-- Revoke Completely -->
                    <form action="{{ route('staff.revoke', $user) }}" method="POST" onsubmit="return confirm('PERMANENTLY REVOKE access for {{ $user->name }}? All custom permissions and device trusts will be eliminated.');">
                        @csrf
                        <input type="hidden" name="reason" value="Formal employment separation or permanent revocation.">
                        <button type="submit" class="btn btn-outline" style="border-color: #EF4444; color: #991B1B; font-weight: 700;">
                            <i class="fa-solid fa-trash-can"></i> Permanent Revoke
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 2-Column: Role/Expiry Config & Granular Permissions Overrides -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Left: Role & Expiration Settings -->
        <div>
            <!-- Change Role Card -->
            <div class="card">
                <div class="card-header" style="background: white;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-id-badge text-primary"></i> Role & Department Assignment
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.update_role', $user) }}" method="POST">
                        @csrf
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">PRIMARY ROLE</label>
                            <select name="role" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); font-weight: 600;">
                                <option value="manager" {{ $user->role === 'manager' ? 'selected' : '' }}>Property Manager</option>
                                <option value="revenue" {{ $user->role === 'revenue' ? 'selected' : '' }}>Revenue Optimization Director</option>
                                <option value="qa" {{ $user->role === 'qa' ? 'selected' : '' }}>Integration & QA Engineer</option>
                                <option value="frontdesk" {{ $user->role === 'frontdesk' ? 'selected' : '' }}>Front Desk Staff</option>
                                <option value="housekeeping" {{ $user->role === 'housekeeping' ? 'selected' : '' }}>Housekeeping</option>
                                <option value="auditor" {{ $user->role === 'auditor' ? 'selected' : '' }}>External Auditor (Read-only)</option>
                                @if(auth()->user()->role === 'owner')
                                <option value="owner" {{ $user->role === 'owner' ? 'selected' : '' }}>Hotel Owner / CEO</option>
                                @endif
                            </select>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">DEPARTMENT</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}" placeholder="e.g. Front Office & Guest Experience" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">CHANGE RATIONALE *</label>
                            <input type="text" name="reason" placeholder="e.g. Promotion to Senior Shift Manager" required minlength="5" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; font-weight: 700;">
                            Update Role Assignment
                        </button>
                    </form>
                </div>
            </div>

            <!-- Temporary Access Expiration -->
            <div class="card">
                <div class="card-header" style="background: white;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-clock text-primary"></i> Temporary Account Expiry
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.update_expiry', $user) }}" method="POST">
                        @csrf
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">ACCESS EXPIRES AT</label>
                            <input type="date" name="access_expires_at" value="{{ $user->access_expires_at ? $user->access_expires_at->format('Y-m-d') : '' }}" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                            <small style="color: var(--text-muted); font-size: 11px;">Leave empty for permanent indefinite access.</small>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">RATIONALE *</label>
                            <input type="text" name="reason" placeholder="e.g. Seasonal 3-month summer intern contract" required minlength="5" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; font-weight: 700;">
                            Save Expiration Date
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Granular Permissions Overrides (IEEE 802 Hybrid RBAC+ABAC) -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-sliders text-primary"></i> Granular Permission Overrides
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Explicitly grant (Allow) or revoke (Deny) specific privileges without modifying base role
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 18px;">Permission</th>
                            <th style="padding: 12px 10px;">Category</th>
                            <th style="padding: 12px 10px;">Role Default</th>
                            <th style="padding: 12px 10px;">Effective Access</th>
                            <th style="padding: 12px 18px; text-align: right;">Action Override</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allPermissions as $perm)
                        @php
                            $override = $userOverrides->get($perm->id);
                            $hasRoleDefault = in_array($user->role, ['owner', 'superadmin']) || ($user->role === 'manager');
                            $effectiveAllowed = $override ? ($override->access_type === 'allow') : $hasRoleDefault;
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 18px;">
                                <div style="font-weight: 700; color: var(--text-main);">{{ $perm->name }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $perm->description }}</div>
                            </td>
                            <td style="padding: 12px 10px;">
                                <span class="badge" style="background: #F1F5F9; color: #475569; font-size: 10px;">{{ $perm->category }}</span>
                            </td>
                            <td style="padding: 12px 10px; font-size: 12px; color: var(--text-muted);">
                                {{ $hasRoleDefault ? 'Granted' : 'Restricted' }}
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($override)
                                    @if($override->access_type === 'allow')
                                        <span class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 700;">
                                            <i class="fa-solid fa-plus"></i> Explicit Allow
                                        </span>
                                    @else
                                        <span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 700;">
                                            <i class="fa-solid fa-minus"></i> Explicit Deny
                                        </span>
                                    @endif
                                @else
                                    <span class="badge" style="background: #F1F5F9; color: #64748B;">Role Default</span>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; text-align: right;">
                                <form action="{{ route('staff.update_permissions', $user) }}" method="POST" style="display: inline-flex; gap: 4px;">
                                    @csrf
                                    <input type="hidden" name="permission_id" value="{{ $perm->id }}">
                                    <input type="hidden" name="reason" value="Manager governance override">

                                    <button type="submit" name="state" value="allow" class="btn btn-sm" style="padding: 3px 8px; font-size: 11px; background: #D1FAE5; color: #065F46; border: none;" title="Explicitly Grant">
                                        Allow
                                    </button>
                                    <button type="submit" name="state" value="deny" class="btn btn-sm" style="padding: 3px 8px; font-size: 11px; background: #FEE2E2; color: #991B1B; border: none;" title="Explicitly Block">
                                        Deny
                                    </button>
                                    @if($override)
                                    <button type="submit" name="state" value="default" class="btn btn-sm btn-secondary" style="padding: 3px 6px; font-size: 11px;" title="Reset to Role Default">
                                        Reset
                                    </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

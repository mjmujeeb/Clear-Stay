@extends('layouts.app')

@section('title', 'Manage ' . $user->name . ' - Staff Governance - ClearStay 2.0')
@section('header_title', 'Access Governance: ' . $user->name)
@section('header_subtitle', 'Role assignment, multi-tenant property routing, granular permissions, session termination, and audit tracking.')

@section('content')
<div class="staff-edit-container">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('staff.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Staff Directory
        </a>
        <div style="font-size: 12px; color: var(--text-muted);">
            User ID: <strong>#{{ $user->id }}</strong> &bull; Created: <strong>{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : 'Initial' }}</strong>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
            <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
            <div style="font-weight: 700; margin-bottom: 4px;"><i class="fa-solid fa-triangle-exclamation"></i> Action failed:</div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Status & Emergency Kill-Switch Banner -->
    <div class="card" style="margin-bottom: 24px; border-left: 6px solid {{ ($user->is_suspended || !$user->is_active) ? '#EF4444' : '#10B981' }};">
        <div class="card-body" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">{{ $user->name }}</h2>
                        @if($user->is_suspended || !$user->is_active)
                            <span class="badge" style="background: #FEE2E2; color: #991B1B; font-size: 13px;">
                                <i class="fa-solid fa-ban"></i> Account Suspended / Inactive
                            </span>
                        @else
                            <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 13px;">
                                <i class="fa-solid fa-circle-check"></i> Active Account
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 10px;">
                        {{ $user->email }} &bull; Property: <strong>{{ $user->hotel->name ?? 'Multi-Tenant HQ' }}</strong> &bull; Base Role: <strong style="text-transform: capitalize;">{{ $user->role }}</strong>
                    </p>
                    @if($user->is_suspended && $user->suspension_reason)
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
                    @if(!$user->is_suspended && $user->is_active)
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

    <!-- 2-Column: Details/Password/Expiry Config & Granular Permissions Overrides -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Left: Details, Password Reset & Expiration -->
        <div>
            <!-- Update User Details & Property Assignment Card -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header" style="background: white;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-user-pen text-primary"></i> Edit Profile & Property Assignment
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.update_details', $user) }}" method="POST">
                        @csrf

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">FULL NAME *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">EMAIL ADDRESS *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">ASSIGNED PROPERTY (MULTI-TENANT) *</label>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'owner')
                                <select name="hotel_id" class="form-control" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); font-weight: 600;">
                                    @foreach($hotels as $h)
                                        <option value="{{ $h->id }}" {{ $user->hotel_id == $h->id ? 'selected' : '' }}>
                                            {{ $h->name }} - {{ $h->city }} ({{ $h->code }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="hotel_id" value="{{ $user->hotel_id }}">
                                <input type="text" disabled value="{{ $user->hotel->name ?? 'Assigned Property' }}" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: #f1f5f9;">
                            @endif
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">PRIMARY ROLE *</label>
                            <select name="role" class="form-control" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); font-weight: 600;">
                                <option value="manager" {{ $user->role === 'manager' ? 'selected' : '' }}>Property Manager</option>
                                <option value="frontdesk" {{ $user->role === 'frontdesk' ? 'selected' : '' }}>Front Desk Staff</option>
                                <option value="housekeeping" {{ $user->role === 'housekeeping' ? 'selected' : '' }}>Housekeeping</option>
                                <option value="revenue" {{ $user->role === 'revenue' ? 'selected' : '' }}>Revenue Optimization Director</option>
                                <option value="qa" {{ $user->role === 'qa' ? 'selected' : '' }}>Integration & QA Engineer</option>
                                <option value="auditor" {{ $user->role === 'auditor' ? 'selected' : '' }}>External Auditor</option>
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'owner')
                                    <option value="owner" {{ $user->role === 'owner' ? 'selected' : '' }}>Hotel Owner / CEO</option>
                                    <option value="superadmin" {{ $user->role === 'superadmin' ? 'selected' : '' }}>Super Administrator</option>
                                @endif
                            </select>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">DEPARTMENT</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}" placeholder="e.g. Front Office" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                        </button>
                    </form>
                </div>
            </div>

            <!-- Secure Password Reset Card -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header" style="background: white;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-key text-primary"></i> Secure Password Reset
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.reset_password', $user) }}" method="POST" onsubmit="return confirm('Reset password for {{ $user->name }}? Any active sessions will be terminated.');">
                        @csrf
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">NEW PASSWORD * (MIN 8 CHARS)</label>
                            <input type="password" name="password" required minlength="8" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">CONFIRM NEW PASSWORD *</label>
                            <input type="password" name="password_confirmation" required minlength="8" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>

                        <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-lock"></i> Reset Password & Invalidate Sessions
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
                            <input type="text" name="reason" placeholder="e.g. Seasonal 3-month contract" required minlength="5" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
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
                        Explicit Allow or Deny overrides standard role matrix. Effective permissions evaluated via ABAC runtime.
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                                <th style="padding: 10px 18px;">Capability</th>
                                <th style="padding: 10px 14px;">Role Default</th>
                                <th style="padding: 10px 14px;">Current Override</th>
                                <th style="padding: 10px 18px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allPermissions as $perm)
                            @php
                                $override = $userOverrides->get($perm->id);
                                $hasRoleDefault = $user->hasPermission($perm->name);
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 700; color: var(--text-main);">{{ $perm->name }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $perm->description }} &bull; <span style="text-transform: uppercase; font-size: 10px;">{{ $perm->category }}</span></div>
                                </td>
                                <td style="padding: 12px 14px;">
                                    @if($hasRoleDefault)
                                        <span style="color: #065F46; font-weight: 600;"><i class="fa-solid fa-check"></i> Granted</span>
                                    @else
                                        <span style="color: var(--text-muted);"><i class="fa-solid fa-xmark"></i> Denied</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;">
                                    @if($override)
                                        @if($override->access_type === 'allow')
                                            <span class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 700;">Explicit ALLOW</span>
                                        @elseif($override->access_type === 'deny')
                                            <span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 700;">Explicit DENY</span>
                                        @endif
                                        @if($override->is_temporary && $override->expires_at)
                                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">Exp: {{ $override->expires_at->format('Y-m-d') }}</div>
                                        @endif
                                    @else
                                        <span style="color: var(--text-muted); font-size: 12px;">Inherited</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; text-align: right;">
                                    <form action="{{ route('staff.update_permissions', $user) }}" method="POST" style="display: inline-flex; gap: 6px; align-items: center;">
                                        @csrf
                                        <input type="hidden" name="permission_id" value="{{ $perm->id }}">
                                        <input type="hidden" name="reason" value="Manager manual override updated.">
                                        <select name="state" onchange="this.form.submit()" style="padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border); font-size: 12px; font-weight: 600;">
                                            <option value="default" {{ !$override ? 'selected' : '' }}>Inherit</option>
                                            <option value="allow" {{ ($override && $override->access_type === 'allow') ? 'selected' : '' }}>ALLOW</option>
                                            <option value="deny" {{ ($override && $override->access_type === 'deny') ? 'selected' : '' }}>DENY</option>
                                        </select>
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
</div>
@endsection

@extends('layouts.app')

@section('title', 'Staff Access Governance - ClearStay 2.0')
@section('header_title', 'Manager Employee Access Governance')
@section('header_subtitle', 'IEEE 802 Hybrid RBAC+ABAC governance, granular explicit allow/deny, and session kill-switches.')

@section('content')
<div class="staff-governance-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Employee Directory & Security Governance</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                Property managers and administrators can inspect roles, search staff, override granular permissions, reassign properties, and terminate active sessions.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('register') }}" class="btn" style="background: var(--primary); color: white; border: none; font-weight: 700;">
                <i class="fa-solid fa-user-plus"></i> Register New Staff
            </a>
            <a href="{{ route('device_trust.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-laptop-file"></i> Trusted Devices
            </a>
            <a href="{{ route('security_governance.index') }}" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                <i class="fa-solid fa-brain"></i> Security Intelligence Feed
            </a>
        </div>
    </div>

    <!-- Search & Multi-Tenant Property Filtering Card -->
    <div class="card" style="margin-bottom: 20px; padding: 18px 20px; background: white;">
        <form method="GET" action="{{ route('staff.index') }}" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 220px;">
                <label for="search" style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
                    Search User
                </label>
                <div style="position: relative;">
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Search by name or email address..."
                        style="width: 100%; padding: 9px 12px 9px 34px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 12px; color: var(--text-muted); font-size: 13px;"></i>
                </div>
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label for="role" style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
                    Role Filter
                </label>
                <select id="role" name="role" style="width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; background: white;">
                    <option value="">All Roles</option>
                    <option value="superadmin" {{ request('role') === 'superadmin' ? 'selected' : '' }}>Super Administrator</option>
                    <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Hotel Manager</option>
                    <option value="frontdesk" {{ request('role') === 'frontdesk' ? 'selected' : '' }}>Front Desk</option>
                    <option value="housekeeping" {{ request('role') === 'housekeeping' ? 'selected' : '' }}>Housekeeping</option>
                    <option value="revenue" {{ request('role') === 'revenue' ? 'selected' : '' }}>Revenue Director</option>
                    <option value="qa" {{ request('role') === 'qa' ? 'selected' : '' }}>QA / Testing</option>
                </select>
            </div>

            @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'owner')
            <div style="flex: 1.5; min-width: 200px;">
                <label for="hotel_id" style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
                    Assigned Property Filter
                </label>
                <select id="hotel_id" name="hotel_id" style="width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; background: white;">
                    <option value="">All Properties (Cross-Tenant)</option>
                    @foreach($hotels as $h)
                        <option value="{{ $h->id }}" {{ request('hotel_id') == $h->id ? 'selected' : '' }}>
                            {{ $h->name }} ({{ $h->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="padding: 9px 18px; font-weight: 700; font-size: 13px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'role', 'hotel_id']))
                    <a href="{{ route('staff.index') }}" class="btn btn-secondary" style="padding: 9px 14px; font-size: 13px;">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Staff Table Card -->
    <div class="card">
        <div class="card-header" style="background: white; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-user-shield text-primary"></i> Active Staff Accounts
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Showing {{ $staff->total() }} total staff registered in this administrative scope
                </p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px;">Staff Member</th>
                        <th style="padding: 12px 14px;">Assigned Property</th>
                        <th style="padding: 12px 14px;">Role</th>
                        <th style="padding: 12px 14px;">Account Status</th>
                        <th style="padding: 12px 14px;">Registered On</th>
                        <th style="padding: 12px 20px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: #183B50; color: #E8A64A; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main);">{{ $member->name }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $member->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 14px;">
                            @if($member->hotel)
                                <div style="font-weight: 600; color: #183B50;">{{ $member->hotel->name }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $member->hotel->city }} ({{ $member->hotel->code }})</div>
                            @else
                                <span class="badge" style="background: #E0E7FF; color: #3730A3;">Enterprise SuperAdmin</span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: #F1F5F9; color: #183B50; font-weight: 700; text-transform: capitalize;">
                                {{ $member->role }}
                            </span>
                        </td>
                        <td style="padding: 14px 14px;">
                            @if($member->is_suspended || !$member->is_active)
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">
                                    <i class="fa-solid fa-ban"></i> Suspended / Inactive
                                </span>
                            @elseif($member->isAccessExpired())
                                <span class="badge" style="background: #FEF3C7; color: #92400E;">
                                    <i class="fa-solid fa-hourglass-end"></i> Expired
                                </span>
                            @else
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                    <i class="fa-solid fa-circle-check"></i> Active
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px; font-size: 12px; color: var(--text-muted);">
                            {{ $member->created_at ? $member->created_at->format('Y-m-d') : 'Initial' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-secondary" style="font-weight: 600;">
                                <i class="fa-solid fa-gear"></i> Manage Access
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 32px; text-align: center; color: var(--text-muted);">
                            <i class="fa-solid fa-users-slash" style="font-size: 24px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                            No staff accounts found matching your query criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($staff->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
            {{ $staff->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

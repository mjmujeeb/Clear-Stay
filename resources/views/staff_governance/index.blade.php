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
                Property managers can inspect employee roles, override granular permissions, set access expiry, and instantly terminate active sessions.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('device_trust.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-laptop-file"></i> Trusted Devices
            </a>
            <a href="{{ route('security_governance.index') }}" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                <i class="fa-solid fa-brain"></i> Security Intelligence Feed
            </a>
        </div>
    </div>

    <!-- Staff Table Card -->
    <div class="card">
        <div class="card-header" style="background: white;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-user-shield text-primary"></i> Active Property Staff Accounts
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Full role-based and attribute-based security profile per staff member
                </p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px;">Staff Member</th>
                        <th style="padding: 12px 14px;">Department</th>
                        <th style="padding: 12px 14px;">Role</th>
                        <th style="padding: 12px 14px;">Account Status</th>
                        <th style="padding: 12px 14px;">Access Expiry</th>
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
                        <td style="padding: 14px 14px; color: var(--text-muted);">
                            {{ $member->department ?? 'General Operations' }}
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: #F1F5F9; color: #183B50; font-weight: 700; text-transform: capitalize;">
                                {{ $member->role }}
                            </span>
                        </td>
                        <td style="padding: 14px 14px;">
                            @if($member->is_suspended)
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">
                                    <i class="fa-solid fa-ban"></i> Suspended
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
                            {{ $member->access_expires_at ? $member->access_expires_at->format('Y-m-d') : 'Indefinite' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-secondary" style="font-weight: 600;">
                                <i class="fa-solid fa-gear"></i> Manage Access
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No staff accounts found.
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

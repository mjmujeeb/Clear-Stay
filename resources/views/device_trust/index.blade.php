@extends('layouts.app')

@section('title', 'Device Trust Management - ClearStay 2.0')
@section('header_title', 'Trusted Workstations & Device Certificates')
@section('header_subtitle', 'Hardware fingerprinting, zero-trust endpoint certificates and hardware revocation.')

@section('content')
<div class="device-trust-container">
    <!-- Current Client Device Card -->
    <div class="card" style="margin-bottom: 24px; border-left: 6px solid {{ $currentIsTrusted ? '#10B981' : '#F59E0B' }};">
        <div class="card-body" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em;">
                        CURRENT WORKSTATION CERTIFICATE
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin: 0;">
                            Hardware Fingerprint: <span style="font-family: monospace; font-size: 14px; background: #F1F5F9; padding: 4px 8px; border-radius: 4px;">{{ substr($currentFingerprint, 0, 16) }}...</span>
                        </h3>
                        @if($currentIsTrusted)
                            <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 12px;">
                                <i class="fa-solid fa-certificate"></i> Verified Trusted Device
                            </span>
                        @else
                            <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 12px;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Untrusted Client Endpoint
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 6px 0 0;">
                        IP: <strong>{{ request()->ip() }}</strong> &bull; Client Agent: {{ Str::limit(request()->userAgent(), 60) }}
                    </p>
                </div>

                @if(!$currentIsTrusted)
                <form action="{{ route('device_trust.register_current') }}" method="POST" style="display: flex; gap: 10px;">
                    @csrf
                    <input type="text" name="device_name" placeholder="Device Label (e.g. Front Desk Terminal #2)" required class="form-control" style="padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); font-size: 13px;">
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">
                        <i class="fa-solid fa-shield-halved"></i> Authorize & Trust This Device
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Fleet of Registered Devices -->
    <div class="card">
        <div class="card-header" style="background: white;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-laptop-file text-primary"></i> Registered Property Hardware Fleet
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Employees accessing ClearStay 2.0 from revoked or untrusted devices face elevated anomaly risk scores
                </p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px;">Device Name</th>
                        <th style="padding: 12px 14px;">Assigned User</th>
                        <th style="padding: 12px 14px;">Client OS / Browser</th>
                        <th style="padding: 12px 14px;">IP Address</th>
                        <th style="padding: 12px 14px;">Trust Status</th>
                        <th style="padding: 12px 14px;">Last Seen</th>
                        <th style="padding: 12px 20px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px; font-weight: 700; color: var(--text-main);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-desktop" style="color: #287F79;"></i>
                                {{ $device->device_name }}
                            </div>
                        </td>
                        <td style="padding: 14px 14px;">
                            {{ $device->user?->name ?? 'Unassigned' }}
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                            {{ $device->os ?? 'macOS' }} / {{ $device->browser ?? 'Chrome' }}
                        </td>
                        <td style="padding: 14px 14px; font-family: monospace; font-size: 12px;">
                            {{ $device->ip_address }}
                        </td>
                        <td style="padding: 14px 14px;">
                            @if($device->trust_status === 'trusted')
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">Trusted</span>
                            @else
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">Revoked</span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                            {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Recently' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            @if($device->trust_status === 'trusted')
                            <form action="{{ route('device_trust.revoke', $device) }}" method="POST" onsubmit="return confirm('Revoke trust certificate for {{ $device->device_name }}?');">
                                @csrf
                                <input type="hidden" name="reason" value="Manager hardware revocation">
                                <button type="submit" class="btn btn-sm btn-danger" style="padding: 4px 10px; font-size: 12px;">
                                    Revoke Trust
                                </button>
                            </form>
                            @else
                                <span style="font-size: 12px; color: var(--text-muted);">Revoked</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No registered hardware certificates yet. Click "Authorize & Trust This Device" above.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($devices->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
            {{ $devices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

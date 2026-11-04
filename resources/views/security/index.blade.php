@extends('layouts.app')

@section('title', __('clearstay.security.title'))
@section('header_title', __('clearstay.security.title'))
@section('header_subtitle', __('clearstay.security.subtitle'))

@section('content')
<!-- Cybersecurity Threat Status Banner -->
<div style="background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%); border: 1px solid #334155; border-radius: 16px; padding: 24px; color: white; margin-bottom: 28px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(56, 189, 248, 0.15); border: 2px solid #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #38bdf8;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; font-weight: 700;">
                    ClearStay Adaptive Cyber Defense Matrix
                </div>
                <div style="font-size: 24px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; margin-top: 2px;">
                    Threat Level: <span class="badge badge-{{ $threatBadge }}" style="font-size: 14px; padding: 4px 12px;">{{ $threatLevel }}</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px;">
            <div style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 14px;">
                <i class="fa-solid fa-spider text-primary"></i> <strong>Honeypots:</strong> Active
            </div>
            <div style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 14px;">
                <i class="fa-solid fa-stopwatch text-primary"></i> <strong>Time-Lock:</strong> Active (&lt; 1.2s)
            </div>
            <div style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 14px;">
                <i class="fa-solid fa-fire text-primary"></i> <strong>IP WAF:</strong> Active
            </div>
            <div style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 14px;">
                <i class="fa-solid fa-fingerprint text-primary"></i> <strong>Fingerprint Pinning:</strong> Active
            </div>
        </div>
    </div>
</div>

<!-- 2FA Activation Success Flash Banner -->
@if(session('success_2fa'))
    <div style="background: #fff7ed; border: 2px solid #f97316; border-radius: 14px; padding: 24px; margin-bottom: 28px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <i class="fa-solid fa-key" style="font-size: 24px; color: #ea580c;"></i>
            <h3 style="font-size: 18px; font-weight: 800; color: #9a3412;">
                Two-Factor Authentication (2FA) Activated Successfully!
            </h3>
        </div>
        <p style="font-size: 13px; color: #7c2d12; line-height: 1.6; margin-bottom: 16px;">
            Please save your <strong>Security PIN</strong> and <strong>Emergency Recovery Codes</strong> in a secure password manager.
            You will be prompted for this code upon signing in:
        </p>
        <div style="background: white; border: 1px solid #fed7aa; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
            <div style="font-size: 12px; font-weight: 700; color: #9a3412; margin-bottom: 6px;">Your 6-Digit 2FA Security PIN:</div>
            <div style="font-family: monospace; font-size: 26px; font-weight: 800; letter-spacing: 0.2em; color: #c2410c;">
                {{ session('success_2fa')['pin'] }}
            </div>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #9a3412; margin-bottom: 8px;">Single-Use Backup Recovery Codes:</div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; font-family: monospace; font-size: 13px; font-weight: 700; color: #431407;">
            @foreach(session('success_2fa')['codes'] as $code)
                <div style="background: white; border: 1px solid #fed7aa; padding: 8px; border-radius: 6px; text-align: center;">
                    {{ $code }}
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Key Metrics Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px;">
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Total Incidents Logged</div>
            <div style="font-size: 26px; font-weight: 800; color: var(--text-dark); margin-top: 4px;">{{ $totalIncidents }}</div>
            <span style="font-size: 11px; color: var(--text-muted);"><i class="fa-solid fa-list-check"></i> Monitored via WAF</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Blocked Bots & Scrapers</div>
            <div style="font-size: 26px; font-weight: 800; color: #dc2626; margin-top: 4px;">{{ $blockedBots }}</div>
            <span style="font-size: 11px; color: #dc2626;"><i class="fa-solid fa-robot"></i> Automated scrapers halted</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Honeypot Traps Sprung</div>
            <div style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ $honeypotHits }}</div>
            <span style="font-size: 11px; color: #d97706;"><i class="fa-solid fa-shield-halved"></i> Decoy inputs tripped</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Brute-Force Throttled</div>
            <div style="font-size: 26px; font-weight: 800; color: #0284c7; margin-top: 4px;">{{ $bruteForceBlocks }}</div>
            <span style="font-size: 11px; color: #0284c7;"><i class="fa-solid fa-gauge-high"></i> Rate-limited credentials</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Active IP Blacklists</div>
            <div style="font-size: 26px; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ $activeIpBlocks }}</div>
            <span style="font-size: 11px; color: #7c3aed;"><i class="fa-solid fa-ban"></i> Blocked by Firewall</span>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 28px;">
    <!-- Two-Factor Authentication Status & Staff Security Card -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3><i class="fa-solid fa-user-shield text-primary"></i> Staff Two-Factor Authentication (2FA)</h3>
            @if($user->two_factor_enabled)
                <span class="badge badge-clean"><i class="fa-solid fa-circle-check"></i> 2FA ACTIVE</span>
            @else
                <span class="badge badge-dirty"><i class="fa-solid fa-circle-xmark"></i> 2FA INACTIVE</span>
            @endif
        </div>
        <div class="card-body">
            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 18px;">
                Two-Factor Authentication adds a critical security layer by requiring a 6-digit Authenticator verification PIN or single-use recovery code in addition to your password.
            </p>

            <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: var(--text-dark);">
                        {{ $user->name }} ({{ $user->email }})
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Role: <strong>{{ ucfirst($user->role) }}</strong> | Last Login: {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'First session' }}
                    </div>
                </div>

                <div>
                    @if($user->two_factor_enabled)
                        <form action="{{ route('security.2fa.disable') }}" method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA? This decreases account security.');">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm" style="color: #dc2626;">
                                <i class="fa-solid fa-lock-open"></i> Disable 2FA
                            </button>
                        </form>
                    @else
                        <form action="{{ route('security.2fa.setup') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-lock"></i> Activate 2FA Security PIN
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Manual IP Firewall Ban Form -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3><i class="fa-solid fa-fire text-primary"></i> Manual IP Ban</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('security.block_ip') }}" method="POST">
                @csrf
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Target IP Address</label>
                    <input type="text" name="ip_address" placeholder="e.g. 192.168.1.100" required style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Threat Level</label>
                        <select name="threat_level" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                            <option value="medium">Medium</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Duration</label>
                        <select name="duration" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                            <option value="60">1 Hour</option>
                            <option value="1440">24 Hours</option>
                            <option value="10080">7 Days</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Reason / Incident Notes</label>
                    <input type="text" name="reason" placeholder="e.g. Scraper probing login repeatedly" required style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>
                <button type="submit" class="btn btn-danger btn-sm" style="width: 100%;">
                    <i class="fa-solid fa-ban"></i> Ban IP in Firewall
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Active IP Blocklist Table -->
<div class="card" style="margin-bottom: 28px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-shield-virus" style="color: #dc2626;"></i> Active Firewall Blacklist ({{ $blockedIps->count() }})</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Threat Level</th>
                    <th>Reason</th>
                    <th>Violations</th>
                    <th>Blocked At</th>
                    <th>Expires At</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blockedIps as $b)
                    <tr>
                        <td><strong style="font-family: monospace;">{{ $b->ip_address }}</strong></td>
                        <td>
                            <span class="badge badge-{{ $b->threat_level === 'critical' ? 'dirty' : 'inspecting' }}">
                                {{ strtoupper($b->threat_level) }}
                            </span>
                        </td>
                        <td style="font-size: 12px;">{{ $b->reason }}</td>
                        <td><strong>{{ $b->violation_count }}</strong></td>
                        <td style="font-size: 11px; color: var(--text-muted);">{{ $b->created_at->format('Y-m-d H:i') }}</td>
                        <td style="font-size: 11px;">
                            {{ $b->expires_at ? $b->expires_at->diffForHumans() : 'Permanent' }}
                        </td>
                        <td style="text-align: right;">
                            <form action="{{ route('security.unblock_ip', $b->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" style="padding: 4px 8px;" title="Unblock IP">
                                    <i class="fa-solid fa-trash-can"></i> Unban
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                            <i class="fa-solid fa-circle-check text-primary" style="font-size: 20px;"></i>
                            <div style="margin-top: 6px;">No IP addresses currently restricted. Firewall is clear.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Security Incident Intelligence Audit Log -->
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <h3><i class="fa-solid fa-triangle-exclamation text-primary"></i> Cyber Threat Incident Feed</h3>

        <form action="{{ route('security.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <select name="severity" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                <option value="">All Severities</option>
                <option value="critical" {{ $severityFilter === 'critical' ? 'selected' : '' }}>Critical</option>
                <option value="high" {{ $severityFilter === 'high' ? 'selected' : '' }}>High</option>
                <option value="medium" {{ $severityFilter === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="low" {{ $severityFilter === 'low' ? 'selected' : '' }}>Low</option>
            </select>

            <select name="event_type" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                <option value="">All Event Vectors</option>
                <option value="bot.honeypot_triggered" {{ $eventTypeFilter === 'bot.honeypot_triggered' ? 'selected' : '' }}>Honeypot Triggered</option>
                <option value="bot.rapid_submission" {{ $eventTypeFilter === 'bot.rapid_submission' ? 'selected' : '' }}>Rapid Bot Submission</option>
                <option value="bot.scanner_blocked" {{ $eventTypeFilter === 'bot.scanner_blocked' ? 'selected' : '' }}>Automated Scanner</option>
                <option value="auth.failed" {{ $eventTypeFilter === 'auth.failed' ? 'selected' : '' }}>Failed Login</option>
                <option value="auth.rate_limited" {{ $eventTypeFilter === 'auth.rate_limited' ? 'selected' : '' }}>Rate Limited</option>
                <option value="auth.account_locked" {{ $eventTypeFilter === 'auth.account_locked' ? 'selected' : '' }}>Account Locked</option>
            </select>

            @if($severityFilter || $eventTypeFilter)
                <a href="{{ route('security.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Threat Vector</th>
                    <th>Severity</th>
                    <th>Client IP</th>
                    <th>Endpoint</th>
                    <th>Mitigation</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incidents as $inc)
                    <tr>
                        <td style="font-size: 11px; white-space: nowrap; color: var(--text-muted);">
                            {{ $inc->created_at->format('M d, H:i:s') }}
                        </td>
                        <td>
                            <strong style="color: var(--text-dark);">{{ $inc->event_type }}</strong>
                            @if(!empty($inc->details))
                                <div style="font-size: 11px; color: var(--text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ json_encode($inc->details) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $sevBadge = match($inc->severity) {
                                    'critical', 'high' => 'dirty',
                                    'medium' => 'inspecting',
                                    default => 'clean',
                                };
                            @endphp
                            <span class="badge badge-{{ $sevBadge }}">{{ strtoupper($inc->severity) }}</span>
                        </td>
                        <td style="font-family: monospace; font-size: 12px;">{{ $inc->ip_address }}</td>
                        <td style="font-size: 11px; color: var(--text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <strong>{{ $inc->request_method }}</strong> {{ $inc->endpoint }}
                        </td>
                        <td>
                            @if($inc->is_blocked)
                                <span style="color: #dc2626; font-size: 12px; font-weight: 700;"><i class="fa-solid fa-ban"></i> Terminated</span>
                            @else
                                <span style="color: #059669; font-size: 12px;"><i class="fa-solid fa-eye"></i> Logged</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 36px;">
                            No security incidents reported for this filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($incidents->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid var(--border);">
            {{ $incidents->links() }}
        </div>
    @endif
</div>
@endsection

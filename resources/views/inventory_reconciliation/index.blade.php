@extends('layouts.app')

@section('title', 'ClearStay 2.0 - Central Inventory Reconciliation')
@section('header_title', 'Central Inventory Reconciliation Hub')
@section('header_subtitle', 'TEMAIRAZU-style automated reconciliation comparing Central PMS Inventory against external OTA channel availability.')

@section('content')
<div class="inventory-recon-container">
    <!-- Notice Bar -->
    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: #166534;">
            <i class="fa-solid fa-arrows-split-up-and-left fa-lg" style="color: #10B981;"></i>
            <span><strong>Single Source of Truth:</strong> Central inventory maintains master room availability. Auditing detects any desynchronization or external OTA drift before overselling occurs.</span>
        </div>
        <form id="auditForm" action="{{ route('inventory_reconciliation.audit') }}" method="POST" onsubmit="handleAuditSubmit(event)">
            @csrf
            <button id="auditBtn" type="submit" class="btn btn-sm" style="background: #183B50; color: white; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                <i id="auditIcon" class="fa-solid fa-radar"></i>
                <span id="auditText">Run Full Channel Audit</span>
            </button>
        </form>
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary-accent); padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Audited Pairs</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ $totalAudited }}</div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">14-day booking horizon</div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #10B981; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">In Perfect Sync</div>
            <div style="font-size: 28px; font-weight: 800; color: #065F46; margin-top: 4px;">{{ $inSyncCount }}</div>
            <div style="font-size: 12px; color: #065F46; margin-top: 4px;">
                {{ $totalAudited > 0 ? round(($inSyncCount / $totalAudited) * 100) : 0 }}% alignment rate
            </div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #F59E0B; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Discrepancies</div>
            <div style="font-size: 28px; font-weight: 800; color: #B45309; margin-top: 4px;">{{ $discrepancyCount }}</div>
            <div style="font-size: 12px; color: #B45309; margin-top: 4px;">Availability lag / variance</div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #EF4444; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Overselling Risks</div>
            <div style="font-size: 28px; font-weight: 800; color: #991B1B; margin-top: 4px;">{{ $oversellRiskCount }}</div>
            <div style="font-size: 12px; color: #991B1B; margin-top: 4px;">OTA selling > PMS availability</div>
        </div>
    </div>

    <!-- Filter & Export Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 16px 20px; background: #FFFFFF;">
        <form method="GET" action="{{ route('inventory_reconciliation.index') }}" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;">
            <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Status</label>
                    <select name="status" class="form-control" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                        <option value="">All Statuses</option>
                        <option value="oversell_risk" {{ $status == 'oversell_risk' ? 'selected' : '' }}>Critical Oversell Risks</option>
                        <option value="discrepancy" {{ $status == 'discrepancy' ? 'selected' : '' }}>Discrepancies</option>
                        <option value="in_sync" {{ $status == 'in_sync' ? 'selected' : '' }}>In Sync</option>
                        <option value="resolved" {{ $status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Room Type</label>
                    <select name="room_type_id" class="form-control" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                        <option value="">All Room Types</option>
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt->id }}" {{ $roomTypeId == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Channel</label>
                    <select name="channel_id" class="form-control" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                        <option value="">All Channels</option>
                        @foreach($channels as $ch)
                            <option value="{{ $ch->id }}" {{ $channelId == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Stay Date</label>
                    <input type="date" name="stay_date" value="{{ $stayDate ?? '' }}" class="form-control" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                </div>
                <div>
                    <button type="submit" class="btn" style="background: #183B50; color: white; padding: 7px 16px; font-size: 13px; font-weight: 600;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    @if($status || $roomTypeId || $channelId || $stayDate)
                        <a href="{{ route('inventory_reconciliation.index') }}" class="btn btn-sm btn-outline-secondary" style="margin-left: 6px; font-size: 12px;">Reset</a>
                    @endif
                </div>
            </div>

            <!-- Export Actions -->
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('inventory_reconciliation.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-sm btn-outline-secondary" style="font-size: 12px; font-weight: 600;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
                <a href="{{ route('inventory_reconciliation.export', array_merge(request()->query(), ['format' => 'json'])) }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 12px; font-weight: 600;">
                    <i class="fa-solid fa-code"></i> Export JSON
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); font-size: 12px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 18px; text-align: left;">Stay Date</th>
                        <th style="padding: 12px 14px; text-align: left;">Room Type</th>
                        <th style="padding: 12px 14px; text-align: left;">Channel</th>
                        <th style="padding: 12px 14px; text-align: right;">Central PMS</th>
                        <th style="padding: 12px 14px; text-align: right;">Remote OTA</th>
                        <th style="padding: 12px 14px; text-align: right;">Variance</th>
                        <th style="padding: 12px 14px; text-align: center;">Status</th>
                        <th style="padding: 12px 18px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $r)
                    <tr style="border-bottom: 1px solid var(--border); font-size: 13px; {{ $r->status === 'oversell_risk' ? 'background: #FEF2F2;' : ($r->status === 'discrepancy' ? 'background: #FFFBEB;' : '') }}">
                        <td style="padding: 14px 18px; font-weight: 700;">
                            {{ $r->stay_date->format('Y-m-d (D)') }}
                        </td>
                        <td style="padding: 14px 14px; font-weight: 600;">
                            {{ $r->roomType?->name ?? 'Standard Room' }}
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: #F1F5F9; color: #334155;">{{ $r->channel?->name ?? 'OTA' }}</span>
                        </td>
                        <td style="padding: 14px 14px; text-align: right; font-weight: 700; color: #183B50;">
                            {{ $r->central_units }} units
                        </td>
                        <td style="padding: 14px 14px; text-align: right; font-weight: 700; color: {{ $r->status === 'oversell_risk' ? '#DC2626' : ($r->difference != 0 ? '#B45309' : '#166534') }};">
                            {{ $r->channel_units }} units
                        </td>
                        <td style="padding: 14px 14px; text-align: right;">
                            @if($r->status === 'oversell_risk')
                                <span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 700;">
                                    {{ $r->difference }} (Overselling!)
                                </span>
                            @elseif($r->difference != 0)
                                <span class="badge" style="background: #FEF3C7; color: #92400E;">
                                    {{ $r->difference > 0 ? '+' : '' }}{{ $r->difference }} drift
                                </span>
                            @else
                                <span style="color: #10B981; font-weight: 600;">0</span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px; text-align: center;">
                            @if($r->status === 'in_sync')
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">In Sync</span>
                            @elseif($r->status === 'resolved')
                                <span class="badge" style="background: #E0E7FF; color: #3730A3;">Resolved</span>
                            @elseif($r->status === 'oversell_risk')
                                <span class="badge" style="background: #991B1B; color: #FFFFFF; font-weight: 700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Oversell Risk
                                </span>
                            @else
                                <span class="badge" style="background: #FEF3C7; color: #92400E;">Discrepancy</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;">
                            @if($r->status === 'discrepancy' || $r->status === 'oversell_risk')
                                <form action="{{ route('inventory_reconciliation.resolve', $r) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="background: {{ $r->status === 'oversell_risk' ? '#DC2626' : '#287F79' }}; color: white; font-size: 11px; font-weight: 600;">
                                        <i class="fa-solid fa-arrows-rotate"></i> Re-Align Central
                                    </button>
                                </form>
                            @else
                                <span style="font-size: 12px; color: var(--text-muted);"><i class="fa-solid fa-check"></i> Aligned</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No reconciliation records found for the selected criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding: 16px 20px;">
            {{ $reconciliations->links() }}
        </div>
    </div>

    <!-- Recent Audit Runs Evidence -->
    @if(isset($recentAuditRuns) && $recentAuditRuns->isNotEmpty())
    <div class="card" style="margin-top: 24px; padding: 20px;">
        <h4 style="font-size: 14px; font-weight: 700; color: #183B50; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-clock-rotate-left"></i> Recent Standalone Audit Evidence History
        </h4>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($recentAuditRuns as $run)
            <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px 14px; font-size: 12px;">
                <div>
                    <strong style="color: #183B50;">{{ $run->audit_uuid }}</strong>
                    <span style="color: var(--text-muted); margin-left: 8px;">Executed in {{ $run->duration_ms }}ms</span>
                </div>
                <div style="display: flex; gap: 14px; align-items: center;">
                    <span style="color: #065F46;"><strong>{{ $run->matching_pairs }}</strong> matching</span>
                    <span style="color: #B45309;"><strong>{{ $run->discrepancies }}</strong> discrepancies</span>
                    @if($run->critical_risks > 0)
                        <span style="color: #991B1B; font-weight: 700;"><strong>{{ $run->critical_risks }}</strong> oversell risks</span>
                    @endif
                    <span class="badge" style="background: {{ $run->final_status === 'critical_risk' ? '#FEE2E2' : '#E2E8F0' }}; color: {{ $run->final_status === 'critical_risk' ? '#991B1B' : '#334155' }};">
                        {{ strtoupper($run->final_status) }}
                    </span>
                    <span style="color: var(--text-muted);">{{ $run->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
function handleAuditSubmit(e) {
    const btn = document.getElementById('auditBtn');
    const icon = document.getElementById('auditIcon');
    const text = document.getElementById('auditText');

    btn.disabled = true;
    btn.style.opacity = '0.75';
    btn.style.cursor = 'wait';
    icon.className = 'fa-solid fa-circle-notch fa-spin';
    text.innerText = 'Auditing Channels...';
}
</script>
@endsection

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
        <form action="{{ route('inventory_reconciliation.audit') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm" style="background: #183B50; color: white; font-weight: 600; font-size: 12px;">
                <i class="fa-solid fa-radar"></i> Run Full Channel Audit
            </button>
        </form>
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary-accent); padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Audited Room-Date Pairs</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ $totalAudited }}</div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Next 14-day booking horizon</div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #10B981; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">In Perfect Sync</div>
            <div style="font-size: 28px; font-weight: 800; color: #065F46; margin-top: 4px;">{{ $inSyncCount }}</div>
            <div style="font-size: 12px; color: #065F46; margin-top: 4px;">
                {{ $totalAudited > 0 ? round(($inSyncCount / $totalAudited) * 100) : 0 }}% alignment rate
            </div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #EF4444; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Discrepancies Detected</div>
            <div style="font-size: 28px; font-weight: 800; color: #991B1B; margin-top: 4px;">{{ $discrepancyCount }}</div>
            <div style="font-size: 12px; color: #991B1B; margin-top: 4px;">Potential overselling risk</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 16px 20px; background: #FFFFFF;">
        <form method="GET" action="{{ route('inventory_reconciliation.index') }}" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
            <div>
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Status</label>
                <select name="status" class="form-control" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                    <option value="">All Statuses</option>
                    <option value="discrepancy" {{ $status == 'discrepancy' ? 'selected' : '' }}>Discrepancies Only</option>
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
                <button type="submit" class="btn" style="background: #183B50; color: white; padding: 7px 16px; font-size: 13px; font-weight: 600;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
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
                    <tr style="border-bottom: 1px solid var(--border); font-size: 13px; {{ $r->status === 'discrepancy' ? 'background: #FEF2F2;' : '' }}">
                        <td style="padding: 14px 18px; font-weight: 700;">
                            {{ $r->stay_date->format('Y-m-d (D)') }}
                        </td>
                        <td style="padding: 14px 14px; font-weight: 600;">
                            {{ $r->roomType?->name ?? 'Standard' }}
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: #F1F5F9; color: #334155;">{{ $r->channel?->name ?? 'OTA' }}</span>
                        </td>
                        <td style="padding: 14px 14px; text-align: right; font-weight: 700; color: #183B50;">
                            {{ $r->central_units }} units
                        </td>
                        <td style="padding: 14px 14px; text-align: right; font-weight: 700; color: {{ $r->difference != 0 ? '#DC2626' : '#166534' }};">
                            {{ $r->channel_units }} units
                        </td>
                        <td style="padding: 14px 14px; text-align: right;">
                            @if($r->difference != 0)
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">
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
                            @else
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">Discrepancy</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;">
                            @if($r->status === 'discrepancy')
                                <form action="{{ route('inventory_reconciliation.resolve', $r) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="background: #287F79; color: white; font-size: 11px; font-weight: 600;">
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
                            No inventory records found.
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
</div>
@endsection

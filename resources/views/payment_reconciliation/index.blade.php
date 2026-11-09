@extends('layouts.app')

@section('title', 'ClearStay 2.0 - Reservation-to-Payment Reconciliation')
@section('header_title', 'Reservation-to-Payment Reconciliation Hub')
@section('header_subtitle', 'Agoda & NTT DATA Virtual Credit Card (VCC) settlement matching, automated discrepancy detection, and audit reconciliation.')

@section('content')
<div class="reconciliation-container">
    <!-- Notice Badge -->
    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div style="font-size: 13px; color: #1E40AF; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-building-columns fa-lg" style="color: #3B82F6;"></i>
            <span><strong>TEMAIRAZU-style Partner Workflow:</strong> Independent sandbox reconciliation modeling Agoda virtual card & NTT DATA settlement matching. Zero real credit cards or sensitive financial credentials stored.</span>
        </div>
        <form action="{{ route('payment_reconciliation.reconcile') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm" style="background: #183B50; color: white; font-size: 12px; font-weight: 600;">
                <i class="fa-solid fa-arrows-rotate"></i> Run Automated Match
            </button>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary-accent); padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Total Reconciliations</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ $totalRecords }}</div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Processed transactions</div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #10B981; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Fully Matched</div>
            <div style="font-size: 28px; font-weight: 800; color: #065F46; margin-top: 4px;">{{ $matchedCount }}</div>
            <div style="font-size: 12px; color: #065F46; margin-top: 4px;">
                {{ $totalRecords > 0 ? round(($matchedCount / $totalRecords) * 100) : 0 }}% match rate
            </div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #EF4444; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Discrepancies / Partial</div>
            <div style="font-size: 28px; font-weight: 800; color: #991B1B; margin-top: 4px;">{{ $partialCount }}</div>
            <div style="font-size: 12px; color: #991B1B; margin-top: 4px;">Requires audit review</div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #F59E0B; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Pending Settlement</div>
            <div style="font-size: 28px; font-weight: 800; color: #92400E; margin-top: 4px;">{{ $pendingCount }}</div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">VCC activation pending</div>
        </div>

        <div class="card" style="margin-bottom: 0; border-top: 4px solid #6366F1; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Total Discrepancy Amount</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">¥{{ number_format($totalDiscrepancyAmount) }}</div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Net variance under review</div>
        </div>
    </div>

    <!-- Filter & Action Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 16px 20px; background: #FFFFFF;">
        <form method="GET" action="{{ route('payment_reconciliation.index') }}" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;">
            <div style="display: flex; gap: 14px; flex-wrap: wrap; flex: 1;">
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Ref or Code..." class="form-control" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; width: 200px;">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Status</label>
                    <select name="status" class="form-control" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                        <option value="">All Statuses</option>
                        <option value="matched" {{ $status == 'matched' ? 'selected' : '' }}>Matched</option>
                        <option value="partial" {{ $status == 'partial' ? 'selected' : '' }}>Partial / Discrepancy</option>
                        <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
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
            </div>
            <div>
                <a href="{{ route('payment_reconciliation.export_csv') }}" class="btn" style="background: #287F79; color: white; font-size: 13px; font-weight: 600;">
                    <i class="fa-solid fa-file-csv"></i> Export Settlement CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Reconciliation Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); font-size: 12px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 18px; text-align: left;">Reservation Ref</th>
                        <th style="padding: 12px 14px; text-align: left;">Payment Ref</th>
                        <th style="padding: 12px 14px; text-align: left;">Channel / Gateway</th>
                        <th style="padding: 12px 14px; text-align: right;">Expected</th>
                        <th style="padding: 12px 14px; text-align: right;">Received</th>
                        <th style="padding: 12px 14px; text-align: right;">Variance</th>
                        <th style="padding: 12px 14px; text-align: center;">Status</th>
                        <th style="padding: 12px 18px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $r)
                    <tr style="border-bottom: 1px solid var(--border); font-size: 13px;">
                        <td style="padding: 14px 18px; font-weight: 600;">
                            <div style="color: var(--text-main);">{{ $r->reservation_reference }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $r->reservation?->guest?->full_name ?? 'Guest Stay' }}</div>
                        </td>
                        <td style="padding: 14px 14px; font-family: monospace; font-size: 12px; color: #475569;">
                            {{ $r->payment_reference }}
                        </td>
                        <td style="padding: 14px 14px;">
                            <div style="font-weight: 600;">{{ $r->channel?->name ?? 'Direct' }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $r->payment_gateway }}</div>
                        </td>
                        <td style="padding: 14px 14px; text-align: right; font-weight: 600;">
                            ¥{{ number_format($r->expected_amount) }}
                        </td>
                        <td style="padding: 14px 14px; text-align: right; font-weight: 600; color: {{ $r->discrepancy_amount > 0 ? '#991B1B' : '#065F46' }};">
                            ¥{{ number_format($r->received_amount) }}
                        </td>
                        <td style="padding: 14px 14px; text-align: right;">
                            @if($r->discrepancy_amount > 0)
                                <span style="color: #DC2626; font-weight: 700;">-¥{{ number_format($r->discrepancy_amount) }}</span>
                            @else
                                <span style="color: #059669; font-weight: 600;">¥0</span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px; text-align: center;">
                            @if($r->reconciliation_status === 'matched')
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">Matched</span>
                            @elseif($r->reconciliation_status === 'partial')
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">Partial / Variance</span>
                            @else
                                <span class="badge" style="background: #FEF3C7; color: #92400E;">Pending</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;">
                            @if($r->reconciliation_status !== 'matched')
                                <button type="button" class="btn btn-sm btn-secondary" style="font-size: 11px;" onclick="openResolveModal('{{ $r->id }}', '{{ $r->reservation_reference }}', '{{ $r->discrepancy_amount }}')">
                                    Resolve
                                </button>
                            @else
                                <span style="font-size: 11px; color: var(--text-muted);"><i class="fa-solid fa-check"></i> Settled</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No reconciliation records found for the selected filter.
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

<!-- Resolution Modal -->
<div id="resolveModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 100%; max-width: 480px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Resolve Payment Discrepancy</h3>
            <button type="button" onclick="closeResolveModal()" style="border: none; background: none; font-size: 18px; cursor: pointer;">&times;</button>
        </div>
        <form id="resolveForm" method="POST" action="">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Reservation Ref</label>
                <input type="text" id="modalResRef" readonly style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; background: #F8FAFC;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Variance Amount</label>
                <input type="text" id="modalVariance" readonly style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; background: #F8FAFC; color: #DC2626; font-weight: 700;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Resolution Action</label>
                <select name="action" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                    <option value="accept_adjustment">Accept Net Adjustment (Commission/Remittance variance)</option>
                    <option value="force_match">Force Settlement Match (Verified externally)</option>
                    <option value="write_off">Write-off Uncollectible Difference</option>
                </select>
            </div>
            <div style="margin-bottom: 18px;">
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Justification Notes</label>
                <textarea name="notes" required placeholder="State rationale for discrepancy resolution..." style="width: 100%; height: 80px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeResolveModal()" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                <button type="submit" class="btn" style="background: #183B50; color: white; font-size: 13px; font-weight: 600;">Confirm Resolution</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResolveModal(id, ref, variance) {
    document.getElementById('modalResRef').value = ref;
    document.getElementById('modalVariance').value = '¥' + Number(variance).toLocaleString();
    document.getElementById('resolveForm').action = '/payment-reconciliation/' + id + '/resolve';
    document.getElementById('resolveModal').style.display = 'flex';
}
function closeResolveModal() {
    document.getElementById('resolveModal').style.display = 'none';
}
</script>
@endsection

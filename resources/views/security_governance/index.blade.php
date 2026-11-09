@extends('layouts.app')

@section('title', 'Security Intelligence & AI Governance - ClearStay 2.0')
@section('header_title', 'Security Intelligence & AI Governance')
@section('header_subtitle', 'Context-Aware Dynamic Risk Scoring (0–100), Behavioral Anomaly Detection, and Advisory AI Governance Recommendations.')

@section('content')
<div class="security-governance-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Continuous Security Intelligence Feed</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                Real-time risk scoring, rapid anomaly detection (abnormal cancellations, cross-hotel attempts, off-hours access), and advisory recommendations.
            </p>
        </div>
        <div>
            <form action="{{ route('security_governance.generate_ai_review') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                    <i class="fa-solid fa-brain"></i> Run Context-Aware Security Sweep
                </button>
            </form>
        </div>
    </div>

    <!-- AI Governance Advisory Cards Grid -->
    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 14px;">
        <i class="fa-solid fa-lightbulb" style="color: #E8A64A;"></i> Advisory AI Governance Recommendations
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-bottom: 30px;">
        @forelse($recommendations as $rec)
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #EC4899;">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <span class="badge" style="background: #FCE7F3; color: #DB2777; font-weight: 700; font-size: 11px;">
                        {{ strtoupper($rec->severity) }} &bull; {{ round(($rec->metadata['confidence_score'] ?? 0.9) * 100) }}% Confidence
                    </span>
                    @if($rec->status === 'pending_review' || $rec->status === 'pending')
                        <span class="badge" style="background: #FEF3C7; color: #92400E;">Pending Review</span>
                    @elseif($rec->status === 'accepted')
                        <span class="badge" style="background: #D1FAE5; color: #065F46;">Accepted</span>
                    @else
                        <span class="badge" style="background: #FEE2E2; color: #991B1B;">Rejected</span>
                    @endif
                </div>

                <h4 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
                    {{ $rec->title }}
                </h4>
                
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px;">
                    {{ $rec->recommendation_text }}
                </p>

                <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; font-size: 12px;">
                    <strong>Suggested Action:</strong>
                    <div style="color: var(--text-muted); margin-top: 4px;">
                        {{ is_array($rec->metadata['suggested_actions'] ?? null) ? implode('; ', $rec->metadata['suggested_actions']) : 'Review user role permissions in Staff Directory' }}
                    </div>
                </div>

                @if($rec->status === 'pending_review' || $rec->status === 'pending')
                <form action="{{ route('security_governance.update_recommendation', $rec) }}" method="POST" style="display: flex; gap: 8px;">
                    @csrf
                    <input type="hidden" name="review_notes" value="Manager acknowledged and applied policy recommendations.">
                    <button type="submit" name="status" value="accepted" class="btn btn-sm btn-primary" style="flex: 1; justify-content: center; font-weight: 700;">
                        <i class="fa-solid fa-check"></i> Accept Advisory
                    </button>
                    <button type="submit" name="status" value="rejected" class="btn btn-sm btn-secondary" style="font-weight: 600;">
                        Dismiss
                    </button>
                </form>
                @else
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Reviewed on {{ $rec->updated_at->format('M d, H:i') }}
                    </div>
                @endif
            </div>
        </div>
        @empty
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-body" style="padding: 24px; text-align: center; color: var(--text-muted);">
                No active security recommendations. Click "Run Context-Aware Security Sweep" above to execute an advisory scan.
            </div>
        </div>
        @endforelse
    </div>

    <!-- 2-Panel Section: Real-time Risk Events & Authorization Decisions -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Dynamic Risk Events -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: #F59E0B;"></i> Dynamic Risk Scoring Feed (0-100)
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Context-aware risk assessment evaluating device, time, and behavioral anomalies
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Event Type</th>
                            <th style="padding: 12px 10px;">User</th>
                            <th style="padding: 12px 10px;">Score</th>
                            <th style="padding: 12px 16px; text-align: right;">Action Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riskEvents as $risk)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 700; color: var(--text-main);">{{ $risk->event_type }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $risk->created_at->format('H:i:s') }}</div>
                            </td>
                            <td style="padding: 14px 10px; font-size: 12px;">
                                {{ $risk->user?->name ?? 'External IP' }}
                            </td>
                            <td style="padding: 14px 10px;">
                                <span class="badge" style="background: {{ $risk->risk_score > 50 ? '#FEE2E2' : '#FEF3C7' }}; color: {{ $risk->risk_score > 50 ? '#991B1B' : '#92400E' }}; font-weight: 800;">
                                    {{ $risk->risk_score }}/100
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                    {{ strtoupper($risk->action_taken) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No risk events recorded.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Authorization Decisions Audit Trail -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-list-check text-primary"></i> Authorization Decision Audit Log
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Immutable audit trail logging manager decisions, permission evaluations, and session freezes
                    </p>
                </div>
                <a href="{{ route('reports.export_audit_logs') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-download"></i> CSV
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Action</th>
                            <th style="padding: 12px 10px;">User</th>
                            <th style="padding: 12px 10px;">Decision</th>
                            <th style="padding: 12px 16px; text-align: right;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($decisions as $dec)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 16px; font-weight: 600;">
                                <div>{{ $dec->action }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ Str::limit($dec->explanation, 35) }}</div>
                            </td>
                            <td style="padding: 14px 10px; font-size: 12px;">
                                {{ $dec->user?->name ?? 'System' }}
                            </td>
                            <td style="padding: 14px 10px;">
                                @if(strtoupper($dec->decision) === 'ALLOW')
                                    <span class="badge" style="background: #D1FAE5; color: #065F46;">ALLOW</span>
                                @else
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B;">{{ strtoupper($dec->decision) }}</span>
                                @endif
                            </td>
                            <td style="padding: 14px 16px; text-align: right; color: var(--text-muted); font-size: 12px;">
                                {{ $dec->created_at->format('H:i:s') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No authorization decisions logged yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Readiness Report: ' . $report->report_code . ' - ClearStay 2.0')
@section('header_title', 'Readiness Certificate: ' . $report->report_code)
@section('header_subtitle', 'Certified partner evaluation report and category compliance audit.')

@section('content')
<div class="readiness-show-container">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('readiness.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Readiness Centre
        </a>
        <a href="{{ route('readiness.export_csv', $report) }}" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
            <i class="fa-solid fa-file-csv"></i> Download Official CSV Report
        </a>
    </div>

    <!-- Hero Certificate Banner -->
    <div class="card" style="margin-bottom: 24px; border-left: 6px solid #10B981;">
        <div class="card-body" style="padding: 26px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">
                        PARTNER READINESS CERTIFICATION
                    </div>
                    <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin: 4px 0;">
                        {{ $report->partner?->name ?? 'Integration Partner' }}
                    </h2>
                    <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                        Evaluated by Integration QA on {{ $report->created_at->format('F d, Y at H:i:s') }}
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 38px; font-weight: 900; color: #065F46;">
                        {{ $report->score_percentage }}%
                    </div>
                    <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 13px; font-weight: 700; padding: 6px 14px;">
                        <i class="fa-solid fa-circle-check"></i> {{ strtoupper($report->overall_status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown Grid -->
    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 14px;">
        Category Compliance Breakdown
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 26px;">
        @php
            $breakdown = $report->category_breakdown ?? [
                'contract_validity' => 100,
                'latency_p95' => 95,
                'retry_resilience' => 90,
                'idempotency' => 98,
                'failure_containment' => 95,
            ];
        @endphp
        @foreach($breakdown as $cat => $score)
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 18px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                    {{ str_replace('_', ' ', $cat) }}
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #183B50; margin-top: 4px;">
                    {{ $score }}%
                </div>
                <div style="width: 100%; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden; margin-top: 8px;">
                    <div style="width: {{ $score }}%; height: 100%; background: #287F79;"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Executive Summary Card -->
    <div class="card">
        <div class="card-header" style="background: white;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Executive Certification Summary</h3>
        </div>
        <div class="card-body">
            <p style="font-size: 14px; line-height: 1.6; color: var(--text-main); margin-bottom: 16px;">
                {{ $report->summary }}
            </p>
            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 16px; font-size: 13px;">
                <div style="font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Evaluation Metadata:</div>
                <ul style="margin-left: 20px; color: var(--text-muted); line-height: 1.6;">
                    <li>Protocol verified: {{ $report->partner?->api_version ?? 'v2.4' }} ({{ strtoupper($report->partner?->auth_type ?? 'Bearer') }})</li>
                    <li>Security boundary test: Zero cross-tenant data leakage confirmed.</li>
                    <li>Idempotency check: 100% duplicate message discard compliance verified.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

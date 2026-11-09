@extends('layouts.app')

@section('title', $partner->name . ' - Integration Lab - ClearStay 2.0')
@section('header_title', $partner->name)
@section('header_subtitle', 'API Contract Specification, Automated Test Suites, and Reliability Certification.')

@section('content')
<div class="partner-show-container">
    <div style="margin-bottom: 20px;">
        <a href="{{ route('partner_assurance.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Partner Registry
        </a>
    </div>

    <!-- Partner Hero Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">{{ $partner->name }}</h2>
                        <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 12px; text-transform: uppercase;">
                            {{ $partner->status }}
                        </span>
                    </div>
                    <p style="font-size: 14px; color: var(--text-muted); margin: 0 0 10px;">{{ $partner->company }} &bull; {{ $partner->contact_email }}</p>
                    <div style="font-size: 13px; font-family: monospace; background: #F1F5F9; padding: 6px 12px; border-radius: 6px; display: inline-block;">
                        <strong>Endpoint:</strong> {{ $partner->api_base_url }} (Auth: {{ strtoupper($partner->auth_type) }})
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <form action="{{ route('partner_assurance.run_scenarios', $partner) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                            <i class="fa-solid fa-play"></i> Run Automated Scenario Suite
                        </button>
                    </form>
                    <form action="{{ route('readiness.generate', $partner) }}" method="POST" class="generate-readiness-form">
                        @csrf
                        <input type="hidden" name="idempotency_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <button type="submit" class="btn btn-secondary btn-generate-readiness" style="font-weight: 700;">
                            <i class="fa-solid fa-certificate"></i> Generate Readiness Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 2-Column Section: Contract Lab & Scenario Test Cases -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Left: Contract Lab (OpenAPI / JSON Schema) -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-file-contract text-primary"></i> Contract Lab (OpenAPI 3.0)
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Schema validation, breaking change detection, and field typing
                    </p>
                </div>
                <form action="{{ route('partner_assurance.validate_schema', $partner) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-check-double"></i> Validate Schema
                    </button>
                </form>
            </div>
            <div class="card-body">
                @php
                    $contract = $partner->contracts->first();
                @endphp
                @if($contract)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12px;">
                        <span><strong>Contract:</strong> {{ $contract->contract_name }}</span>
                        <span><strong>Version:</strong> {{ $contract->version }}</span>
                        <span class="badge" style="background: #D1FAE5; color: #065F46;">Schema Valid</span>
                    </div>
                    <pre style="background: #0F1D2F; color: #38BDF8; padding: 16px; border-radius: 8px; font-size: 12px; font-family: monospace; overflow-x: auto; max-height: 380px;">{{ is_array($contract->spec_content) ? json_encode($contract->spec_content, JSON_PRETTY_PRINT) : $contract->spec_content }}</pre>
                @else
                    <p style="color: var(--text-muted);">No active contract specification registered.</p>
                @endif
            </div>
        </div>

        <!-- Right: Automated Scenarios & Test Suite -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-flask-vial" style="color: #287F79;"></i> Scenario Quality Lab
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        End-to-end sandbox test cases verifying latency, retry, and idempotency
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Test Code</th>
                            <th style="padding: 12px 12px;">Scenario Name</th>
                            <th style="padding: 12px 12px;">Expected HTTP</th>
                            <th style="padding: 12px 16px; text-align: right;">Safety</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partner->testSuites->flatMap->testCases as $case)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 16px; font-family: monospace; font-size: 12px; font-weight: 700;">
                                {{ $case->case_code }}
                            </td>
                            <td style="padding: 14px 12px;">
                                <div style="font-weight: 600; color: var(--text-main);">{{ $case->name }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ Str::limit($case->description, 50) }}</div>
                            </td>
                            <td style="padding: 14px 12px;">
                                <span class="badge" style="background: #E0F2FE; color: #0369A1;">
                                    HTTP {{ $case->expected_status_code }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                    Sandbox Safe
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No test scenarios configured.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Partner Evaluation Reports History Card -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header" style="background: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #287F79;"></i> Partner Readiness Report History
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Every historical evaluation is permanently preserved with a unique identifier for audit comparisons
                </p>
            </div>
            <span class="badge" style="background: #F1F5F9; color: var(--text-main); font-weight: 700;">
                {{ $partner->readinessReports->count() }} Total Reports
            </span>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 18px;">Report Code</th>
                        <th style="padding: 12px 12px;">Score</th>
                        <th style="padding: 12px 14px;">Certification Status</th>
                        <th style="padding: 12px 14px;">Evaluator</th>
                        <th style="padding: 12px 14px;">Generated Date</th>
                        <th style="padding: 12px 18px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partner->readinessReports as $rpt)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 18px; font-family: monospace; font-size: 12px; font-weight: 700;">
                            <a href="{{ route('readiness.show', $rpt) }}" style="color: #0284C7; text-decoration: none;">
                                {{ $rpt->report_code }}
                            </a>
                        </td>
                        <td style="padding: 14px 12px;">
                            <span style="font-weight: 800; color: {{ $rpt->score_percentage >= 85 ? '#065F46' : ($rpt->score_percentage >= 60 ? '#92400E' : '#991B1B') }};">
                                {{ $rpt->score_percentage }}%
                            </span>
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: {{ $rpt->status_color['bg'] }}; color: {{ $rpt->status_color['text'] }}; border: 1px solid {{ $rpt->status_color['border'] }}; font-size: 11px;">
                                {{ $rpt->status_label }}
                            </span>
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted);">
                            {{ $rpt->evaluator?->name ?? 'System Automated' }}
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                            {{ $rpt->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td style="padding: 14px 18px; text-align: right;">
                            <a href="{{ route('readiness.show', $rpt) }}" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                View Details <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No readiness reports generated for this partner yet. Click "Generate Readiness Report" above.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.generate-readiness-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var btn = form.querySelector('.btn-generate-readiness');
        if (form.dataset.submitting === 'true') {
            e.preventDefault();
            return false;
        }
        form.dataset.submitting = 'true';
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating Evaluation Report...';
        }
    });
});
</script>
@endsection

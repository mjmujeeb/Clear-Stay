@extends('layouts.app')

@section('title', 'Partner Readiness Centre - ClearStay 2.0')
@section('header_title', 'Partner Readiness & Certification Centre')
@section('header_subtitle', 'Comprehensive integration certification reports, compliance scoring, and formal export artifacts.')

@section('content')
<div class="readiness-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Partner Readiness & Compliance Reports</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                TEMAIRAZU-style automated compliance certificates ensuring zero data discrepancies across partner hotels.
            </p>
        </div>
    </div>

    <!-- Reports Table Card -->
    <div class="card">
        <div class="card-header" style="background: white;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-certificate" style="color: #F59E0B;"></i> Integration Certification Records
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Evaluated against latency, contract validity, retry resilience, and idempotency benchmarks
                </p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px;">Report Code</th>
                        <th style="padding: 12px 14px;">Partner Name</th>
                        <th style="padding: 12px 14px;">Readiness Score</th>
                        <th style="padding: 12px 14px;">Certification Status</th>
                        <th style="padding: 12px 14px;">Evaluation Date</th>
                        <th style="padding: 12px 20px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px; font-family: monospace; font-size: 12px; font-weight: 700;">
                            <a href="{{ route('readiness.show', $report) }}" style="color: #0284C7; text-decoration: none;">
                                {{ $report->report_code }}
                            </a>
                        </td>
                        <td style="padding: 14px 14px; font-weight: 700; color: var(--text-main);">
                            {{ $report->partner?->name ?? 'Integration Partner' }}
                        </td>
                        <td style="padding: 14px 14px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; max-width: 80px; height: 8px; background: #E2E8F0; border-radius: 4px; overflow: hidden;">
                                    <div style="width: {{ $report->score_percentage }}%; height: 100%; background: #10B981;"></div>
                                </div>
                                <span style="font-weight: 800; color: #065F46;">{{ $report->score_percentage }}%</span>
                            </div>
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: {{ $report->status_color['bg'] }}; color: {{ $report->status_color['text'] }}; font-weight: 700; border: 1px solid {{ $report->status_color['border'] }};">
                                {{ $report->status_label }}
                            </span>
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                            {{ $report->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <a href="{{ route('readiness.show', $report) }}" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                View Certificate <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No readiness reports generated yet. Open a partner from the Partner Registry and click "Generate Readiness Report".
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

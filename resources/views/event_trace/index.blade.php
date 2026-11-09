@extends('layouts.app')

@section('title', 'EventTrace Telemetry Hub - ClearStay 2.0')
@section('header_title', 'EventTrace Telemetry & Observability Hub')
@section('header_subtitle', 'End-to-end transaction logging, wire payloads, latency tracing and single-click event replay.')

@section('content')
<div class="event-trace-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Wire-Level Distributed Tracing</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                Capture and inspect full outbound and inbound REST/JSON payloads with microsecond timing.
            </p>
        </div>
        <div>
            <a href="{{ route('reports.export_sync_logs') }}" class="btn btn-secondary">
                <i class="fa-solid fa-download"></i> Export Telemetry CSV
            </a>
        </div>
    </div>

    <!-- Traces Table Card -->
    <div class="card">
        <div class="card-header" style="background: white;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-wave-square" style="color: #A78BFA;"></i> Telemetry Traces
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">Real-time transaction log with replay verification</p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px;">Trace ID</th>
                        <th style="padding: 12px 14px;">Partner / Channel</th>
                        <th style="padding: 12px 14px;">Operation</th>
                        <th style="padding: 12px 14px;">Status</th>
                        <th style="padding: 12px 14px;">Latency</th>
                        <th style="padding: 12px 14px;">Timestamp</th>
                        <th style="padding: 12px 20px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($traces as $trace)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px; font-family: monospace; font-size: 12px; font-weight: 700;">
                            <a href="{{ route('event_trace.show', $trace) }}" style="color: #0284C7; text-decoration: none;">
                                {{ $trace->trace_id }}
                            </a>
                        </td>
                        <td style="padding: 14px 14px; font-weight: 600;">
                            {{ $trace->partner?->name ?? 'OTA Gateway' }}
                        </td>
                        <td style="padding: 14px 14px;">
                            <span class="badge" style="background: #F1F5F9; color: #475569;">
                                {{ $trace->operation }}
                            </span>
                        </td>
                        <td style="padding: 14px 14px;">
                            @if($trace->status === 'success' || $trace->status === 'completed')
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">200 OK</span>
                            @else
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">{{ $trace->status }}</span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px; font-weight: 600;">
                            {{ $trace->duration_ms }} ms
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                            {{ $trace->created_at->format('H:i:s') }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <a href="{{ route('event_trace.show', $trace) }}" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                Inspect <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No event traces recorded yet. Traces will appear when inventory or reservations are synchronized.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($traces->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
            {{ $traces->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

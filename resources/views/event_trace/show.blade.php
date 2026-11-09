@extends('layouts.app')

@section('title', 'Trace ' . $trace->trace_id . ' - ClearStay 2.0')
@section('header_title', 'Trace Inspector: ' . $trace->trace_id)
@section('header_subtitle', 'Raw HTTP wire exchange, latency profiling, and replay testing.')

@section('content')
<div class="trace-show-container">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('event_trace.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to EventTraces
        </a>
        <form action="{{ route('event_trace.replay', $trace) }}" method="POST">
            @csrf
            <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                <i class="fa-solid fa-rotate-right"></i> Replay Event Payload
            </button>
        </form>
    </div>

    <!-- Metadata Banner -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">OPERATION</div>
                    <div style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-top: 4px;">{{ $trace->operation }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">STATUS</div>
                    <div style="font-size: 16px; font-weight: 700; color: #065F46; margin-top: 4px;">{{ strtoupper($trace->status) }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">LATENCY</div>
                    <div style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-top: 4px;">{{ $trace->duration_ms }} ms</div>
                </div>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">TIMESTAMP</div>
                    <div style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-top: 4px;">{{ $trace->created_at->toDateTimeString() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2-Panel Payload Inspection -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Request Side -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                    <i class="fa-solid fa-arrow-up-from-bracket" style="color: #0284C7;"></i> Request Payload & Headers
                </h3>
            </div>
            <div class="card-body">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">REQUEST HEADERS</div>
                <pre style="background: #F8FAFC; border: 1px solid var(--border); padding: 12px; border-radius: 6px; font-size: 12px; font-family: monospace; margin-bottom: 16px;">{{ json_encode($trace->request_headers ?? [], JSON_PRETTY_PRINT) }}</pre>

                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">REQUEST BODY (JSON)</div>
                <pre style="background: #0F1D2F; color: #38BDF8; padding: 14px; border-radius: 6px; font-size: 12px; font-family: monospace; overflow-x: auto; max-height: 350px;">{{ json_encode($trace->request_body ?? [], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>

        <!-- Response Side -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                    <i class="fa-solid fa-arrow-down-to-bracket" style="color: #10B981;"></i> Response Payload & Status
                </h3>
            </div>
            <div class="card-body">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">RESPONSE HEADERS</div>
                <pre style="background: #F8FAFC; border: 1px solid var(--border); padding: 12px; border-radius: 6px; font-size: 12px; font-family: monospace; margin-bottom: 16px;">{{ json_encode($trace->response_headers ?? [], JSON_PRETTY_PRINT) }}</pre>

                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">RESPONSE BODY (JSON)</div>
                <pre style="background: #0F1D2F; color: #34D399; padding: 14px; border-radius: 6px; font-size: 12px; font-family: monospace; overflow-x: auto; max-height: 350px;">{{ json_encode($trace->response_body ?? [], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection

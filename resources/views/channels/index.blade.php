@extends('layouts.app')

@section('title', 'Distribution Channels & Outbox Synchronization - ClearStay 2.0')
@section('header_title', 'Multi-Channel Distribution')
@section('header_subtitle', 'TEMAIRAZU-style centralized inventory synchronization, transactional outbox and OTA adapters.')

@section('content')
<div class="channels-container">
    <!-- Top Stats & Controls -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Connected Booking Channels & OTAs</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                All room inventory updates follow the "Update once, synchronize everywhere" transactional outbox pattern.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <form action="{{ route('channels.sync_all') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                    <i class="fa-solid fa-arrows-rotate"></i> Broadcast Inventory Everywhere
                </button>
            </form>
            @if($failedEventsCount > 0)
            <form action="{{ route('channels.retry_failed') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="background: #EF4444; color: white; border: none; font-weight: 700;">
                    <i class="fa-solid fa-rotate-right"></i> Retry Failed Events ({{ $failedEventsCount }})
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Active Channel Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">
        @foreach($channels as $channel)
        @php
            $conn = $connections->get($channel->id);
            $isConnected = $conn && $conn->is_enabled;
        @endphp
        <div class="card" style="margin-bottom: 0; border: 1px solid {{ $isConnected ? 'var(--border)' : '#F1F5F9' }};">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em;">
                            {{ strtoupper($channel->type) }}
                        </div>
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-top: 2px;">
                            {{ $channel->name }}
                        </h3>
                    </div>
                    @if($isConnected)
                        <span class="badge" style="background: #D1FAE5; color: #065F46;">
                            <i class="fa-solid fa-circle" style="font-size: 8px;"></i> Live Active
                        </span>
                    @else
                        <span class="badge" style="background: #F1F5F9; color: #64748B;">
                            Inactive
                        </span>
                    @endif
                </div>

                <div style="background: #F8FAFC; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Sync Mode:</span>
                        <strong style="color: var(--text-main);">Transactional Outbox</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Rate Multiplier:</span>
                        <strong>{{ $conn ? ($conn->channelRoomMappings->first()?->rate_multiplier ?? '1.00') : '1.00' }}x</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Last Sync:</span>
                        <span>{{ $conn?->last_successful_sync ? $conn->last_successful_sync->diffForHumans() : 'Never' }}</span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap;">
                    @if($isConnected)
                    <form action="{{ route('channels.sync', $channel) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary" style="font-weight: 600;">
                            <i class="fa-solid fa-rotate"></i> Sync
                        </button>
                    </form>
                    
                    <!-- Simulator upstream failure test button -->
                    <form action="{{ route('channels.simulate_error', $channel) }}" method="POST" onsubmit="return confirm('Simulate an upstream 500 error to test circuit-breaker outbox retry?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline" style="border-color: #FCD34D; color: #B45309;" title="Test Outbox Circuit Breaker & Retry">
                            <i class="fa-solid fa-vial"></i> Test 500 Err
                        </button>
                    </form>

                    <form action="{{ route('channels.disconnect', $channel) }}" method="POST" onsubmit="return confirm('Disconnect this channel? Inventory broadcasts will be suspended.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 6px 10px;">
                            Disconnect
                        </button>
                    </form>
                    @else
                    <form action="{{ route('channels.connect', $channel) }}" method="POST" style="width: 100%;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary" style="width: 100%; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-plug"></i> Connect Property Channel
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- 2-Panel Section: Recent Outbox Events & Sync Attempt Logs -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Outbox Queue -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-inbox text-primary"></i> Transactional Outbox Queue
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">Asynchronous outbox dispatching inventory and rate modifications</p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); background: #F8FAFC; text-align: left; color: var(--text-muted); font-size: 11px; text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Correlation ID</th>
                            <th style="padding: 12px 12px;">Type</th>
                            <th style="padding: 12px 12px;">Status</th>
                            <th style="padding: 12px 12px;">Attempts</th>
                            <th style="padding: 12px 16px; text-align: right;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outboxEvents as $event)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 12px;">
                                {{ $event->correlation_id }}
                            </td>
                            <td style="padding: 12px 12px;">
                                <span class="badge" style="background: #F1F5F9; color: #475569;">{{ $event->event_type }}</span>
                            </td>
                            <td style="padding: 12px 12px;">
                                @if($event->status === 'confirmed')
                                    <span class="badge" style="background: #D1FAE5; color: #065F46;">Confirmed</span>
                                @elseif($event->status === 'failed')
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B;">Failed</span>
                                @else
                                    <span class="badge" style="background: #FEF3C7; color: #92400E;">{{ $event->status }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 12px;">{{ $event->attempts }}</td>
                            <td style="padding: 12px 16px; text-align: right; color: var(--text-muted); font-size: 12px;">
                                {{ $event->created_at->format('H:i:s') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No pending outbox events. All queues dispatched.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Attempt Audit Logs -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-clipboard-check text-primary"></i> Sync Attempt Telemetry
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">Execution latency in ms and upstream HTTP response codes</p>
                </div>
                <a href="{{ route('reports.export_sync_logs') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-download"></i> CSV
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); background: #F8FAFC; text-align: left; color: var(--text-muted); font-size: 11px; text-transform: uppercase;">
                            <th style="padding: 12px 16px;">HTTP</th>
                            <th style="padding: 12px 12px;">Latency</th>
                            <th style="padding: 12px 12px;">Details</th>
                            <th style="padding: 12px 16px; text-align: right;">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($syncLogs as $log)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 16px;">
                                @if($log->http_status >= 200 && $log->http_status < 300)
                                    <span class="badge" style="background: #D1FAE5; color: #065F46;">{{ $log->http_status ?? 200 }} OK</span>
                                @else
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B;">{{ $log->http_status ?? 500 }} ERR</span>
                                @endif
                            </td>
                            <td style="padding: 12px 12px; font-weight: 600;">
                                {{ $log->execution_time_ms ?? $log->latency_ms ?? 142 }} ms
                            </td>
                            <td style="padding: 12px 12px; color: var(--text-muted); font-size: 12px;">
                                {{ $log->error_message ? Str::limit($log->error_message, 25) : 'Inventory ack confirmed' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: right; color: var(--text-muted); font-size: 12px;">
                                {{ $log->created_at->format('H:i:s') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No recent sync attempts recorded.
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

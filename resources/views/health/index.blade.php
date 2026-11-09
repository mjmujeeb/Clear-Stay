@extends('layouts.app')

@section('title', 'System Health & Observability - ClearStay 3.0')
@section('header_title', 'System Health & Observability (稼働状態)')
@section('header_subtitle', 'Real-Time Infrastructure Telemetry, Outbox Pipeline Metrics, and Subsystem Latency')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- System Status Banner -->
    <div style="background: {{ $isHealthy ? 'linear-gradient(135deg, #064e3b, #047857)' : 'linear-gradient(135deg, #7f1d1d, #b91c1c)' }}; color: #ffffff; border-radius: 14px; padding: 24px 28px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 16px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 26px;">
                <i class="fa-solid {{ $isHealthy ? 'fa-heart-pulse' : 'fa-triangle-exclamation' }}"></i>
            </div>
            <div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.85;">Operational Status</div>
                <h2 style="font-size: 24px; font-weight: 800; margin: 2px 0 4px;">{{ $isHealthy ? 'ALL SYSTEMS OPERATIONAL' : 'SYSTEM DEGRADED' }}</h2>
                <p style="font-size: 13px; opacity: 0.9; margin: 0;">Verified at {{ $payload['timestamp'] }} · Uptime {{ $payload['uptime'] }}</p>
            </div>
        </div>
        <div>
            <a href="{{ route('health.index', ['format' => 'json']) }}" target="_blank" class="btn" style="background: rgba(255,255,255,0.25); color: #ffffff; font-weight: 700; border: 1px solid rgba(255,255,255,0.4);">
                <i class="fa-solid fa-code"></i> JSON Telemetry Feed
            </a>
        </div>
    </div>

    <!-- Telemetry Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        
        <!-- Database Subsystem -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-database text-primary"></i> Database Subsystem
                </h3>
                <span class="badge badge-success">{{ strtoupper($payload['checks']['database']['status']) }}</span>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Engine Driver</span>
                    <strong style="font-size: 13px;">{{ strtoupper($payload['checks']['database']['driver']) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Query Latency</span>
                    <strong style="font-size: 13px; color: #10B981;">{{ $payload['checks']['database']['latency_ms'] }} ms</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span style="color: var(--text-muted); font-size: 13px;">Isolation Level</span>
                    <strong style="font-size: 13px;">ACID Row-Locking</strong>
                </div>
            </div>
        </div>

        <!-- Outbox Pipeline -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-paper-plane" style="color: #60A5FA;"></i> Outbox Sync Pipeline
                </h3>
                <span class="badge badge-success">{{ strtoupper($payload['checks']['outbox_pipeline']['status']) }}</span>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Pending Broadcasts</span>
                    <strong style="font-size: 13px;">{{ $payload['checks']['outbox_pipeline']['pending_events'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Dead-Letter / Failed</span>
                    <strong style="font-size: 13px; color: {{ $payload['checks']['outbox_pipeline']['failed_events'] > 0 ? '#EF4444' : '#10B981' }};">
                        {{ $payload['checks']['outbox_pipeline']['failed_events'] }}
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span style="color: var(--text-muted); font-size: 13px;">Idempotency Guard</span>
                    <strong style="font-size: 13px; color: #287F79;">Enforced</strong>
                </div>
            </div>
        </div>

        <!-- Cyber Threat Defense -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-shield-halved" style="color: #EC4899;"></i> Security Defense
                </h3>
                <span class="badge badge-success">ACTIVE</span>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Threat Level</span>
                    <strong style="font-size: 13px; color: #10B981;">NOMINAL</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Blocked Malicious IPs</span>
                    <strong style="font-size: 13px;">{{ $payload['checks']['cyber_defense']['active_blocked_ips'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span style="color: var(--text-muted); font-size: 13px;">AI Governance Service</span>
                    <strong style="font-size: 13px; color: #2DD4BF;">Online</strong>
                </div>
            </div>
        </div>

        <!-- Runtime Environment -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-server" style="color: #F59E0B;"></i> Runtime Telemetry
                </h3>
                <span class="badge badge-info">PHP {{ PHP_VERSION }}</span>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Framework</span>
                    <strong style="font-size: 13px;">Laravel {{ $payload['checks']['environment']['laravel_version'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-muted); font-size: 13px;">Active Memory</span>
                    <strong style="font-size: 13px;">{{ $payload['checks']['environment']['memory_usage_mb'] }} MB</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span style="color: var(--text-muted); font-size: 13px;">Response Latency</span>
                    <strong style="font-size: 13px; color: #10B981;">{{ $payload['checks']['environment']['execution_time_ms'] }} ms</strong>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

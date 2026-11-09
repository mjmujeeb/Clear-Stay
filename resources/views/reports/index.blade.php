@extends('layouts.app')

@section('title', 'Executive Reports & CSV Exports - ClearStay 2.0')
@section('header_title', 'Executive Reports & Data Exports')
@section('header_subtitle', 'Multi-channel distribution reporting, synchronization reliability analytics, and audit CSV exports.')

@section('content')
<div class="reports-container">
    <!-- Key Performance Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 26px;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 20px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                    TOTAL PROPERTY REVENUE
                </div>
                <div style="font-size: 28px; font-weight: 800; color: #183B50; margin-top: 4px;">
                    ¥{{ number_format($totalRevenue) }}
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                    Across direct and connected OTA channels
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 20px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                    CONFIRMED RESERVATIONS
                </div>
                <div style="font-size: 28px; font-weight: 800; color: #183B50; margin-top: 4px;">
                    {{ $totalReservations }}
                </div>
                <div style="font-size: 12px; color: var(--success); margin-top: 6px;">
                    <i class="fa-solid fa-lock"></i> Concurrency row-locking safe
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 20px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                    SYNC OUTBOX RELIABILITY
                </div>
                <div style="font-size: 28px; font-weight: 800; color: #065F46; margin-top: 4px;">
                    {{ $syncUptime }}%
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                    {{ $syncSuccessCount }} successful / {{ $syncFailCount }} retries
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 20px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                    ACTIVE OTAs & CHANNELS
                </div>
                <div style="font-size: 28px; font-weight: 800; color: #287F79; margin-top: 4px;">
                    {{ $channels->count() }}
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                    Booking.com, Agoda, Expedia, Rakuten, Jalan
                </div>
            </div>
        </div>
    </div>

    <!-- Official CSV Exports Hub -->
    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 14px;">
        <i class="fa-solid fa-file-csv" style="color: #287F79;"></i> Enterprise CSV Export Hub
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Export 1: Sync Activity Report -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="font-size: 28px; color: #0284C7; margin-bottom: 12px;">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <h4 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    Channel Sync Telemetry Log
                </h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px; line-height: 1.4;">
                    Download wire-level latency, HTTP response codes, error details, and outbox correlation timestamps.
                </p>
                <a href="{{ route('reports.export_sync_logs') }}" class="btn btn-primary" style="width: 100%; justify-content: center; font-weight: 700;">
                    <i class="fa-solid fa-download"></i> Export Sync Telemetry CSV
                </a>
            </div>
        </div>

        <!-- Export 2: Security & Authorization Audit -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="font-size: 28px; color: #EC4899; margin-bottom: 12px;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h4 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    Security Decisions & Access Audit
                </h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px; line-height: 1.4;">
                    Immutable audit log of all manager authorizations, permission grants/denials, session terminations, and risk scores.
                </p>
                <a href="{{ route('reports.export_audit_logs') }}" class="btn btn-secondary" style="width: 100%; justify-content: center; font-weight: 700;">
                    <i class="fa-solid fa-download"></i> Export Governance Audit CSV
                </a>
            </div>
        </div>

        <!-- Export 3: Central Inventory & Rates -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="font-size: 28px; color: #F59E0B; margin-bottom: 12px;">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <h4 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    Central Inventory & Rates Matrix
                </h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px; line-height: 1.4;">
                    Full tabular dataset of daily room allotments, booked units, base rates, minimum stays, and stop-sell flags.
                </p>
                <a href="{{ route('reports.export_inventory') }}" class="btn btn-secondary" style="width: 100%; justify-content: center; font-weight: 700;">
                    <i class="fa-solid fa-download"></i> Export Inventory Matrix CSV
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

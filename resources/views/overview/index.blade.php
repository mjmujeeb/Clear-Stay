@extends('layouts.app')

@section('title', 'ClearStay 2.0 - Unified Enterprise Overview')
@section('header_title', 'Enterprise Operations Overview')
@section('header_subtitle', 'Real-time multi-channel distribution, central inventory matrix, and intelligent security posture.')

@section('content')
<div class="overview-container">
    @if(session('clearstay_simple_mode'))
    <!-- Simple Mode Guided Banner -->
    <div style="background: #FEF3C7; border: 2px solid #F59E0B; border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <i class="fa-solid fa-glasses fa-2x" style="color: #D97706;"></i>
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #92400E; margin: 0;">簡単モード（高視認性）が有効です</h3>
                <p style="font-size: 14px; color: #B45309; margin: 2px 0 0;">大きなボタンと分かりやすい言葉で、日々の客室販売と予約確認を行えます。</p>
            </div>
        </div>
        <form action="{{ route('simple_mode.toggle') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline" style="background: #FFFFFF; font-weight: 700;">標準モードに戻す</button>
        </form>
    </div>
    @endif

    <!-- Key Metrics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 26px;">
        <!-- Card 1: Total Today's Inventory -->
        <a href="{{ route('bulk_editor.index') }}" style="text-decoration: none; color: inherit; display: block;" class="kpi-card-link" title="Click to open Bulk Inventory Studio">
            <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary-accent); transition: transform 0.15s, box-shadow 0.15s; cursor: pointer;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">
                                Today's Available
                            </div>
                            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                                {{ $availableRoomsToday }} <span style="font-size: 14px; color: var(--text-muted); font-weight: 500;">/ {{ $totalRooms }} rooms</span>
                            </div>
                        </div>
                        <div style="width: 42px; height: 42px; border-radius: 10px; background: #E0F2FE; display: flex; align-items: center; justify-content: center; color: #0284C7; font-size: 18px;">
                            <i class="fa-solid fa-bed"></i>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: var(--success); margin-top: 10px; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-circle-check"></i> Outbox inventory synchronized across all OTAs
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 2: Connected Channels -->
        <a href="{{ route('channels.index') }}" style="text-decoration: none; color: inherit; display: block;" class="kpi-card-link" title="Click to view Channel Connections">
            <div class="card" style="margin-bottom: 0; border-top: 4px solid #34D399; transition: transform 0.15s, box-shadow 0.15s; cursor: pointer;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">
                                Connected OTAs
                            </div>
                            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                                {{ $activeChannelsCount }} <span style="font-size: 14px; color: var(--text-muted); font-weight: 500;">active</span>
                            </div>
                        </div>
                        <div style="width: 42px; height: 42px; border-radius: 10px; background: #D1FAE5; display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 18px;">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 10px;">
                        Booking.com, Agoda, Expedia, Rakuten, Jalan
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 3: Outbox Sync Queue -->
        <a href="{{ route('event_trace.index') }}" style="text-decoration: none; color: inherit; display: block;" class="kpi-card-link" title="Click to inspect EventTrace Telemetry">
            <div class="card" style="margin-bottom: 0; border-top: 4px solid #F59E0B; transition: transform 0.15s, box-shadow 0.15s; cursor: pointer;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">
                                Sync Outbox Queue
                            </div>
                            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                                {{ $pendingOutboxCount }} <span style="font-size: 14px; color: var(--text-muted); font-weight: 500;">pending</span>
                            </div>
                        </div>
                        <div style="width: 42px; height: 42px; border-radius: 10px; background: #FEF3C7; display: flex; align-items: center; justify-content: center; color: #D97706; font-size: 18px;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: {{ $failedOutboxCount > 0 ? 'var(--danger)' : 'var(--text-muted)' }}; margin-top: 10px;">
                        @if($failedOutboxCount > 0)
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ $failedOutboxCount }} events require retry
                        @else
                            <i class="fa-solid fa-check-double"></i> 0 failed queue events
                        @endif
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 4: Security Risk Index -->
        <a href="{{ route('security_governance.index') }}" style="text-decoration: none; color: inherit; display: block;" class="kpi-card-link" title="Click to open Security Intelligence">
            <div class="card" style="margin-bottom: 0; border-top: 4px solid #EC4899; transition: transform 0.15s, box-shadow 0.15s; cursor: pointer;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">
                                Security Posture
                            </div>
                            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                                {{ $systemRiskScore }}/100 <span style="font-size: 13px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: #D1FAE5; color: #065F46;">NORMAL</span>
                            </div>
                        </div>
                        <div style="width: 42px; height: 42px; border-radius: 10px; background: #FCE7F3; display: flex; align-items: center; justify-content: center; color: #DB2777; font-size: 18px;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 10px;">
                        Context-Aware Hybrid Access Governance Active
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Quick Actions Banner -->
    <div class="card" style="background: linear-gradient(135deg, #183B50, #0F1D2F); color: white; border: none; padding: 18px 24px; margin-bottom: 26px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 700; margin: 0; color: #FFFFFF;">
                    <i class="fa-solid fa-bolt" style="color: #E8A64A;"></i> Express Multi-Channel Operations
                </h3>
                <p style="font-size: 13px; color: #94A3B8; margin: 4px 0 0;">
                    Update once, synchronize everywhere. Transactional outbox guarantees reliable propagation.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <form action="{{ route('channels.sync_all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 600;">
                        <i class="fa-solid fa-arrows-rotate"></i> Broadcast Inventory Now
                    </button>
                </form>
                <a href="{{ route('bulk_editor.index') }}" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fa-solid fa-layer-group"></i> Bulk Change Studio
                </a>
                <a href="{{ route('partner_assurance.index') }}" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fa-solid fa-handshake"></i> Partner Quality Lab
                </a>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Section -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Left Column: Channel Status & Rate Parity -->
        <div>
            <!-- Channel Distribution Live Monitor -->
            <div class="card">
                <div class="card-header" style="background: #FFFFFF;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Connected Channel Status & Health</h3>
                        <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">Live adapter status, latency, and outbox synchronization state</p>
                    </div>
                    <a href="{{ route('channels.index') }}" class="btn btn-sm btn-secondary">
                        Manage Channels <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: #F8FAFC; text-align: left; font-size: 12px; color: var(--text-muted); text-transform: uppercase;">
                                <th style="padding: 12px 20px;">Channel</th>
                                <th style="padding: 12px 14px;">Type</th>
                                <th style="padding: 12px 14px;">Status</th>
                                <th style="padding: 12px 14px;">Last Sync</th>
                                <th style="padding: 12px 20px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($channels as $ch)
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 14px 20px; font-weight: 600;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 28px; height: 28px; border-radius: 6px; background: #F1F5F9; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #183B50;">
                                            {{ substr($ch->name, 0, 2) }}
                                        </div>
                                        <span>{{ $ch->name }}</span>
                                    </div>
                                </td>
                                <td style="padding: 14px 14px;">
                                    <span class="badge" style="background: #F1F5F9; color: #475569; text-transform: uppercase; font-size: 11px;">
                                        {{ $ch->type }}
                                    </span>
                                </td>
                                <td style="padding: 14px 14px;">
                                    @if($ch->status === 'connected')
                                        <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                            <i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 4px;"></i> Connected
                                        </span>
                                    @else
                                        <span class="badge" style="background: #FEE2E2; color: #991B1B;">
                                            <i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 4px;"></i> {{ $ch->status }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 14px 14px; font-size: 12px; color: var(--text-muted);">
                                    {{ $ch->last_sync_at ? $ch->last_sync_at->diffForHumans() : 'Just now' }}
                                </td>
                                <td style="padding: 14px 20px; text-align: right;">
                                    <form action="{{ route('channels.sync', $ch) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="padding: 4px 10px; font-size: 12px;">
                                            <i class="fa-solid fa-rotate"></i> Sync
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rate Parity Alert Card -->
            <div class="card">
                <div class="card-header" style="background: #FFFFFF;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Rate Parity Intelligence</h3>
                        <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">Ensuring direct booking website retains competitive pricing against OTAs</p>
                    </div>
                    <form action="{{ route('rates.scan_parity') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-radar"></i> Run Parity Scan
                        </button>
                    </form>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: #F8FAFC; text-align: left; font-size: 12px; color: var(--text-muted); text-transform: uppercase;">
                                <th style="padding: 12px 20px;">Room Type</th>
                                <th style="padding: 12px 14px;">Channel</th>
                                <th style="padding: 12px 14px;">Direct vs OTA</th>
                                <th style="padding: 12px 20px; text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($parityObservations as $parity)
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 14px 20px; font-weight: 600;">
                                    {{ $parity->roomType?->name ?? 'Standard' }}
                                </td>
                                <td style="padding: 14px 14px; font-size: 13px;">
                                    {{ $parity->channel?->name ?? 'OTA' }}
                                </td>
                                <td style="padding: 14px 14px; font-size: 13px;">
                                    ¥{{ number_format($parity->expected_rate) }} direct / ¥{{ number_format($parity->observed_rate) }} OTA
                                </td>
                                <td style="padding: 14px 20px; text-align: right;">
                                    @if(!$parity->is_discrepancy)
                                        <span class="badge" style="background: #D1FAE5; color: #065F46;">Compliant</span>
                                    @else
                                        <span class="badge" style="background: #FEE2E2; color: #991B1B;">Discrepancy</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                    All channels in strict parity compliance.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: AI Security Recommendations & Certified Partners -->
        <div>
            <!-- AI Security Advisory (IEEE 802) -->
            <div class="card">
                <div class="card-header" style="background: #FFFFFF;">
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                            <i class="fa-solid fa-brain" style="color: #EC4899;"></i> AI Security Advisory
                        </h3>
                        <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0;">Adaptive least-privilege governance</p>
                    </div>
                    <a href="{{ route('security_governance.index') }}" class="btn btn-sm btn-secondary" style="font-size: 11px;">
                        View All
                    </a>
                </div>
                <div class="card-body" style="padding: 16px;">
                    @forelse($recommendations as $rec)
                    <div style="background: #FDF2F8; border: 1px solid #FBCFE8; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        <div style="font-size: 13px; font-weight: 700; color: #9D174D; margin-bottom: 4px;">
                            {{ $rec->title }}
                        </div>
                        <p style="font-size: 12px; color: #831843; margin-bottom: 8px; line-height: 1.4;">
                            {{ Str::limit($rec->recommendation_text, 140) }}
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #BE185D;">
                            <span>Target: {{ $rec->user?->name ?? 'Staff' }}</span>
                            <span class="badge" style="background: #BE185D; color: white;">Advisory</span>
                        </div>
                    </div>
                    @empty
                    <div style="text-align: center; color: var(--text-muted); padding: 14px; font-size: 13px;">
                        No pending risk advisories. Security perimeter clean.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Certified Integration Partners -->
            <div class="card">
                <div class="card-header" style="background: #FFFFFF;">
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                            <i class="fa-solid fa-certificate" style="color: #F59E0B;"></i> Certified Partners
                        </h3>
                        <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0;">TEMAIRAZU-style Partner Registry</p>
                    </div>
                    <a href="{{ route('partner_assurance.index') }}" class="btn btn-sm btn-secondary" style="font-size: 11px;">
                        Registry
                    </a>
                </div>
                <div class="card-body" style="padding: 16px;">
                    @foreach($partners as $partner)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: var(--text-main);">{{ $partner->name }}</div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">{{ $partner->category }}</div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px;">
                                <i class="fa-solid fa-check"></i> Certified
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

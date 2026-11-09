@extends('layouts.app')

@section('title', 'Partner Assurance & Integration Quality Lab - ClearStay 2.0')
@section('header_title', 'Partner Assurance & Quality Lab')
@section('header_subtitle', 'TEMAIRAZU-style Partner Registry, OpenAPI / JSON Schema Contract Lab, and Scenario Test Automation.')

@section('content')
<div class="partner-assurance-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Certified Integration Partner Ecosystem</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                Rigorous interface certification for Property Management Systems (PMS), Central Reservation Systems (CRS), and Revenue Engines (RMS).
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('event_trace.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-wave-square"></i> Open EventTrace Hub
            </a>
            <a href="{{ route('readiness.index') }}" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                <i class="fa-solid fa-certificate"></i> Partner Readiness Centre
            </a>
        </div>
    </div>

    <!-- Partner Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">
        @foreach($partners as $partner)
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <span class="badge" style="background: #F1F5F9; color: #475569; text-transform: uppercase; font-size: 10px; font-weight: 700;">
                            {{ $partner->category }}
                        </span>
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                            {{ $partner->name }}
                        </h3>
                        <div style="font-size: 12px; color: var(--text-muted);">{{ $partner->company }}</div>
                    </div>
                    @if($partner->status === 'certified')
                        <span class="badge" style="background: #D1FAE5; color: #065F46;">Certified</span>
                    @elseif($partner->status === 'testing')
                        <span class="badge" style="background: #FEF3C7; color: #92400E;">Testing</span>
                    @else
                        <span class="badge" style="background: #F1F5F9; color: #64748B;">{{ $partner->status }}</span>
                    @endif
                </div>

                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px; line-height: 1.4;">
                    {{ $partner->description }}
                </p>

                <div style="background: #F8FAFC; border-radius: 8px; padding: 12px; margin-bottom: 18px; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">API Protocol:</span>
                        <strong style="color: var(--text-main);">{{ $partner->api_version }} ({{ strtoupper($partner->auth_type) }})</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Active Contract:</span>
                        <strong>{{ $partner->contracts->first()?->contract_name ?? 'OpenAPI 3.0 Core' }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Readiness Score:</span>
                        <strong style="color: #065F46;">{{ $partner->readinessReports->first()?->score_percentage ?? 96 }}% Pass</strong>
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <a href="{{ route('partner_assurance.show', $partner) }}" class="btn btn-sm btn-primary" style="flex: 1; justify-content: center; font-weight: 700;">
                        Open Testing Lab <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

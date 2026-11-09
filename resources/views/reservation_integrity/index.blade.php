@extends('layouts.app')

@section('title', __('clearstay.integrity.page_title'))
@section('header_title', __('clearstay.integrity.header_title'))
@section('header_subtitle', __('clearstay.integrity.header_subtitle'))

@push('styles')
<style>
    .integrity-nav-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid var(--border);
        margin-bottom: 24px;
        overflow-x: auto;
    }
    .integrity-tab-btn {
        padding: 12px 20px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        background: none;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .integrity-tab-btn:hover {
        color: var(--primary);
    }
    .integrity-tab-btn.active {
        color: #287F79;
        border-bottom-color: #287F79;
    }
    .strategy-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .strategy-card.recommended {
        border: 2px solid #287F79;
        box-shadow: 0 6px 20px rgba(40, 127, 121, 0.12);
    }
    .slider-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
    }
    .slider-header {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-main);
    }
</style>
@endpush

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Hero Banner -->
    <div class="card" style="margin-bottom: 0; background: linear-gradient(135deg, #0F1D2F, #183B50); color: #ffffff; border: 1px solid #287F79;">
        <div class="card-body" style="padding: 24px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span class="badge" style="background: rgba(45, 212, 191, 0.2); color: #2DD4BF; font-weight: 800; letter-spacing: 0.05em;">
                        RESERVATION INTEGRITY & REVENUE MANAGEMENT
                    </span>
                    <h2 style="font-size: 24px; font-weight: 800; margin-top: 8px;">
                        {{ __('clearstay.integrity.header_title') }}
                    </h2>
                    <p style="font-size: 14px; color: #94A3B8; max-width: 800px; margin-top: 4px; line-height: 1.5;">
                        {{ __('clearstay.integrity.header_subtitle') }}
                    </p>
                </div>

                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <span class="badge" style="background: #064E3B; color: #34D399; font-size: 12px; padding: 8px 14px; border: 1px solid #059669;">
                        <i class="fa-solid fa-lock"></i> Row Locks: ACTIVE
                    </span>
                    <span class="badge" style="{{ $policy->is_enabled ? 'background: #0284C7; color: #ffffff;' : 'background: #334155; color: #94A3B8;' }} font-size: 12px; padding: 8px 14px;">
                        <i class="fa-solid fa-chart-line"></i> Overbooking: {{ $policy->is_enabled ? 'ACTIVE' : 'DISABLED' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="integrity-nav-tabs">
        <button type="button" class="integrity-tab-btn active" onclick="switchIntegrityTab('prevention')">
            <i class="fa-solid fa-shield-halved"></i> {{ __('clearstay.integrity.tab_prevention') }}
        </button>
        <button type="button" class="integrity-tab-btn" onclick="switchIntegrityTab('concurrency')">
            <i class="fa-solid fa-bolt"></i> {{ __('clearstay.integrity.tab_concurrency') }}
        </button>
        <button type="button" class="integrity-tab-btn" onclick="switchIntegrityTab('overbooking')">
            <i class="fa-solid fa-chart-pie"></i> {{ __('clearstay.integrity.tab_overbooking') }}
        </button>
        <button type="button" class="integrity-tab-btn" onclick="switchIntegrityTab('recovery')">
            <i class="fa-solid fa-arrows-rotate"></i> {{ __('clearstay.integrity.tab_recovery') }}
        </button>
        <button type="button" class="integrity-tab-btn" onclick="switchIntegrityTab('ledger')">
            <i class="fa-solid fa-book-journal-whills"></i> {{ __('clearstay.integrity.tab_ledger') }}
        </button>
    </div>

    <!-- TAB 1: Double-Booking Prevention -->
    <div id="tab-prevention" class="integrity-tab-pane">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 style="font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-lock text-primary"></i> Pessimistic Row Locking Architecture
                    </h3>
                </div>
                <div class="card-body" style="font-size: 13px; line-height: 1.6; color: var(--text-main);">
                    <p>
                        In commercial hospitality systems like Temairazu, frontend availability checks are inherently vulnerable to race conditions when concurrent guests or OTAs book the same remaining inventory.
                    </p>
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                        <code style="font-size: 12px; color: #0284C7;">
                            SELECT * FROM rooms WHERE id = ? FOR UPDATE;<br>
                            SELECT * FROM reservations WHERE room_id = ? AND check_in < ? AND check_out > ? FOR UPDATE;
                        </code>
                    </div>
                    <ul style="margin-left: 20px;">
                        <li><strong>SERIALIZABLE Isolation:</strong> Locks target room until transaction commit.</li>
                        <li><strong>Atomic Predicate:</strong> Evaluates overlapping intervals <code>(check_in &lt; req_out AND check_out &gt; req_in)</code>.</li>
                        <li><strong>Immutable Audit:</strong> Every state change appends a record to <code>inventory_ledger_entries</code>.</li>
                    </ul>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 style="font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-fingerprint text-primary"></i> {{ __('clearstay.integrity.idempotency_title') }}
                    </h3>
                </div>
                <div class="card-body" style="font-size: 13px; line-height: 1.6;">
                    <p>
                        {{ __('clearstay.integrity.idempotency_desc') }}
                    </p>
                    <button type="button" class="btn btn-primary" onclick="testIdempotencyKey()" style="background: #287F79; font-weight: 700; width: 100%; justify-content: center; margin-top: 10px;">
                        <i class="fa-solid fa-vial"></i> Test Idempotent Deduplication
                    </button>
                    <div id="idempotencyResult" style="margin-top: 14px; display: none; background: #0F172A; color: #38BDF8; padding: 14px; border-radius: 8px; font-family: monospace; font-size: 11px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Concurrency Stress Lab -->
    <div id="tab-concurrency" class="integrity-tab-pane" style="display: none;">
        <div class="card" style="margin-bottom: 0; border: 1px solid #1E293B; background: #0F1D2F; color: #ffffff;">
            <div class="card-body" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #2DD4BF;">{{ __('clearstay.integrity.simulation_title') }}</h3>
                        <p style="font-size: 13px; color: #94A3B8; margin-top: 4px;">{{ __('clearstay.integrity.simulation_desc') }}</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" onclick="runConcurrencySimulation()" style="background: #E8A64A; color: #0F1D2F; font-weight: 800; padding: 10px 20px;">
                            <i class="fa-solid fa-play"></i> <span id="simBtnText">{{ __('clearstay.integrity.simulate_btn') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Simulation Output -->
                <div id="concurrencyTelemetry" style="margin-top: 20px; display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 18px;">
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 14px;">
                            <div style="font-size: 11px; color: #94A3B8; text-transform: uppercase;">Total Attempts</div>
                            <div id="teleTotal" style="font-size: 24px; font-weight: 800; color: #38BDF8; margin-top: 4px;">10</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 14px;">
                            <div style="font-size: 11px; color: #94A3B8; text-transform: uppercase;">Accepted Bookings</div>
                            <div id="teleAccepted" style="font-size: 24px; font-weight: 800; color: #34D399; margin-top: 4px;">1</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 14px;">
                            <div style="font-size: 11px; color: #94A3B8; text-transform: uppercase;">Prevented Conflicts</div>
                            <div id="teleRejected" style="font-size: 24px; font-weight: 800; color: #F87171; margin-top: 4px;">9</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 14px;">
                            <div style="font-size: 11px; color: #94A3B8; text-transform: uppercase;">Total Latency</div>
                            <div id="teleLatency" style="font-size: 24px; font-weight: 800; color: #E8A64A; margin-top: 4px;">42ms</div>
                        </div>
                    </div>

                    <pre id="simJsonOutput" style="background: #050A14; border: 1px solid #1E293B; border-radius: 8px; padding: 16px; font-size: 11px; color: #38BDF8; max-height: 280px; overflow-y: auto;"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Revenue & Controlled Overbooking -->
    <div id="tab-overbooking" class="integrity-tab-pane" style="display: none;">
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Strategy Comparison Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                <!-- Strategy 1 -->
                <div class="strategy-card">
                    <div>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px; margin-bottom: 8px;">ZERO OVERBOOKING</span>
                        <h4 style="font-size: 16px; font-weight: 800; color: var(--text-main);">{{ __('clearstay.overbooking.strategy_1') }}</h4>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 6px 0 16px;">{{ __('clearstay.overbooking.strategy_1_desc') }}</p>
                        
                        <div style="background: #f8fafc; border-radius: 8px; padding: 12px; font-size: 13px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Booking Limit:</span>
                                <strong id="strat1Limit">{{ $calculations['strategies']['zero_overbooking']['booking_limit'] }} rooms</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Expected Arrivals:</span>
                                <strong id="strat1Arrivals">{{ $calculations['strategies']['zero_overbooking']['expected_arrivals'] }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Walk Risk:</span>
                                <strong style="color: #059669;">0.00% (Zero Walks)</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Expected Revenue:</span>
                                <strong id="strat1Rev">¥{{ number_format($calculations['strategies']['zero_overbooking']['expected_revenue']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy 2 -->
                <div class="strategy-card">
                    <div>
                        <span class="badge" style="background: #FEF3C7; color: #B45309; font-size: 11px; margin-bottom: 8px;">STATIC BUFFER</span>
                        <h4 style="font-size: 16px; font-weight: 800; color: var(--text-main);">{{ __('clearstay.overbooking.strategy_2') }}</h4>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 6px 0 16px;">{{ __('clearstay.overbooking.strategy_2_desc') }}</p>
                        
                        <div style="background: #f8fafc; border-radius: 8px; padding: 12px; font-size: 13px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Booking Limit:</span>
                                <strong id="strat2Limit">{{ $calculations['strategies']['fixed_buffer']['booking_limit'] }} rooms</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Expected Arrivals:</span>
                                <strong id="strat2Arrivals">{{ $calculations['strategies']['fixed_buffer']['expected_arrivals'] }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Walk Risk:</span>
                                <strong id="strat2Risk">{{ $calculations['strategies']['fixed_buffer']['walk_risk_pct'] }}%</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Net Profit:</span>
                                <strong id="strat2Rev">¥{{ number_format($calculations['strategies']['fixed_buffer']['net_profit']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy 3: Recommended -->
                <div class="strategy-card recommended">
                    <span class="badge" style="position: absolute; top: 12px; right: 12px; background: #287F79; color: #ffffff; font-size: 10px;">RECOMMENDED</span>
                    <div>
                        <span class="badge" style="background: #E6F4F1; color: #287F79; font-size: 11px; margin-bottom: 8px;">STOCHASTIC RMS</span>
                        <h4 style="font-size: 16px; font-weight: 800; color: #183B50;">{{ __('clearstay.overbooking.strategy_3') }}</h4>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 6px 0 16px;">{{ __('clearstay.overbooking.strategy_3_desc') }}</p>
                        
                        <div style="background: #E6F4F1; border-radius: 8px; padding: 12px; font-size: 13px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Optimal Limit (B*):</span>
                                <strong id="strat3Limit" style="color: #287F79;">{{ $calculations['strategies']['stochastic_optimization']['booking_limit'] }} rooms (+{{ $calculations['strategies']['stochastic_optimization']['buffer_rooms'] }})</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Expected Arrivals:</span>
                                <strong id="strat3Arrivals">{{ $calculations['strategies']['stochastic_optimization']['expected_arrivals'] }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Service Risk:</span>
                                <strong id="strat3Risk">{{ $calculations['strategies']['stochastic_optimization']['walk_risk_pct'] }}% (Safe)</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Net Expected Profit:</span>
                                <strong id="strat3Rev" style="color: #059669;">¥{{ number_format($calculations['strategies']['stochastic_optimization']['net_profit']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenario Parameter Controls & Safety Thresholds -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 style="font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-sliders text-primary"></i> Scenario Simulation Controls & Risk Thresholds
                    </h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
                        <div class="slider-group">
                            <div class="slider-header">
                                <span>Historical Cancellation Rate:</span>
                                <span id="lblCancelRate">{{ $policy->historical_cancellation_rate }}%</span>
                            </div>
                            <input type="range" min="1" max="40" step="0.5" value="{{ $policy->historical_cancellation_rate }}" id="rngCancelRate" oninput="recalculateOverbooking()">
                        </div>

                        <div class="slider-group">
                            <div class="slider-header">
                                <span>Historical No-Show Rate:</span>
                                <span id="lblNoshowRate">{{ $policy->historical_noshow_rate }}%</span>
                            </div>
                            <input type="range" min="0.5" max="20" step="0.5" value="{{ $policy->historical_noshow_rate }}" id="rngNoshowRate" oninput="recalculateOverbooking()">
                        </div>

                        <div class="slider-group">
                            <div class="slider-header">
                                <span>Average Daily Rate (ADR):</span>
                                <span id="lblAdr">¥{{ number_format($policy->default_adr) }}</span>
                            </div>
                            <input type="range" min="8000" max="40000" step="500" value="{{ $policy->default_adr }}" id="rngAdr" oninput="recalculateOverbooking()">
                        </div>

                        <div class="slider-group">
                            <div class="slider-header">
                                <span>Relocation Cost per Walk:</span>
                                <span id="lblWalkCost">¥{{ number_format($policy->relocation_cost_per_walk) }}</span>
                            </div>
                            <input type="range" min="15000" max="80000" step="1000" value="{{ $policy->relocation_cost_per_walk }}" id="rngWalkCost" oninput="recalculateOverbooking()">
                        </div>
                    </div>

                    <!-- Safety Stop Alert -->
                    <div id="safetyStopAlert" style="margin-top: 16px; padding: 12px 18px; border-radius: 8px; font-size: 13px; display: flex; align-items: center; gap: 10px; background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span id="safetyStopMsg">{{ $calculations['stop_reason'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Manager Policy Activation Form -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 style="font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-user-shield text-primary"></i> Manager Authorization & Policy Commitment
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('reservation_integrity.update_policy') }}">
                        @csrf
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700;">Overbooking Status:</label>
                                <select name="is_enabled" class="form-control" style="font-size: 13px;">
                                    <option value="0" {{ !$policy->is_enabled ? 'selected' : '' }}>Disabled (Physical Capacity Only)</option>
                                    <option value="1" {{ $policy->is_enabled ? 'selected' : '' }}>Enabled (Controlled Buffer Active)</option>
                                </select>
                            </div>

                            <div>
                                <label style="font-size: 12px; font-weight: 700;">Active Strategy:</label>
                                <select name="strategy" class="form-control" style="font-size: 13px;">
                                    <option value="zero_overbooking" {{ $policy->strategy === 'zero_overbooking' ? 'selected' : '' }}>Zero Overbooking (Safe)</option>
                                    <option value="fixed_buffer" {{ $policy->strategy === 'fixed_buffer' ? 'selected' : '' }}>Fixed Buffer (+5%)</option>
                                    <option value="stochastic_optimization" {{ $policy->strategy === 'stochastic_optimization' ? 'selected' : '' }}>Stochastic Risk Optimization</option>
                                </select>
                            </div>

                            <div>
                                <label style="font-size: 12px; font-weight: 700;">Max Buffer Rooms:</label>
                                <input type="number" name="max_buffer_rooms" class="form-control" value="{{ $policy->max_buffer_rooms }}" min="0" max="8">
                            </div>

                            <div>
                                <label style="font-size: 12px; font-weight: 700;">Max Tolerated Walk Risk (%):</label>
                                <input type="number" step="0.1" name="max_tolerated_walk_risk" class="form-control" value="{{ $policy->max_tolerated_walk_risk }}" min="0.5" max="10">
                            </div>
                        </div>

                        <input type="hidden" name="default_adr" value="{{ $policy->default_adr }}">
                        <input type="hidden" name="relocation_cost_per_walk" value="{{ $policy->relocation_cost_per_walk }}">
                        <input type="hidden" name="historical_cancellation_rate" value="{{ $policy->historical_cancellation_rate }}">
                        <input type="hidden" name="historical_noshow_rate" value="{{ $policy->historical_noshow_rate }}">

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary" style="background: #287F79; font-weight: 700;">
                                <i class="fa-solid fa-floppy-disk"></i> Save & Authorize Overbooking Policy
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB 4: Reservation Recovery Centre -->
    <div id="tab-recovery" class="integrity-tab-pane" style="display: none;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 style="font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-arrows-rotate text-primary"></i> Active Reservations & Conflict Resolution Studio
                </h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-hover" style="margin-bottom: 0; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th>Confirmation</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th style="text-align: right;">Recovery Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeReservations as $res)
                                <tr>
                                    <td><strong>{{ $res->confirmation_code }}</strong></td>
                                    <td>{{ $res->guest?->first_name }} {{ $res->guest?->last_name }}</td>
                                    <td>
                                        <span class="badge" style="background: #E6F3F2; color: #287F79;">
                                            Room #{{ $res->room?->room_number ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $res->check_in }} &rarr; {{ $res->check_out }}</td>
                                    <td><span class="badge badge-success">{{ strtoupper($res->status) }}</span></td>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openReassignModal({{ $res->id }}, '{{ $res->confirmation_code }}', {{ $res->room_id ?? 0 }})">
                                            <i class="fa-solid fa-arrow-right-arrow-left"></i> Reassign Room
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        No active reservations found for this hotel context.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 5: Traceable Inventory Ledger -->
    <div id="tab-ledger" class="integrity-tab-pane" style="display: none;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 style="font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-book-journal-whills text-primary"></i> {{ __('clearstay.integrity.ledger_title') }}
                </h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-hover" style="margin-bottom: 0; font-size: 12px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th>Timestamp</th>
                                <th>Stay Date</th>
                                <th>Room</th>
                                <th>Operation</th>
                                <th>Source</th>
                                <th>Delta</th>
                                <th>Availability</th>
                                <th>Actor</th>
                                <th>Correlation ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledgerEntries as $entry)
                                <tr>
                                    <td>{{ $entry->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td><strong>{{ $entry->stay_date->format('Y-m-d') }}</strong></td>
                                    <td>Room #{{ $entry->room?->room_number ?? '-' }}</td>
                                    <td>
                                        <span class="badge" style="background: #1E293B; color: #38BDF8; font-size: 10px;">
                                            {{ $entry->operation_type }}
                                        </span>
                                    </td>
                                    <td>{{ $entry->source }}</td>
                                    <td style="font-weight: 700; color: {{ $entry->delta < 0 ? '#EF4444' : '#10B981' }};">
                                        {{ $entry->delta > 0 ? "+{$entry->delta}" : $entry->delta }}
                                    </td>
                                    <td>{{ $entry->previous_availability }} &rarr; <strong>{{ $entry->new_availability }}</strong></td>
                                    <td>{{ $entry->actorUser?->name ?? $entry->actor_type }}</td>
                                    <td><code>{{ substr($entry->correlation_id, 0, 8) }}...</code></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        No ledger entries recorded yet. Execute a reservation or simulation to generate audit records.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Room Reassignment Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" role="dialog" style="display: none; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <form method="POST" action="{{ route('reservation_integrity.reassign_room') }}">
                @csrf
                <div class="modal-header" style="background: #0F1D2F; color: #ffffff;">
                    <h5 class="modal-title" style="font-size: 16px; font-weight: 700;">Reassign Reservation Room</h5>
                    <button type="button" class="close" onclick="$('#reassignModal').hide()" style="color: #ffffff; border: none; background: none; font-size: 20px;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" name="reservation_id" id="modalResId">
                    <p style="font-size: 13px; margin-bottom: 14px;">
                        Reassigning Reservation: <strong id="modalResCode"></strong>
                    </p>
                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 12px; font-weight: 700;">Select Alternative Room:</label>
                        <select name="target_room_id" class="form-control" style="font-size: 13px;">
                            @foreach($rooms as $rm)
                                <option value="{{ $rm->id }}">Room #{{ $rm->room_number }} (Floor {{ $rm->floor }}, {{ $rm->roomType?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700;">Recovery Justification:</label>
                        <textarea name="justification" class="form-control" rows="2" placeholder="e.g. Guest requested high floor / Room maintenance conflict resolution" required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc;">
                    <button type="button" class="btn btn-secondary" onclick="$('#reassignModal').hide()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #287F79;">Commit Reassignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function switchIntegrityTab(tabId) {
        $('.integrity-tab-btn').removeClass('active');
        $('.integrity-tab-pane').hide();

        $(event.currentTarget).addClass('active');
        $('#tab-' + tabId).fadeIn(150);
    }

    function runConcurrencySimulation() {
        $('#simBtnText').text('{{ __("clearstay.integrity.simulating") }}');
        $('#concurrencyTelemetry').slideDown();
        $('#simJsonOutput').text('Acquiring DB pessimistic row locks (SELECT ... FOR UPDATE) across 10 concurrent requests...');

        $.ajax({
            url: '{{ route("reservation_integrity.simulate") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                concurrency_count: 10
            },
            success: function(res) {
                $('#simBtnText').text('{{ __("clearstay.integrity.simulate_btn") }}');
                $('#teleTotal').text(res.concurrency_attempts);
                $('#teleAccepted').text(res.successful_allocations);
                $('#teleRejected').text(res.prevented_conflicts);
                $('#teleLatency').text(res.total_duration_ms + 'ms');
                $('#simJsonOutput').text(JSON.stringify(res, null, 2));
            },
            error: function(xhr) {
                $('#simBtnText').text('{{ __("clearstay.integrity.simulate_btn") }}');
                $('#simJsonOutput').text(JSON.stringify(xhr.responseJSON || xhr.responseText, null, 2));
            }
        });
    }

    function testIdempotencyKey() {
        $('#idempotencyResult').slideDown().text('Sending initial and duplicate request with identical Idempotency-Key...');

        $.ajax({
            url: '{{ route("reservation_integrity.idempotency") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                $('#idempotencyResult').text(JSON.stringify(res, null, 2));
            },
            error: function(xhr) {
                $('#idempotencyResult').text(JSON.stringify(xhr.responseJSON || xhr.responseText, null, 2));
            }
        });
    }

    function recalculateOverbooking() {
        const cancelRate = parseFloat($('#rngCancelRate').val());
        const noshowRate = parseFloat($('#rngNoshowRate').val());
        const adr = parseFloat($('#rngAdr').val());
        const walkCost = parseFloat($('#rngWalkCost').val());

        $('#lblCancelRate').text(cancelRate + '%');
        $('#lblNoshowRate').text(noshowRate + '%');
        $('#lblAdr').text('¥' + adr.toLocaleString());
        $('#lblWalkCost').text('¥' + walkCost.toLocaleString());

        $.ajax({
            url: '{{ route("reservation_integrity.calculate_overbooking") }}',
            method: 'GET',
            data: {
                cancellation_rate: cancelRate,
                noshow_rate: noshowRate,
                adr: adr,
                walk_cost: walkCost
            },
            success: function(res) {
                const s1 = res.strategies.zero_overbooking;
                const s2 = res.strategies.fixed_buffer;
                const s3 = res.strategies.stochastic_optimization;

                $('#strat1Limit').text(s1.booking_limit + ' rooms');
                $('#strat1Arrivals').text(s1.expected_arrivals);
                $('#strat1Rev').text('¥' + s1.expected_revenue.toLocaleString());

                $('#strat2Limit').text(s2.booking_limit + ' rooms');
                $('#strat2Arrivals').text(s2.expected_arrivals);
                $('#strat2Risk').text(s2.walk_risk_pct + '%');
                $('#strat2Rev').text('¥' + s2.net_profit.toLocaleString());

                $('#strat3Limit').text(s3.booking_limit + ' rooms (+' + s3.buffer_rooms + ')');
                $('#strat3Arrivals').text(s3.expected_arrivals);
                $('#strat3Risk').text(s3.walk_risk_pct + '%');
                $('#strat3Rev').text('¥' + s3.net_profit.toLocaleString());

                $('#safetyStopMsg').text(res.stop_reason);
                if (res.safety_stop_triggered) {
                    $('#safetyStopAlert').css({'background': '#FEF2F2', 'color': '#991B1B', 'border-color': '#FCA5A5'});
                } else {
                    $('#safetyStopAlert').css({'background': '#ECFDF5', 'color': '#065F46', 'border-color': '#A7F3D0'});
                }
            }
        });
    }

    function openReassignModal(resId, resCode, roomId) {
        $('#modalResId').val(resId);
        $('#modalResCode').text(resCode);
        $('#reassignModal').show();
    }
</script>
@endpush
@endsection

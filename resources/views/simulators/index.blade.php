@extends('layouts.app')

@section('title', 'Integration Simulator & Partner Network - ClearStay 4.2')
@section('header_title', 'Integration Simulator & Partner Network')
@section('header_subtitle', 'Comprehensive stateful simulation environment for Booking.com, Agoda, Expedia, Rakuten, Jalan, Direct Web & Sakura PMS.')

@section('content')
<div class="simulator-container" style="max-width: 1400px; margin: 0 auto;">

    <!-- Disclaimer / Simulation Notice Banner -->
    <div style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.08), rgba(14, 165, 233, 0.12)); border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
            <div style="background: #0284C7; color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                <i class="fa-solid fa-flask-vial"></i>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <span style="font-weight: 800; font-size: 16px; color: var(--text-main);">ClearStay 4.2 Integration Simulation Environment</span>
                    <span style="background: #38BDF8; color: #0F172A; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 9999px;">LOCAL SIMULATION ONLY</span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.5;">
                    This simulator executes <strong>real database state changes</strong>, maintains <strong>independent remote inventory tables</strong>, generates <strong>real HTTP-style request/response exchanges</strong>, and runs full reconciliation audits. No real OTA accounts or production API credentials are used.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form action="{{ route('simulators.reset_demo') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm" style="background: white; border: 1px solid #cbd5e1; color: #0F172A; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" onclick="return confirm('Reset all simulated remote inventories to match central PMS?')">
                    <i class="fa-solid fa-rotate text-primary"></i> Reset Simulated Data
                </button>
            </form>
            <a href="{{ route('inventory_reconciliation.index') }}" class="btn btn-sm btn-primary" style="font-weight: 700;">
                <i class="fa-solid fa-arrows-split-up-and-left"></i> Run Channel Audit
            </a>
        </div>
    </div>

    <!-- Mode Control & Active Property Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 18px 24px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <span style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Current Integration Mode</span>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px;">
                    <span class="badge {{ $globalBadge['class'] }}" style="padding: 6px 12px; font-size: 12px; font-weight: 800; border-radius: 6px; border: 1px solid currentColor;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px;" class="{{ $globalBadge['dot'] }}"></span>
                        {{ $globalBadge['label'] }}
                    </span>
                    <span style="font-size: 13px; color: var(--text-muted);">{{ $globalBadge['description'] }}</span>
                </div>
            </div>

            <!-- Mode Switcher Form -->
            <form action="{{ route('simulators.switch_mode') }}" method="POST" style="display: flex; gap: 8px; align-items: center;">
                @csrf
                <label for="mode_select" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Switch Mode:</label>
                <select name="mode" id="mode_select" class="form-select" style="font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 6px;">
                    <option value="simulator" {{ $globalMode === 'simulator' ? 'selected' : '' }}>Mode A: SIMULATOR (Recommended)</option>
                    <option value="sandbox" {{ $globalMode === 'sandbox' ? 'selected' : '' }}>Mode B: SANDBOX (Testbeds)</option>
                    <option value="live" {{ $globalMode === 'live' ? 'selected' : '' }}>Mode C: LIVE (Production - Protected)</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-weight: 700;">Apply Mode</button>
            </form>
        </div>
    </div>

    <!-- 7 Simulated Partners Cards Grid -->
    <h3 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-network-wired text-primary"></i> ClearStay Simulated Partner Network (7 Independent Remote States)
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-bottom: 30px;">
        @foreach($partnerModes as $code => $info)
        @php
            $prop = $properties[$code] ?? null;
            $failure = $activeFailures[$code] ?? null;
        @endphp
        <div class="card" style="padding: 16px 18px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div>
                        <h4 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--text-main);">{{ $info['name'] }}</h4>
                        <span style="font-size: 11px; font-family: monospace; color: var(--text-muted);">{{ $code }} &bull; {{ $prop?->api_version ?? 'v2.4' }}</span>
                    </div>
                    <span class="badge {{ $info['badge']['class'] }}" style="font-size: 10px; font-weight: 800; padding: 2px 6px;">
                        {{ $info['mode'] }}
                    </span>
                </div>

                <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6; margin-bottom: 12px;">
                    <div><strong>Remote Property:</strong> {{ $prop?->property_code ?? 'PROVISIONING...' }}</div>
                    <div><strong>Mapped Room Types:</strong> {{ $prop?->room_mappings_count ?? 0 }} configured</div>
                    <div><strong>Remote Inventory Rows:</strong> {{ $prop?->inventories_count ?? 0 }} tracked</div>
                </div>

                @if($failure && $failure->is_active)
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; border-radius: 6px; padding: 6px 10px; font-size: 11px; color: #dc2626; margin-bottom: 12px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <strong>Fault Injected:</strong> {{ $failure->failure_type }} (HTTP {{ $failure->http_status }})
                </div>
                @else
                <div style="font-size: 11px; color: #10B981; font-weight: 600; margin-bottom: 12px;">
                    <i class="fa-solid fa-circle-check"></i> Normal Operation (HTTP 200 Ack)
                </div>
                @endif
            </div>

            <div style="display: flex; gap: 6px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                <form action="{{ route('simulators.configure_failure') }}" method="POST" style="flex: 1;">
                    @csrf
                    <input type="hidden" name="partner_code" value="{{ $code }}">
                    <input type="hidden" name="failure_type" value="504_timeout">
                    <input type="hidden" name="action" value="{{ ($failure && $failure->is_active) ? 'clear' : 'set' }}">
                    <button type="submit" class="btn btn-sm w-100" style="font-size: 11px; font-weight: 700; background: {{ ($failure && $failure->is_active) ? '#10B981; color: white;' : '#FEE2E2; color: #991B1B;' }}">
                        {{ ($failure && $failure->is_active) ? 'Clear Failure' : 'Inject 504 Timeout' }}
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Failure Injection Lab -->
    <div class="card" style="margin-bottom: 30px; padding: 22px 24px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-radiation text-danger"></i> Failure Injection Lab
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                    Force simulated partner adapters to return real HTTP error codes, latency timeouts, or schema corruptions to test ClearStay resilience.
                </p>
            </div>
            <form action="{{ route('simulators.configure_failure') }}" method="POST">
                @csrf
                <input type="hidden" name="partner_code" value="all">
                <input type="hidden" name="failure_type" value="none">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-weight: 700;">
                    <i class="fa-solid fa-circle-xmark"></i> Clear All Active Injections
                </button>
            </form>
        </div>

        <form action="{{ route('simulators.configure_failure') }}" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px; gap: 12px; align-items: flex-end;">
            @csrf
            <input type="hidden" name="action" value="set">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">Target Simulator:</label>
                <select name="partner_code" class="form-select" style="font-size: 13px;">
                    @foreach($partnerModes as $code => $info)
                    <option value="{{ $code }}">{{ $info['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">Failure Condition:</label>
                <select name="failure_type" class="form-select" style="font-size: 13px;">
                    <option value="504_timeout">HTTP 504 — Gateway Timeout</option>
                    <option value="429_rate_limit">HTTP 429 — Rate Limit Exceeded</option>
                    <option value="500_server_error">HTTP 500 — Upstream Server Crash</option>
                    <option value="400_bad_request">HTTP 400 — Schema Validation Error</option>
                    <option value="401_unauthorized">HTTP 401 — Invalid Partner Token</option>
                    <option value="403_forbidden">HTTP 403 — Room Type Not Authorized</option>
                    <option value="409_conflict">HTTP 409 — Inventory Lock Conflict</option>
                </select>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">Simulated Delay (ms):</label>
                <input type="number" name="latency_ms" class="form-control" style="font-size: 13px;" value="350" min="0" max="3000">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">Custom Message (optional):</label>
                <input type="text" name="error_message" class="form-control" style="font-size: 13px;" placeholder="Custom error payload message">
            </div>
            <div>
                <button type="submit" class="btn btn-danger w-100" style="font-weight: 700; font-size: 13px; height: 38px;">
                    <i class="fa-solid fa-bolt"></i> Arm Fault
                </button>
            </div>
        </form>
    </div>

    <!-- 15 Interviewer Demonstration Scenarios (Section 16) -->
    <div class="card" style="margin-bottom: 30px; padding: 22px 24px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin: 0;">
                        15 Interviewer Demonstration Scenarios
                    </h3>
                    <span class="badge" style="background: #287F79; color: white; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 9999px;">
                        LIVE EXECUTABLE BACKEND LOGIC
                    </span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                    Click any scenario to execute actual backend services, observe database mutations, and inspect real request/response telemetry.
                </p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 14px; margin-bottom: 24px;">
            @foreach($scenarios as $sId => $scen)
            <div class="card" style="padding: 14px 16px; border-radius: 10px; background: var(--bg-card-subtle, #f8fafc); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.15s, box-shadow 0.15s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 800; color: var(--color-primary, #287F79);">SCENARIO {{ str_pad($sId, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="badge {{ $scen['badge_color'] }}" style="font-size: 10px; font-weight: 800; padding: 2px 6px;">
                            {{ $scen['tag'] }}
                        </span>
                    </div>
                    <h4 style="font-size: 14px; font-weight: 800; margin: 0 0 4px; color: var(--text-main);">{{ $scen['title'] }}</h4>
                    <div style="font-size: 11px; font-weight: 600; color: #0284C7; margin-bottom: 6px;">Target: {{ $scen['partner'] }}</div>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 12px; line-height: 1.4;">
                        {{ $scen['summary'] }}
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-primary w-100" style="font-weight: 700; font-size: 12px;" onclick="runScenario({{ $sId }}, '{{ addslashes($scen['title']) }}')">
                        <i class="fa-solid fa-play"></i> Run Scenario {{ $sId }}
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Live Scenario Execution Console & Telemetry Viewer -->
        <div id="scenarioConsole" style="background: #0F172A; color: #F8FAFC; border-radius: 10px; padding: 18px 22px; font-family: monospace; font-size: 12px; display: none; box-shadow: inset 0 2px 6px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10B981; animation: pulse 1.5s infinite;"></span>
                    <strong id="consoleScenarioTitle" style="color: #38BDF8; font-size: 13px;">Executing Scenario...</strong>
                </div>
                <button type="button" onclick="document.getElementById('scenarioConsole').style.display='none'" style="background: transparent; border: none; color: #94A3B8; cursor: pointer; font-size: 14px;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-bottom: 12px;">
                <div>
                    <div style="color: #94A3B8; font-size: 11px; margin-bottom: 2px;">START STATE:</div>
                    <div id="consoleStartState" style="color: #E2E8F0;">-</div>
                </div>
                <div>
                    <div style="color: #94A3B8; font-size: 11px; margin-bottom: 2px;">ACTIONS EXECUTED:</div>
                    <div id="consoleActions" style="color: #E2E8F0;">-</div>
                </div>
                <div>
                    <div style="color: #94A3B8; font-size: 11px; margin-bottom: 2px;">EVENT TRACE UUID:</div>
                    <div id="consoleTraceId" style="color: #38BDF8;">-</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <div style="color: #94A3B8; font-size: 11px; margin-bottom: 4px;">REQUEST PAYLOAD:</div>
                    <pre id="consoleRequest" style="background: #1E293B; padding: 10px; border-radius: 6px; overflow-x: auto; max-height: 140px; margin: 0; color: #F1F5F9; font-size: 11px;"></pre>
                </div>
                <div>
                    <div style="color: #94A3B8; font-size: 11px; margin-bottom: 4px;">SIMULATED RESPONSE:</div>
                    <pre id="consoleResponse" style="background: #1E293B; padding: 10px; border-radius: 6px; overflow-x: auto; max-height: 140px; margin: 0; color: #34D399; font-size: 11px;"></pre>
                </div>
            </div>

            <div style="background: #1E293B; border-left: 4px solid #10B981; padding: 10px 14px; border-radius: 4px;">
                <div style="color: #94A3B8; font-size: 11px;">FINAL VERIFIED OUTCOME:</div>
                <div id="consoleFinalResult" style="color: #F8FAFC; font-weight: bold; margin-top: 2px;"></div>
            </div>
        </div>
    </div>

    <!-- Inbound OTA Webhook Simulator Form -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
        <div class="card" style="padding: 22px 24px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color);">
            <h3 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-inbox text-primary"></i> Inbound OTA Booking Webhook Simulator
            </h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                Deliver a simulated reservation into ClearStay as if sent from Agoda, Booking.com, or Expedia webhook.
            </p>

            <form id="inboundForm" onsubmit="submitInboundBooking(event)" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                @csrf
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Partner:</label>
                    <select name="partner_code" class="form-select" style="font-size: 13px;" required>
                        <option value="agoda">Agoda Simulator</option>
                        <option value="booking_com">Booking.com Simulator</option>
                        <option value="expedia">Expedia Simulator</option>
                        <option value="rakuten">Rakuten Travel Simulator</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">External Res ID:</label>
                    <input type="text" name="external_reservation_id" class="form-control" style="font-size: 13px;" value="EXT-{{ rand(100000, 999999) }}" required>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Property Code:</label>
                    <input type="text" name="property_code" class="form-control" style="font-size: 13px;" value="AGD-{{ $hotel->code }}" required>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Room Type:</label>
                    <select name="remote_room_code" class="form-select" style="font-size: 13px;" required>
                        @foreach($roomTypes as $rt)
                        <option value="{{ $rt->code }}">{{ $rt->name }} ({{ $rt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Check-in Date:</label>
                    <input type="date" name="check_in_date" class="form-control" style="font-size: 13px;" value="{{ \Carbon\Carbon::today()->addDays(5)->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Check-out Date:</label>
                    <input type="date" name="check_out_date" class="form-control" style="font-size: 13px;" value="{{ \Carbon\Carbon::today()->addDays(7)->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Guest Name:</label>
                    <input type="text" name="guest_name" class="form-control" style="font-size: 13px;" value="Taro Suzuki" required>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Total Price (JPY):</label>
                    <input type="number" name="price" class="form-control" style="font-size: 13px;" value="48000" required>
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Idempotency Key (Optional):</label>
                    <input type="text" name="idempotency_key" class="form-control" style="font-size: 13px;" value="IDEM-{{ Str::uuid() }}">
                </div>
                <div style="grid-column: span 2;">
                    <button type="submit" class="btn btn-primary w-100" style="font-weight: 700; font-size: 13px;">
                        <i class="fa-solid fa-paper-plane"></i> Dispatch Inbound Webhook Booking
                    </button>
                </div>
            </form>
            <div id="inboundResult" style="margin-top: 14px; font-size: 12px; display: none;"></div>
        </div>

        <!-- Payment Simulator & Real RFC 5545 iCal Connector -->
        <div class="card" style="padding: 22px 24px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-credit-card text-success"></i> Virtual Card Payment Terminal Simulator
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
                    Simulate NTT DATA / Agoda Pay virtual terminal operations with realistic card settlement outcomes.
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px;">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="testPayment('charge')" style="font-weight: 700;">
                        <i class="fa-solid fa-circle-check"></i> Charge VCC (200)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="testPayment('decline')" style="font-weight: 700;">
                        <i class="fa-solid fa-ban"></i> Card Decline (402)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="testPayment('timeout')" style="font-weight: 700;">
                        <i class="fa-solid fa-clock"></i> Gateway Timeout (504)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="testPayment('refund')" style="font-weight: 700;">
                        <i class="fa-solid fa-rotate-left"></i> Full Refund (200)
                    </button>
                </div>
                <div id="paymentResult" style="background: #f1f5f9; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 11px; max-height: 110px; overflow-y: auto; display: none;"></div>
            </div>

            <div style="border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 16px;">
                <h4 style="font-size: 14px; font-weight: 800; margin: 0 0 6px; color: var(--text-main); display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-calendar-days text-primary"></i> Real RFC 5545 iCalendar Connector
                </h4>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
                    Live 1-way calendar availability subscription feed. Suitable for Apple Calendar, Google Calendar, or Airbnb sync.
                </p>
                @if($roomTypes->isNotEmpty())
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="{{ route('ical.export', ['hotel' => $hotel->id, 'roomType' => $roomTypes->first()->id]) }}" class="btn btn-sm btn-secondary" style="font-weight: 700;">
                        <i class="fa-solid fa-download"></i> Download .ICS Feed ({{ $roomTypes->first()->name }})
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Live Telemetry & Inbound Reservations Table -->
    <div class="card" style="padding: 22px 24px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); margin-bottom: 30px;">
        <h3 style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-clock-rotate-left text-primary"></i> Recent Simulated Requests & Telemetry Log
        </h3>

        <div style="overflow-x: auto;">
            <table class="table" style="font-size: 12px; margin: 0;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-transform: uppercase; font-size: 11px; color: var(--text-muted);">
                        <th>Timestamp</th>
                        <th>Partner</th>
                        <th>Direction</th>
                        <th>Endpoint</th>
                        <th>Status Code</th>
                        <th>Latency</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequests as $req)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="font-family: monospace;">{{ $req->created_at->format('H:i:s') }}</td>
                        <td><strong>{{ strtoupper($req->partner_code) }}</strong></td>
                        <td>
                            @if($req->direction === 'inbound_webhook')
                            <span class="badge bg-purple-100 text-purple-800" style="font-size: 10px;">INBOUND</span>
                            @else
                            <span class="badge bg-blue-100 text-blue-800" style="font-size: 10px;">OUTBOUND</span>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 11px;">{{ $req->endpoint }}</td>
                        <td>
                            @if($req->http_status >= 200 && $req->http_status < 300)
                            <span class="badge bg-success" style="font-size: 10px;">{{ $req->http_status }}</span>
                            @else
                            <span class="badge bg-danger" style="font-size: 10px;">{{ $req->http_status }}</span>
                            @endif
                        </td>
                        <td style="font-family: monospace;">{{ $req->duration_ms }}ms</td>
                        <td>
                            @if($req->status === 'success')
                            <span style="color: #10B981; font-weight: 600;"><i class="fa-solid fa-check"></i> Ack</span>
                            @else
                            <span style="color: #EF4444; font-weight: 600;" title="{{ $req->error_summary }}"><i class="fa-solid fa-triangle-exclamation"></i> {{ Str::limit($req->error_summary, 25) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 18px;">
                            No simulated requests executed yet. Run a scenario above to observe live exchange telemetry.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function runScenario(id, title) {
    const consoleBox = document.getElementById('scenarioConsole');
    consoleBox.style.display = 'block';
    document.getElementById('consoleScenarioTitle').innerText = 'Executing Scenario ' + id + ': ' + title + '...';
    document.getElementById('consoleStartState').innerText = 'Connecting to backend...';
    document.getElementById('consoleActions').innerText = 'Executing transaction...';
    document.getElementById('consoleTraceId').innerText = 'Pending...';
    document.getElementById('consoleRequest').innerText = '...';
    document.getElementById('consoleResponse').innerText = '...';
    document.getElementById('consoleFinalResult').innerText = 'Waiting for execution response...';

    consoleBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    fetch('/integrations/scenario/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data) {
            const d = data.data;
            document.getElementById('consoleScenarioTitle').innerText = 'âœ… Scenario ' + id + ': ' + d.title;
            document.getElementById('consoleStartState').innerText = d.start_state || '-';
            document.getElementById('consoleActions').innerText = d.actions || '-';
            document.getElementById('consoleTraceId').innerText = d.event_trace_id || '-';
            document.getElementById('consoleRequest').innerText = JSON.stringify(d.request_payload, null, 2);
            document.getElementById('consoleResponse').innerText = JSON.stringify(d.simulated_response, null, 2);
            document.getElementById('consoleFinalResult').innerText = d.final_result || 'Success';
        } else {
            document.getElementById('consoleFinalResult').innerText = 'Failed: ' + (data.message || 'Unknown error');
        }
    })
    .catch(err => {
        document.getElementById('consoleFinalResult').innerText = 'Execution error: ' + err.message;
    });
}

function submitInboundBooking(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    const resultBox = document.getElementById('inboundResult');
    resultBox.style.display = 'block';
    resultBox.innerHTML = '<span style="color: #64748B;"><i class="fa-solid fa-spinner fa-spin"></i> Dispatching webhook...</span>';

    fetch('/integrations/inbound-webhook', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json().then(data => ({ status: res.status, body: data })))
    .then(res => {
        if (res.status >= 200 && res.status < 300) {
            resultBox.innerHTML = '<div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 10px; border-radius: 6px;">' +
                '<strong>âœ… Booking Accepted (' + res.body.status + '):</strong> Ref: ' + res.body.confirmation_code + ' (PMS Res ID #' + res.body.clearstay_reservation_id + ')' +
                '</div>';
        } else {
            resultBox.innerHTML = '<div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 10px; border-radius: 6px;">' +
                '<strong>â Œ Rejected (' + (res.body.error_code || res.status) + '):</strong> ' + (res.body.error || 'Conflict') +
                '</div>';
        }
    })
    .catch(err => {
        resultBox.innerHTML = '<div style="color: red;">Error: ' + err.message + '</div>';
    });
}

function testPayment(action) {
    const resBox = document.getElementById('paymentResult');
    resBox.style.display = 'block';
    resBox.innerText = 'Calling virtual payment gateway...';

    fetch('/integrations/payment-simulate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ action: action, amount: 25000, currency: 'JPY' })
    })
    .then(r => r.json().then(d => ({ status: r.status, body: d })))
    .then(r => {
        resBox.innerText = JSON.stringify(r.body, null, 2);
    })
    .catch(e => {
        resBox.innerText = 'Payment error: ' + e.message;
    });
}
</script>
@endsection

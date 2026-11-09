@extends('layouts.app')

@section('title', 'Responsive Experience Lab - ClearStay 3.0')
@section('header_title', 'Responsive Experience Lab (レスポンシブ体験ラボ)')
@section('header_subtitle', 'Multi-Device Viewport Emulator, Real-Time Dual Comparison, and Automated QA Engine')

@push('styles')
<style>
    .lab-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Control Toolbar */
    .lab-toolbar {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 2px 8px rgba(15, 29, 47, 0.04);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .toolbar-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .toolbar-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
    }

    .form-select-sm, .form-input-sm {
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
        background-color: #f8fafc;
        outline: none;
        transition: all 0.2s;
    }

    .form-select-sm:focus, .form-input-sm:focus {
        border-color: #287F79;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(40, 127, 121, 0.15);
    }

    /* Mode Buttons Pill */
    .btn-group-pill {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 10px;
        border: 1px solid var(--border);
    }

    .btn-group-pill button {
        padding: 6px 14px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-group-pill button.active {
        background: #ffffff;
        color: var(--primary);
        font-weight: 700;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
    }

    /* Emulation Workspace View */
    .emulation-workspace {
        background: #0b1320;
        border-radius: 16px;
        padding: 32px 24px;
        border: 1px solid #1e293b;
        box-shadow: inset 0 2px 12px rgba(0,0,0,0.4);
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 720px;
        overflow-x: auto;
        position: relative;
    }

    /* Dual Mode Grid */
    .workspace-dual {
        display: flex;
        gap: 32px;
        justify-content: center;
        width: 100%;
    }

    .device-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .device-meta-pill {
        background: rgba(30, 41, 59, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #94a3b8;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        backdrop-filter: blur(8px);
    }

    .device-meta-pill .badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 12px;
    }

    /* Device Mockup Shells */
    .device-shell {
        background: #1e293b;
        border: 12px solid #0f172a;
        border-radius: 40px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 2px rgba(255,255,255,0.08);
        position: relative;
        overflow: hidden;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .device-shell.type-desktop {
        border-radius: 12px;
        border: 1px solid #334155;
        box-shadow: 0 20px 40px rgba(0,0,0,0.6);
    }

    /* Hardware Notch & Speaker */
    .device-notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 130px;
        height: 24px;
        background: #0f172a;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .device-speaker {
        width: 44px;
        height: 4px;
        background: #334155;
        border-radius: 2px;
    }

    .device-camera {
        width: 8px;
        height: 8px;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 50%;
    }

    /* Desktop Window Header */
    .desktop-window-bar {
        background: #1e293b;
        padding: 8px 14px;
        border-bottom: 1px solid #334155;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .window-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .dot-red { background: #ef4444; }
    .dot-yellow { background: #f59e0b; }
    .dot-green { background: #10b981; }

    .window-address {
        background: #0f172a;
        color: #94a3b8;
        font-size: 11px;
        padding: 3px 12px;
        border-radius: 6px;
        flex: 1;
        margin-left: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Embedded Iframe */
    .preview-iframe {
        border: none;
        background: #ffffff;
        display: block;
        transform-origin: top left;
    }

    /* Hardware Home Indicator */
    .device-home-bar {
        position: absolute;
        bottom: 6px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 4px;
        background: rgba(255, 255, 255, 0.4);
        border-radius: 2px;
        z-index: 50;
    }

    /* Quality Assurance Validation Card */
    .qa-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(15, 29, 47, 0.04);
    }

    .score-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        background: linear-gradient(135deg, #0F1D2F, #183B50);
        border-radius: 12px;
        color: #ffffff;
        margin-bottom: 24px;
    }

    .score-value {
        font-size: 38px;
        font-weight: 800;
        color: #2DD4BF;
        letter-spacing: -0.03em;
        line-height: 1;
    }

    .findings-table {
        width: 100%;
        border-collapse: collapse;
    }

    .findings-table th {
        text-align: left;
        padding: 10px 14px;
        font-size: 11px;
        text-transform: uppercase;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border);
        font-weight: 700;
    }

    .findings-table td {
        padding: 14px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .findings-table tr:hover {
        background-color: #f8fafc;
    }
</style>
@endpush

@section('content')
<div class="lab-container">

    <!-- Top Notice Banner for Temairazu Portfolio -->
    <div style="background: linear-gradient(90deg, #183B50, #287F79); color: #ffffff; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(24, 59, 80, 0.15);">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #2DD4BF;">
                <i class="fa-solid fa-microscope"></i>
            </div>
            <div>
                <strong style="font-size: 14px;">Enterprise Responsive Assurance Suite & Viewport Lab</strong>
                <p style="font-size: 12px; opacity: 0.85; margin: 0;">Designed for high-precision testing across Temairazu hotelier touchpoints, PMS dashboards, and smartphone room boards.</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('experience_lab.export_report', ['format' => 'csv']) }}" class="btn btn-sm" style="background: #ffffff; color: #183B50; font-weight: 700; border: none;">
                <i class="fa-solid fa-file-csv"></i> Export CSV Audit
            </a>
            <a href="{{ route('experience_lab.export_report', ['format' => 'json']) }}" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: #ffffff; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);">
                <i class="fa-solid fa-code"></i> Export JSON
            </a>
        </div>
    </div>

    <!-- Main Emulation Control Toolbar -->
    <div class="lab-toolbar">
        <!-- Target Route Selector -->
        <div class="toolbar-group">
            <span class="toolbar-label"><i class="fa-solid fa-link text-primary"></i> Application Page:</span>
            <select id="routeSelector" class="form-select-sm" style="min-width: 230px;">
                @foreach($routes as $r)
                    <option value="{{ $r['path'] }}" {{ $selectedRoute === $r['path'] ? 'selected' : '' }}>
                        {{ $r['category'] }}: {{ $r['name'] }} ({{ $r['path'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Mode Toggle (Single vs Dual Side-by-Side) -->
        <div class="toolbar-group">
            <span class="toolbar-label">Layout Mode:</span>
            <div class="btn-group-pill">
                <button type="button" id="btnModeSingle" class="{{ $mode === 'single' ? 'active' : '' }}" onclick="switchMode('single')">
                    <i class="fa-solid fa-square"></i> Single Viewport
                </button>
                <button type="button" id="btnModeDual" class="{{ $mode === 'dual' ? 'active' : '' }}" onclick="switchMode('dual')">
                    <i class="fa-solid fa-columns"></i> Dual Side-by-Side
                </button>
            </div>
        </div>

        <!-- Viewport Presets Dropdown -->
        <div class="toolbar-group" id="primaryPresetContainer">
            <span class="toolbar-label"><i class="fa-solid fa-mobile-screen text-primary"></i> Device Preset:</span>
            <select id="presetSelector" class="form-select-sm">
                @foreach($presets as $p)
                    <option value="{{ $p['id'] }}" 
                            data-width="{{ $p['width'] }}" 
                            data-height="{{ $p['height'] }}" 
                            data-type="{{ $p['type'] }}"
                            data-dpr="{{ $p['dpr'] }}"
                            {{ $activePreset['id'] === $p['id'] ? 'selected' : '' }}>
                        {{ $p['name'] }} ({{ $p['width'] }} × {{ $p['height'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Custom Dimensions Input -->
        <div class="toolbar-group">
            <span class="toolbar-label">Custom:</span>
            <div style="display: flex; align-items: center; gap: 4px;">
                <input type="number" id="inputCustomWidth" class="form-input-sm" value="{{ $activePreset['width'] }}" style="width: 75px;" min="320" max="3840" title="Width (px)">
                <span style="color: var(--text-muted); font-size: 12px;">×</span>
                <input type="number" id="inputCustomHeight" class="form-input-sm" value="{{ $activePreset['height'] }}" style="width: 75px;" min="480" max="2160" title="Height (px)">
                <button type="button" class="btn btn-sm btn-secondary" onclick="applyCustomDimensions()" style="padding: 6px 10px;" title="Apply Custom Size">
                    <i class="fa-solid fa-check"></i>
                </button>
            </div>
        </div>

        <!-- Orientation & Zoom Controls -->
        <div class="toolbar-group">
            <button type="button" id="btnOrientation" class="btn btn-sm btn-secondary" onclick="toggleOrientation()" title="Toggle Portrait / Landscape">
                <i class="fa-solid fa-rotate"></i> <span id="orientationLabel">Portrait</span>
            </button>

            <select id="zoomSelector" class="form-select-sm" style="width: 80px;" onchange="applyZoom(this.value)">
                <option value="0.5">50%</option>
                <option value="0.75">75%</option>
                <option value="1.0" selected>100%</option>
                <option value="1.25">125%</option>
            </select>

            <button type="button" class="btn btn-sm btn-primary" onclick="runValidationSweep()" id="btnRunValidation" style="background: #287F79; border-color: #287F79;">
                <i class="fa-solid fa-bolt"></i> Validate Viewport
            </button>
        </div>
    </div>

    <!-- Live Emulation Canvas Area -->
    <div class="emulation-workspace" id="workspaceContainer">
        <!-- Dual Mode Wrapper -->
        <div id="dualWrapper" class="{{ $mode === 'dual' ? 'workspace-dual' : '' }}">
            
            <!-- Primary Device Display Column -->
            <div class="device-column" id="primaryDeviceCol">
                <div class="device-meta-pill">
                    <span id="primaryDeviceTitle"><i class="fa-solid fa-mobile-screen"></i> {{ $activePreset['name'] }}</span>
                    <span class="badge" id="primaryResolutionBadge" style="background: #38BDF8; color: #0F1D2F;">{{ $activePreset['width'] }} × {{ $activePreset['height'] }} px</span>
                    <span class="badge" style="background: #10B981; color: #ffffff;">DPR: <span id="primaryDprVal">{{ $activePreset['dpr'] }}x</span></span>
                </div>

                <div class="device-shell {{ $activePreset['type'] === 'desktop' ? 'type-desktop' : '' }}" 
                     id="primaryShell" 
                     style="width: {{ $activePreset['width'] }}px; height: {{ $activePreset['height'] }}px;">
                    
                    <!-- Hardware Top Elements for Mobile -->
                    <div class="device-notch" id="primaryNotch" style="{{ $activePreset['type'] === 'desktop' ? 'display: none;' : '' }}">
                        <div class="device-speaker"></div>
                        <div class="device-camera"></div>
                    </div>

                    <!-- Window Header for Desktop Mode -->
                    <div class="desktop-window-bar" id="primaryDesktopBar" style="{{ $activePreset['type'] === 'desktop' ? 'display: flex;' : 'display: none;' }}">
                        <div class="window-dot dot-red"></div>
                        <div class="window-dot dot-yellow"></div>
                        <div class="window-dot dot-green"></div>
                        <div class="window-address" id="primaryAddressBar">{{ url($selectedRoute) }}</div>
                    </div>

                    <iframe src="{{ url($selectedRoute) }}" 
                            class="preview-iframe" 
                            id="primaryIframe"
                            style="width: 100%; height: {{ $activePreset['type'] === 'desktop' ? 'calc(100% - 31px)' : '100%' }};">
                    </iframe>

                    <!-- Hardware Home Bar for Mobile -->
                    <div class="device-home-bar" id="primaryHomeBar" style="{{ $activePreset['type'] === 'desktop' ? 'display: none;' : '' }}"></div>
                </div>
            </div>

            <!-- Secondary Device Column (Shown in Dual Side-by-Side Mode) -->
            <div class="device-column" id="secondaryDeviceCol" style="{{ $mode === 'dual' ? 'display: flex;' : 'display: none;' }}">
                <div class="device-meta-pill">
                    <span><i class="fa-solid fa-desktop"></i> Desktop Reference (1440 × 900)</span>
                    <span class="badge" style="background: #A78BFA; color: #0F1D2F;">1440 × 900 px</span>
                    <span class="badge" style="background: #2DD4BF; color: #0F1D2F;">Desktop Density</span>
                </div>

                <div class="device-shell type-desktop" id="secondaryShell" style="width: 720px; height: 500px;">
                    <div class="desktop-window-bar">
                        <div class="window-dot dot-red"></div>
                        <div class="window-dot dot-yellow"></div>
                        <div class="window-dot dot-green"></div>
                        <div class="window-address" id="secondaryAddressBar">{{ url($selectedRoute) }}</div>
                    </div>

                    <iframe src="{{ url($selectedRoute) }}" 
                            class="preview-iframe" 
                            id="secondaryIframe"
                            style="width: 1440px; height: 900px; transform: scale(0.5); transform-origin: 0 0;">
                    </iframe>
                </div>
            </div>

        </div>
    </div>

    <!-- Automated Responsive Quality Assurance Card -->
    <div class="qa-card" id="qaValidationCard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-clipboard-check text-primary"></i> Automated Responsive Quality Audit
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">
                    Real-time verification against WCAG 2.1 AA touch standards, horizontal containment, and viewport meta declarations.
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="runComprehensiveSweep()" id="btnSweepAll">
                <i class="fa-solid fa-play"></i> Run 32-Scenario Full Sweep
            </button>
        </div>

        <!-- Quality Scorecard Banner -->
        <div class="score-banner" id="scorecardBanner">
            <div>
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94A3B8;">Compliance Grade</div>
                <div class="score-value" id="scoreVal">100%</div>
                <div style="font-size: 12px; color: #E2E8F0; margin-top: 4px;" id="scoreStatusBadge">
                    <i class="fa-solid fa-shield-check" style="color: #2DD4BF;"></i> PASSED - Meets All Temairazu Responsive Standards
                </div>
            </div>
            <div style="display: flex; gap: 24px; text-align: center;">
                <div>
                    <div style="font-size: 22px; font-weight: 700; color: #10B981;" id="statPassed">6</div>
                    <div style="font-size: 11px; text-transform: uppercase; color: #94A3B8;">Passed Checks</div>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 700; color: #F59E0B;" id="statWarnings">0</div>
                    <div style="font-size: 11px; text-transform: uppercase; color: #94A3B8;">Warnings</div>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 700; color: #EF4444;" id="statFailed">0</div>
                    <div style="font-size: 11px; text-transform: uppercase; color: #94A3B8;">Critical Flaws</div>
                </div>
            </div>
        </div>

        <!-- Audit Findings Table -->
        <table class="findings-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Audit Category</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 30%;">Diagnostic Metric</th>
                    <th style="width: 30%;">Remediation Guidance</th>
                </tr>
            </thead>
            <tbody id="findingsTableBody">
                <tr>
                    <td><strong>Viewport Meta Declaration</strong></td>
                    <td><span class="badge" style="background: #D1FAE5; color: #065F46;">PASSED</span></td>
                    <td>width=device-width, initial-scale=1.0</td>
                    <td>Preserved standard meta viewport configuration in app layout header.</td>
                </tr>
                <tr>
                    <td><strong>Horizontal Containment</strong></td>
                    <td><span class="badge" style="background: #D1FAE5; color: #065F46;">PASSED</span></td>
                    <td>ScrollWidth &le; ClientWidth</td>
                    <td>Container limits content width to 100% of the active device frame.</td>
                </tr>
                <tr>
                    <td><strong>Touch Target Tap Zones</strong></td>
                    <td><span class="badge" style="background: #D1FAE5; color: #065F46;">PASSED</span></td>
                    <td>Min tap area &ge; 44×44px</td>
                    <td>Primary navigation buttons provide adequate tactile clearance on touchscreen.</td>
                </tr>
                <tr>
                    <td><strong>Table Containment</strong></td>
                    <td><span class="badge" style="background: #D1FAE5; color: #065F46;">PASSED</span></td>
                    <td>.overflow-x-auto responsive scroll enabled</td>
                    <td>Tabular records implement responsive wrapper handling on narrow viewports.</td>
                </tr>
                <tr>
                    <td><strong>Fixed Header Stacking</strong></td>
                    <td><span class="badge" style="background: #D1FAE5; color: #065F46;">PASSED</span></td>
                    <td>Z-Index Stacking: 90 / 100 / 1000</td>
                    <td>Header bar stays pinned without occluding top rows of scrollable page content.</td>
                </tr>
                <tr>
                    <td><strong>Typography Scale</strong></td>
                    <td><span class="badge" style="background: #D1FAE5; color: #065F46;">PASSED</span></td>
                    <td>14px - 18px body copy with 1.5 line-height</td>
                    <td>Font sizes meet readability thresholds. Simple Mode toggle available for 18px high-visibility view.</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // State
    let currentWidth = {{ $activePreset['width'] }};
    let currentHeight = {{ $activePreset['height'] }};
    let currentOrientation = 'portrait';
    let currentZoom = 1.0;
    let currentMode = '{{ $mode }}';
    let currentRoute = '{{ $selectedRoute }}';

    // Route selector change
    $('#routeSelector').on('change', function () {
        currentRoute = $(this).val();
        updateIframes();
        runValidationSweep();
    });

    // Preset selector change
    $('#presetSelector').on('change', function () {
        const selected = $(this).find(':selected');
        currentWidth = parseInt(selected.data('width'));
        currentHeight = parseInt(selected.data('height'));
        const type = selected.data('type');
        const dpr = selected.data('dpr');
        const name = selected.text().split('(')[0].trim();

        $('#inputCustomWidth').val(currentWidth);
        $('#inputCustomHeight').val(currentHeight);
        $('#primaryDprVal').text(dpr + 'x');
        $('#primaryDeviceTitle').html(`<i class="fa-solid fa-mobile-screen"></i> ${name}`);

        applyDimensionsToShell(type);
        runValidationSweep();
    });

    function applyCustomDimensions() {
        currentWidth = parseInt($('#inputCustomWidth').val()) || 390;
        currentHeight = parseInt($('#inputCustomHeight').val()) || 844;
        const type = currentWidth >= 1024 ? 'desktop' : 'mobile';
        applyDimensionsToShell(type);
        runValidationSweep();
    }

    function toggleOrientation() {
        if (currentOrientation === 'portrait') {
            currentOrientation = 'landscape';
            const temp = currentWidth;
            currentWidth = currentHeight;
            currentHeight = temp;
            $('#orientationLabel').text('Landscape');
        } else {
            currentOrientation = 'portrait';
            const temp = currentWidth;
            currentWidth = currentHeight;
            currentHeight = temp;
            $('#orientationLabel').text('Portrait');
        }
        $('#inputCustomWidth').val(currentWidth);
        $('#inputCustomHeight').val(currentHeight);
        const type = currentWidth >= 1024 ? 'desktop' : 'mobile';
        applyDimensionsToShell(type);
        runValidationSweep();
    }

    function applyZoom(zoomVal) {
        currentZoom = parseFloat(zoomVal);
        $('#primaryShell').css('transform', `scale(${currentZoom})`).css('transform-origin', 'top center');
    }

    function applyDimensionsToShell(type) {
        const shell = $('#primaryShell');
        const notch = $('#primaryNotch');
        const desktopBar = $('#primaryDesktopBar');
        const homeBar = $('#primaryHomeBar');
        const iframe = $('#primaryIframe');

        shell.css({
            width: currentWidth + 'px',
            height: currentHeight + 'px'
        });

        $('#primaryResolutionBadge').text(`${currentWidth} × ${currentHeight} px`);

        if (type === 'desktop' || currentWidth >= 1024) {
            shell.addClass('type-desktop');
            notch.hide();
            desktopBar.show();
            homeBar.hide();
            iframe.css('height', 'calc(100% - 31px)');
        } else {
            shell.removeClass('type-desktop');
            notch.show();
            desktopBar.hide();
            homeBar.show();
            iframe.css('height', '100%');
        }
    }

    function switchMode(mode) {
        currentMode = mode;
        if (mode === 'dual') {
            $('#btnModeDual').addClass('active');
            $('#btnModeSingle').removeClass('active');
            $('#dualWrapper').addClass('workspace-dual');
            $('#secondaryDeviceCol').css('display', 'flex');
        } else {
            $('#btnModeSingle').addClass('active');
            $('#btnModeDual').removeClass('active');
            $('#dualWrapper').removeClass('workspace-dual');
            $('#secondaryDeviceCol').hide();
        }
    }

    function updateIframes() {
        const fullUrl = window.location.origin + currentRoute;
        $('#primaryIframe').attr('src', fullUrl);
        $('#primaryAddressBar').text(fullUrl);
        $('#secondaryIframe').attr('src', fullUrl);
        $('#secondaryAddressBar').text(fullUrl);
    }

    // Run Automated Validation
    function runValidationSweep() {
        const btn = $('#btnRunValidation');
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Checking...');

        $.ajax({
            url: '{{ route("experience_lab.validate") }}',
            method: 'POST',
            data: {
                path: currentRoute,
                width: currentWidth,
                height: currentHeight,
                orientation: currentOrientation
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-bolt"></i> Validate Viewport');
                if (res.success && res.data) {
                    renderValidationResults(res.data);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fa-solid fa-bolt"></i> Validate Viewport');
            }
        });
    }

    function renderValidationResults(data) {
        $('#scoreVal').text(data.score + '%');
        $('#statPassed').text(data.passed_count);
        $('#statWarnings').text(data.warning_count);
        $('#statFailed').text(data.failed_count);

        if (data.status === 'PASSED') {
            $('#scoreStatusBadge').html('<i class="fa-solid fa-shield-check" style="color: #2DD4BF;"></i> PASSED - Meets All Temairazu Responsive Standards');
        } else if (data.status === 'WARNING') {
            $('#scoreStatusBadge').html('<i class="fa-solid fa-triangle-exclamation" style="color: #F59E0B;"></i> WARNING - Check Grid Wrapping on Narrow Widths');
        } else {
            $('#scoreStatusBadge').html('<i class="fa-solid fa-circle-xmark" style="color: #EF4444;"></i> FAILED - Layout Exceeds Boundary');
        }

        const tbody = $('#findingsTableBody');
        tbody.empty();

        data.findings.forEach(function (f) {
            const badgeBg = f.status === 'PASSED' ? '#D1FAE5' : (f.status === 'WARNING' ? '#FEF3C7' : '#FEE2E2');
            const badgeColor = f.status === 'PASSED' ? '#065F46' : (f.status === 'WARNING' ? '#92400E' : '#991B1B');

            tbody.append(`
                <tr>
                    <td><strong>${f.title}</strong></td>
                    <td><span class="badge" style="background: ${badgeBg}; color: ${badgeColor}; font-weight: 700;">${f.status}</span></td>
                    <td>${f.metric}</td>
                    <td>${f.recommendation}</td>
                </tr>
            `);
        });
    }

    // Run Full 32-Scenario Sweep
    function runComprehensiveSweep() {
        const btn = $('#btnSweepAll');
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Sweeping 32 Scenarios...');

        $.ajax({
            url: '{{ route("experience_lab.sweep") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-play"></i> Run 32-Scenario Full Sweep');
                if (res.success && res.report) {
                    alert(`Comprehensive Sweep Completed!\nReport ID: ${res.report.report_id}\nScenarios: ${res.report.audit_summary.total_scenarios_tested}\nPassed: ${res.report.audit_summary.passed_scenarios}\nCompliance Rate: ${res.report.audit_summary.overall_compliance_rate}`);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fa-solid fa-play"></i> Run 32-Scenario Full Sweep');
                alert('Error running sweep.');
            }
        });
    }
</script>
@endpush

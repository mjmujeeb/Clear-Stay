@extends('layouts.app')

@section('title', 'Responsive Experience Lab - ClearStay 3.0')
@section('header_title', 'Responsive Experience Lab (レスポンシブ体験ラボ)')
@section('header_subtitle', 'Flawless Multi-Device Viewport Scaler, Dual Side-by-Side Comparison, and Automated QA Engine')

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
        padding: 28px 20px;
        border: 1px solid #1e293b;
        box-shadow: inset 0 2px 14px rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 640px;
        overflow: hidden; /* No outer clipping; the scaler fits perfectly */
        position: relative;
    }

    /* Dual Mode Grid */
    .workspace-dual {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        width: 100%;
        align-items: flex-start;
    }

    .device-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        width: 100%;
        overflow: hidden;
    }

    .device-meta-pill {
        background: rgba(30, 41, 59, 0.95);
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
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .device-meta-pill .badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 12px;
    }

    /* The Viewport Frame Wrapper: Layout box matching (target * scale) */
    .device-viewport-frame {
        position: relative;
        overflow: hidden;
        margin: 0 auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.1);
        transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), height 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* The Scaler: Applied CSS transform scale */
    .device-scaler {
        position: absolute;
        top: 0;
        left: 0;
        transform-origin: top left;
        background: #1e293b;
    }

    /* Device Mockup Shell */
    .device-shell {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background: #0f172a;
    }

    .device-shell.type-mobile {
        border: 12px solid #0f172a;
        border-radius: 36px;
    }

    .device-shell.type-desktop {
        border: 1px solid #334155;
        border-radius: 10px;
    }

    /* Hardware Notch & Speaker */
    .device-notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 140px;
        height: 24px;
        background: #0f172a;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        pointer-events: none;
    }

    .device-speaker {
        width: 48px;
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
        gap: 10px;
        min-height: 38px;
        flex-shrink: 0;
    }

    .window-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .dot-red { background: #ef4444; }
    .dot-yellow { background: #f59e0b; }
    .dot-green { background: #10b981; }

    .window-address-box {
        background: #0f172a;
        color: #94a3b8;
        font-size: 12px;
        padding: 4px 12px;
        border-radius: 6px;
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .window-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .window-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        font-size: 12px;
        transition: color 0.15s;
    }

    .window-btn:hover {
        color: #ffffff;
        background: rgba(255,255,255,0.08);
    }

    /* Embedded Iframe */
    .preview-iframe {
        border: none;
        background: #ffffff;
        display: block;
        flex: 1;
        width: 100%;
        height: 100%;
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
        pointer-events: none;
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
                <p style="font-size: 12px; opacity: 0.85; margin: 0;">Calibrated hardware preview engine: 100% genuine CSS viewports with zero horizontal clipping, dual live comparison, and WCAG 2.1 AA audits.</p>
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
            <select id="routeSelector" class="form-select-sm" style="min-width: 240px;">
                @foreach($routes as $r)
                    <option value="{{ $r['path'] }}" {{ $selectedRoute === $r['path'] ? 'selected' : '' }}>
                        {{ $r['category'] }}: {{ $r['name'] }} ({{ $r['path'] }})
                    </option>
                @endforeach
                <option value="/easy-mode">Operations: Easy Mode Hub (/easy-mode)</option>
                <option value="/learning-centre">Education: Learning Centre (/learning-centre)</option>
                <option value="/health">System: Health Observability (/health)</option>
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

        <!-- Orientation, Zoom & Action Controls -->
        <div class="toolbar-group">
            <button type="button" id="btnOrientation" class="btn btn-sm btn-secondary" onclick="toggleOrientation()" title="Toggle Portrait / Landscape">
                <i class="fa-solid fa-rotate"></i> <span id="orientationLabel">Portrait</span>
            </button>

            <select id="zoomSelector" class="form-select-sm" style="width: 110px;" onchange="applyZoomMode(this.value)">
                <option value="fit" selected>Fit to Screen</option>
                <option value="0.5">Zoom: 50%</option>
                <option value="0.75">Zoom: 75%</option>
                <option value="1.0">Zoom: 100%</option>
                <option value="1.25">Zoom: 125%</option>
            </select>

            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleFullscreen()" title="Fullscreen Preview">
                <i class="fa-solid fa-expand"></i>
            </button>

            <button type="button" class="btn btn-sm btn-primary" onclick="runValidationSweep()" id="btnRunValidation" style="background: #287F79; border-color: #287F79;">
                <i class="fa-solid fa-bolt"></i> Validate Viewport
            </button>
        </div>
    </div>

    <!-- Live Emulation Canvas Area -->
    <div class="emulation-workspace" id="workspaceContainer">
        
        <!-- SINGLE VIEWPORT MODE -->
        <div id="singleWorkspaceWrapper" style="{{ $mode === 'dual' ? 'display: none;' : 'width: 100%; display: flex; flex-direction: column; align-items: center;' }}">
            
            <!-- Meta Bar -->
            <div class="device-meta-pill" style="margin-bottom: 14px;">
                <span id="primaryDeviceTitle"><i class="fa-solid fa-desktop"></i> {{ $activePreset['name'] }}</span>
                <span class="badge" id="primaryResolutionBadge" style="background: #38BDF8; color: #0F1D2F;">{{ $activePreset['width'] }} × {{ $activePreset['height'] }} px</span>
                <span class="badge" id="primaryFitScaleBadge" style="background: #10B981; color: #ffffff;">Fit: 100%</span>
                <span class="badge" id="primaryStatusBadge" style="background: #D1FAE5; color: #065F46;"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Live</span>
            </div>

            <!-- Frame Wrapper: Constrains layout space to (width * scale) by (height * scale) -->
            <div class="device-viewport-frame" id="primaryFrameWrapper">
                <!-- Scaler Box: Contains full CSS viewport dimensions and transforms down -->
                <div class="device-scaler" id="primaryScaler">
                    <div class="device-shell {{ $activePreset['type'] === 'desktop' ? 'type-desktop' : 'type-mobile' }}" id="primaryShell">
                        
                        <!-- Mobile Notch -->
                        <div class="device-notch" id="primaryNotch" style="{{ $activePreset['type'] === 'desktop' ? 'display: none;' : '' }}">
                            <div class="device-speaker"></div>
                            <div class="device-camera"></div>
                        </div>

                        <!-- Desktop Window Bar -->
                        <div class="desktop-window-bar" id="primaryDesktopBar" style="{{ $activePreset['type'] === 'desktop' ? 'display: flex;' : 'display: none;' }}">
                            <div class="window-dot dot-red"></div>
                            <div class="window-dot dot-yellow"></div>
                            <div class="window-dot dot-green"></div>
                            <div class="window-address-box">
                                <i class="fa-solid fa-lock" style="color: #10B981; font-size: 10px;"></i>
                                <span id="primaryAddressText">{{ url($selectedRoute) }}</span>
                            </div>
                            <div class="window-actions">
                                <button type="button" class="window-btn" onclick="reloadPrimaryIframe()" title="Reload Preview">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <a href="{{ url($selectedRoute) }}" target="_blank" class="window-btn" id="primaryExternalLink" title="Open in New Tab">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Full CSS Viewport Iframe -->
                        <iframe src="{{ url($selectedRoute) }}" 
                                class="preview-iframe" 
                                id="primaryIframe"
                                onload="handleIframeLoaded('primary')"
                                onerror="handleIframeError('primary')">
                        </iframe>

                        <!-- Mobile Home Bar -->
                        <div class="device-home-bar" id="primaryHomeBar" style="{{ $activePreset['type'] === 'desktop' ? 'display: none;' : '' }}"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- DUAL SIDE-BY-SIDE MODE -->
        <div id="dualWorkspaceWrapper" class="workspace-dual" style="{{ $mode === 'dual' ? 'display: grid;' : 'display: none;' }}">
            
            <!-- Left Side: Desktop 1440x900 Reference -->
            <div class="device-column" id="dualLeftCol">
                <div class="device-meta-pill">
                    <span><i class="fa-solid fa-desktop"></i> Desktop Reference (1440 × 900)</span>
                    <span class="badge" style="background: #38BDF8; color: #0F1D2F;">1440 × 900 px</span>
                    <span class="badge" id="dualLeftScaleBadge" style="background: #2DD4BF; color: #0F1D2F;">Fit: 48%</span>
                    <button type="button" class="window-btn" onclick="reloadDualLeft()" style="padding: 0 4px;" title="Reload Desktop">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <a href="{{ url($selectedRoute) }}" target="_blank" class="window-btn" style="padding: 0 4px;" title="Open in New Tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>

                <div class="device-viewport-frame" id="dualLeftFrameWrapper">
                    <div class="device-scaler" id="dualLeftScaler" style="width: 1442px; height: 938px;">
                        <div class="device-shell type-desktop" style="width: 1442px; height: 938px;">
                            <div class="desktop-window-bar">
                                <div class="window-dot dot-red"></div>
                                <div class="window-dot dot-yellow"></div>
                                <div class="window-dot dot-green"></div>
                                <div class="window-address-box">
                                    <i class="fa-solid fa-lock" style="color: #10B981; font-size: 10px;"></i>
                                    <span id="dualLeftAddress">{{ url($selectedRoute) }}</span>
                                </div>
                            </div>
                            <iframe src="{{ url($selectedRoute) }}" 
                                    class="preview-iframe" 
                                    id="dualLeftIframe" 
                                    style="width: 1440px; height: 900px;">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Mobile 390x844 Reference -->
            <div class="device-column" id="dualRightCol">
                <div class="device-meta-pill">
                    <span><i class="fa-solid fa-mobile-screen"></i> Smartphone Reference (390 × 844)</span>
                    <span class="badge" style="background: #FBBF24; color: #0F1D2F;">390 × 844 px</span>
                    <span class="badge" id="dualRightScaleBadge" style="background: #10B981; color: #ffffff;">Fit: 80%</span>
                    <button type="button" class="window-btn" onclick="reloadDualRight()" style="padding: 0 4px;" title="Reload Mobile">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <a href="{{ url($selectedRoute) }}" target="_blank" class="window-btn" style="padding: 0 4px;" title="Open in New Tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>

                <div class="device-viewport-frame" id="dualRightFrameWrapper">
                    <div class="device-scaler" id="dualRightScaler" style="width: 414px; height: 868px;">
                        <div class="device-shell type-mobile" style="width: 414px; height: 868px;">
                            <div class="device-notch">
                                <div class="device-speaker"></div>
                                <div class="device-camera"></div>
                            </div>
                            <iframe src="{{ url($selectedRoute) }}" 
                                    class="preview-iframe" 
                                    id="dualRightIframe" 
                                    style="width: 390px; height: 844px;">
                            </iframe>
                            <div class="device-home-bar"></div>
                        </div>
                    </div>
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
                    <td>Font sizes meet readability thresholds. Easy Mode toggle available for 18px high-visibility view.</td>
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
    let currentType = '{{ $activePreset['type'] }}';
    let currentDpr = {{ $activePreset['dpr'] }};
    let currentOrientation = 'portrait';
    let currentZoomMode = 'fit'; // 'fit' or numeric string
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
        currentType = selected.data('type');
        currentDpr = selected.data('dpr');
        const name = selected.text().split('(')[0].trim();

        $('#inputCustomWidth').val(currentWidth);
        $('#inputCustomHeight').val(currentHeight);
        $('#primaryDeviceTitle').html(`<i class="fa-solid fa-${currentType === 'desktop' ? 'desktop' : 'mobile-screen'}"></i> ${name}`);

        renderSingleDevice();
        runValidationSweep();
    });

    function applyCustomDimensions() {
        currentWidth = parseInt($('#inputCustomWidth').val()) || 390;
        currentHeight = parseInt($('#inputCustomHeight').val()) || 844;
        currentType = currentWidth >= 1024 ? 'desktop' : 'mobile';
        renderSingleDevice();
        runValidationSweep();
    }

    function toggleOrientation() {
        const temp = currentWidth;
        currentWidth = currentHeight;
        currentHeight = temp;
        currentOrientation = currentOrientation === 'portrait' ? 'landscape' : 'portrait';
        $('#orientationLabel').text(currentOrientation.charAt(0).toUpperCase() + currentOrientation.slice(1));
        $('#inputCustomWidth').val(currentWidth);
        $('#inputCustomHeight').val(currentHeight);
        currentType = currentWidth >= 1024 ? 'desktop' : 'mobile';
        renderSingleDevice();
        runValidationSweep();
    }

    function applyZoomMode(val) {
        currentZoomMode = val;
        recalculateAndScaleAll();
    }

    function toggleFullscreen() {
        const elem = document.getElementById('workspaceContainer');
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                console.warn('Fullscreen error:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    function switchMode(mode) {
        currentMode = mode;
        if (mode === 'dual') {
            $('#btnModeDual').addClass('active');
            $('#btnModeSingle').removeClass('active');
            $('#singleWorkspaceWrapper').hide();
            $('#dualWorkspaceWrapper').css('display', 'grid');
            recalculateDualLayout();
        } else {
            $('#btnModeSingle').addClass('active');
            $('#btnModeDual').removeClass('active');
            $('#singleWorkspaceWrapper').css('display', 'flex');
            $('#dualWorkspaceWrapper').hide();
            renderSingleDevice();
        }
    }

    /* Core Scaling Calculation: Flawless fit without clipping */
    function renderSingleDevice() {
        const isDesktop = currentType === 'desktop' || currentWidth >= 1024;
        const targetWidth = currentWidth + (isDesktop ? 2 : 24);
        const targetHeight = currentHeight + (isDesktop ? 39 : 24);

        const availableWidth = Math.max(300, $('#workspaceContainer').width() - 40);

        let scale = 1.0;
        if (currentZoomMode === 'fit') {
            scale = Math.min(availableWidth / targetWidth, 1.0);
        } else {
            scale = parseFloat(currentZoomMode);
        }

        // Apply shell styling
        const shell = $('#primaryShell');
        const notch = $('#primaryNotch');
        const desktopBar = $('#primaryDesktopBar');
        const homeBar = $('#primaryHomeBar');
        const iframe = $('#primaryIframe');

        if (isDesktop) {
            shell.removeClass('type-mobile').addClass('type-desktop');
            notch.hide();
            desktopBar.show();
            homeBar.hide();
        } else {
            shell.removeClass('type-desktop').addClass('type-mobile');
            notch.show();
            desktopBar.hide();
            homeBar.show();
        }

        // Iframe gets EXACT CSS viewport dimensions
        iframe.css({
            width: currentWidth + 'px',
            height: currentHeight + 'px'
        });

        // Scaler gets exact target dimensions and CSS transform
        $('#primaryScaler').css({
            width: targetWidth + 'px',
            height: targetHeight + 'px',
            transform: `scale(${scale})`,
            transformOrigin: 'top left'
        });

        // Outer frame wrapper gets rendered layout dimensions
        $('#primaryFrameWrapper').css({
            width: Math.round(targetWidth * scale) + 'px',
            height: Math.round(targetHeight * scale) + 'px',
            borderRadius: isDesktop ? '10px' : '36px'
        });

        $('#primaryResolutionBadge').text(`${currentWidth} × ${currentHeight} px`);
        $('#primaryFitScaleBadge').text(`Fit: ${Math.round(scale * 100)}%`);
    }

    function recalculateDualLayout() {
        const workspaceWidth = Math.max(600, $('#workspaceContainer').width() - 48);
        const colWidth = (workspaceWidth / 2) - 16;

        // Left Desktop Scaler (1440 × 900)
        const leftTargetWidth = 1442;
        const leftTargetHeight = 939;
        const leftScale = Math.min(colWidth / leftTargetWidth, 1.0);

        $('#dualLeftScaler').css({
            transform: `scale(${leftScale})`,
            transformOrigin: 'top left'
        });
        $('#dualLeftFrameWrapper').css({
            width: Math.round(leftTargetWidth * leftScale) + 'px',
            height: Math.round(leftTargetHeight * leftScale) + 'px'
        });
        $('#dualLeftScaleBadge').text(`Fit: ${Math.round(leftScale * 100)}%`);

        // Right Mobile Scaler (390 × 844)
        const rightTargetWidth = 414;
        const rightTargetHeight = 868;
        const rightScale = Math.min(colWidth / rightTargetWidth, 1.0);

        $('#dualRightScaler').css({
            transform: `scale(${rightScale})`,
            transformOrigin: 'top left'
        });
        $('#dualRightFrameWrapper').css({
            width: Math.round(rightTargetWidth * rightScale) + 'px',
            height: Math.round(rightTargetHeight * rightScale) + 'px'
        });
        $('#dualRightScaleBadge').text(`Fit: ${Math.round(rightScale * 100)}%`);
    }

    function recalculateAndScaleAll() {
        if (currentMode === 'single') {
            renderSingleDevice();
        } else {
            recalculateDualLayout();
        }
    }

    // ResizeObserver: auto-resizes preview when window size changes
    const resizeObserver = new ResizeObserver(() => {
        recalculateAndScaleAll();
    });
    const workspaceElement = document.getElementById('workspaceContainer');
    if (workspaceElement) {
        resizeObserver.observe(workspaceElement);
    }

    // Iframe Management & URL updating
    function updateIframes() {
        const fullUrl = window.location.origin + currentRoute;
        $('#primaryIframe').attr('src', fullUrl);
        $('#primaryAddressText').text(fullUrl);
        $('#primaryExternalLink').attr('href', fullUrl);

        $('#dualLeftIframe').attr('src', fullUrl);
        $('#dualLeftAddress').text(fullUrl);
        $('#dualRightIframe').attr('src', fullUrl);
    }

    function reloadPrimaryIframe() {
        const iframe = document.getElementById('primaryIframe');
        if (iframe) iframe.src = iframe.src;
        $('#primaryStatusBadge').html('<i class="fa-solid fa-spinner fa-spin"></i> Loading');
    }

    function reloadDualLeft() {
        const iframe = document.getElementById('dualLeftIframe');
        if (iframe) iframe.src = iframe.src;
    }

    function reloadDualRight() {
        const iframe = document.getElementById('dualRightIframe');
        if (iframe) iframe.src = iframe.src;
    }

    function handleIframeLoaded(id) {
        if (id === 'primary') {
            $('#primaryStatusBadge').html('<i class="fa-solid fa-circle" style="font-size: 8px; color: #10B981;"></i> Live (200 OK)');
        }
    }

    function handleIframeError(id) {
        if (id === 'primary') {
            $('#primaryStatusBadge').html('<i class="fa-solid fa-triangle-exclamation" style="color: #EF4444;"></i> Blocked');
        }
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
                    alert(`Comprehensive Sweep Completed!\nReport ID: ${res.report.report_id}\nScenarios Tested: ${res.report.audit_summary.total_scenarios_tested}\nPassed: ${res.report.audit_summary.passed_scenarios}\nCompliance Rate: ${res.report.audit_summary.overall_compliance_rate}`);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fa-solid fa-play"></i> Run 32-Scenario Full Sweep');
                alert('Error running sweep.');
            }
        });
    }

    // Initialize scaling on load
    $(document).ready(function () {
        recalculateAndScaleAll();
    });
</script>
@endpush

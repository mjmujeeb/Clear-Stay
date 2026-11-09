<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F1D2F">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'ClearStay 3.0') - {{ __('clearstay.tagline') }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #183B50;
            --primary-dark: #0F2942;
            --primary-accent: #287F79;
            --accent-gold: #E8A64A;
            --sidebar-bg: #0F1D2F;
            --sidebar-text: #94A3B8;
            --sidebar-active: #2DD4BF;
            --bg: #F8FAFC;
            --card-bg: #FFFFFF;
            --border: #E2E8F0;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --success: #10B981;
            --success-light: #D1FAE5;
            --warning: #F59E0B;
            --warning-light: #FEF3C7;
            --danger: #EF4444;
            --danger-light: #FEE2E2;
            --info: #0284C7;
            --info-light: #E0F2FE;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            box-shadow: 4px 0 16px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .brand-text h1 {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .brand-text span {
            font-size: 11px;
            color: #38bdf8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sidebar-menu {
            list-style: none;
            padding: 18px 12px;
            flex: 1;
            overflow-y: auto;
        }

        .menu-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            padding: 12px 14px 6px;
            font-weight: 700;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 4px;
        }

        .menu-item a:hover {
            background: rgba(255,255,255,0.06);
            color: #ffffff;
        }

        .menu-item.active a {
            background: linear-gradient(90deg, rgba(56, 189, 248, 0.15), rgba(56, 189, 248, 0.05));
            color: var(--sidebar-active);
            font-weight: 600;
            border-left: 3px solid var(--sidebar-active);
        }

        .menu-item i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.2);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #334155;
            color: #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 11px;
            color: #94a3b8;
            text-transform: capitalize;
        }

        /* Main Content */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Top Header */
        .top-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .header-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
        }

        .header-title p {
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hotel-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg);
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 13px;
        }

        .hotel-selector select {
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
        }

        .lang-switch {
            display: flex;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .lang-switch a {
            padding: 6px 12px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .lang-switch a.active {
            background: var(--primary);
            color: #ffffff;
        }

        /* Page Body */
        .page-content {
            padding: 28px;
            flex: 1;
        }

        /* Alert notifications */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-success {
            background: var(--success-light);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: var(--danger-light);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-info {
            background: var(--info-light);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Cards & Grids */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .card-body {
            padding: 24px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-clean { background: #d1fae5; color: #065f46; }
        .badge-dirty { background: #fee2e2; color: #991b1b; }
        .badge-inspecting { background: #fef3c7; color: #92400e; }
        .badge-maintenance { background: #f1f5f9; color: #475569; }
        .badge-occupied { background: #ede9fe; color: #5b21b6; }
        .badge-confirmed { background: #dbeafe; color: #1e40af; }
        .badge-checked_in { background: #d1fae5; color: #065f46; }
        .badge-checked_out { background: #f1f5f9; color: #475569; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table.data-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }

        table.data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        table.data-table tr:hover td {
            background: #f8fafc;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-container {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 640px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            animation: modalPop 0.25s ease-out;
        }

        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Accessibility & Contrast Profiles */
        .skip-link {
            position: absolute;
            top: -60px;
            left: 14px;
            background: #287F79;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            z-index: 10000;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: top 0.2s ease;
        }
        .skip-link:focus {
            top: 14px;
            outline: 3px solid #E8A64A;
        }

        /* Fluid Font Scaling (WCAG 1.4.10 Reflow Tested) */
        body.font-scale-125 { font-size: 110%; }
        body.font-scale-150 { font-size: 125%; }
        body.font-scale-200 { font-size: 140%; }
        body.font-scale-300 { font-size: 160%; }

        /* Motion Preference */
        body.reduced-motion *, body.reduced-motion *::before, body.reduced-motion *::after {
            animation: none !important;
            transition: none !important;
        }

        /* Contrast Themes (WCAG 2.2 AA / AAA) */
        body.theme-high_contrast_light {
            --bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --text-main: #000000;
            --text-muted: #1F2937;
            --border: #000000;
            --primary: #000000;
            --primary-dark: #111111;
        }
        body.theme-high_contrast_light .card,
        body.theme-high_contrast_light .top-header,
        body.theme-high_contrast_light table.data-table th,
        body.theme-high_contrast_light table.data-table td {
            border-color: #000000 !important;
        }

        body.theme-high_contrast_dark {
            --bg: #050505;
            --card-bg: #111111;
            --text-main: #FFFFFF;
            --text-muted: #CBD5E1;
            --border: #334155;
            --primary: #FBBF24;
            --primary-dark: #F59E0B;
        }
        body.theme-high_contrast_dark .top-header {
            background: #111111 !important;
            border-color: #334155 !important;
        }
        body.theme-high_contrast_dark .card {
            background: #111111 !important;
            border-color: #334155 !important;
        }
        body.theme-high_contrast_dark .data-table tr:hover td {
            background: #1E293B !important;
        }

        body.theme-monochrome {
            filter: grayscale(100%);
        }

        body.theme-colorblind .badge-clean { background: #DBEAFE !important; color: #1E40AF !important; }
        body.theme-colorblind .badge-dirty { background: #FEF3C7 !important; color: #92400E !important; }

        /* Critical Header Wrapping & Mobile Responsiveness Fix */
        .btn, .btn-sm, .badge, .lang-switch a {
            white-space: nowrap !important;
            flex-shrink: 0;
        }

        .mobile-sidebar-controls {
            display: none;
            padding: 14px 16px;
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-direction: column;
            gap: 12px;
        }

        /* Mobile Responsive */
        .mobile-menu-toggle {
            display: none;
        }

        @media (max-width: 900px) {
            .mobile-menu-toggle {
                display: inline-flex !important;
            }
            .sidebar {
                left: -260px;
            }
            .sidebar.mobile-open {
                left: 0;
            }
            .main-wrapper {
                margin-left: 0;
            }
            .page-content {
                padding: 16px;
            }
            .mobile-sidebar-controls {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .top-header {
                padding: 8px 12px !important;
                min-height: 54px;
            }
            .header-title h2 {
                font-size: 15px !important;
                max-width: 140px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .header-title p {
                display: none !important;
            }
            .header-controls {
                gap: 6px !important;
            }
            .search-trigger {
                min-width: 36px !important;
                width: 36px !important;
                height: 36px !important;
                padding: 0 !important;
                justify-content: center !important;
                border-radius: 8px !important;
            }
            .search-trigger span, .search-trigger kbd {
                display: none !important;
            }
            .hide-on-mobile {
                display: none !important;
            }
            .hotel-selector-header {
                display: none !important;
            }
        }

        @media (max-width: 480px) {
            .header-title h2 {
                max-width: 105px !important;
                font-size: 14px !important;
            }
            .hide-on-xs {
                display: none !important;
            }
        }

        /* Command Palette Search Styles */
        .search-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1050;
            align-items: flex-start;
            justify-content: center;
            padding-top: 10vh;
        }
        .search-modal-backdrop.active {
            display: flex;
        }
        .search-modal-dialog {
            background: #ffffff;
            width: 100%;
            max-width: 620px;
            border-radius: 14px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            animation: modalPop 0.2s ease-out;
            border: 1px solid var(--border);
        }
        .search-input-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: #ffffff;
        }
        .search-input-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-main);
            background: transparent;
        }
        .search-results-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 8px;
        }
        .search-category-header {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            padding: 8px 12px 4px;
        }
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.15s;
        }
        .search-result-item:hover, .search-result-item.highlighted {
            background: #f1f5f9;
        }
        .search-result-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #1e293b;
        }

        /* Simple Mode Accessibility Styles */
        body.simple-mode {
            font-size: 18px;
            line-height: 1.6;
        }
        body.simple-mode .card-body, body.simple-mode .table, body.simple-mode .page-content {
            font-size: 17px;
        }
        body.simple-mode .btn {
            padding: 12px 22px;
            font-size: 16px;
            font-weight: 700;
        }
        body.simple-mode .badge {
            font-size: 13px;
            padding: 6px 12px;
        }
        body.simple-mode .table th, body.simple-mode .table td {
            padding: 15px 18px;
        }
        body.simple-mode .sidebar-menu .menu-item a {
            font-size: 16px;
            padding: 14px 16px;
        }
        body.simple-mode .sidebar {
            width: 290px;
        }
        body.simple-mode .main-wrapper {
            margin-left: 290px;
        }

        /* ==========================================================================
           Interactive Help & Universal Tooltip System
           ========================================================================== */
        .clearstay-tooltip-bubble {
            position: fixed;
            z-index: 10000;
            max-width: 280px;
            padding: 8px 12px;
            background: #0F1D2F;
            color: #F8FAFC;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.45;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.12);
            pointer-events: none;
            opacity: 0;
            transform: translateY(4px);
            transition: opacity 0.15s ease, transform 0.15s ease;
        }
        .clearstay-tooltip-bubble.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .clearstay-tooltip-bubble .tooltip-title {
            font-weight: 700;
            color: #2DD4BF;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .clearstay-tooltip-bubble .tooltip-close {
            display: none;
            background: transparent;
            border: none;
            color: #94A3B8;
            cursor: pointer;
            margin-left: auto;
            font-size: 12px;
        }
        .clearstay-tooltip-bubble.touch-active .tooltip-close {
            display: inline-block;
        }
        .help-cue {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #475569;
            font-size: 10px;
            font-weight: 800;
            cursor: help;
            margin-left: 6px;
            vertical-align: middle;
            transition: all 0.2s;
            border: 1px solid #CBD5E1;
        }
        .help-cue:hover, .help-cue:focus {
            background: #287F79;
            color: #ffffff;
            border-color: #287F79;
            outline: none;
            transform: scale(1.1);
        }

        /* ==========================================================================
           Guided Next-Step Floating Card & Onboarding Modal
           ========================================================================== */
        .next-step-floater {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 990;
            background: #ffffff;
            border: 1px solid var(--border);
            border-left: 4px solid #287F79;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(15, 29, 47, 0.15);
            max-width: 360px;
            padding: 16px;
            animation: slideInUp 0.3s ease-out;
            display: none;
        }
        .next-step-floater.visible {
            display: block;
        }
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .next-step-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .next-step-tag {
            font-size: 10px;
            font-weight: 800;
            color: #287F79;
            background: #E6F3F2;
            padding: 2px 8px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .next-step-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .next-step-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.45;
            margin-bottom: 12px;
        }
        .next-step-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
    </style>

    @stack('styles')
</head>
@php
    $contrastTheme = session('clearstay_contrast_theme', auth()->user()?->contrast_theme ?? 'standard');
    $fontScale = session('clearstay_font_scale', auth()->user()?->font_scale ?? 100);
    $reducedMotion = session('clearstay_reduced_motion', auth()->user()?->reduced_motion ?? false);
    $currentHotelId = session('current_hotel_id') ?? auth()->user()?->hotel_id;
    $accessibleHotels = (auth()->user() && (auth()->user()->role === 'superadmin' || auth()->user()->role === 'owner'))
        ? \App\Models\Hotel::where('is_active', true)->get() 
        : \App\Models\Hotel::where('id', $currentHotelId ?? 1)->get();
@endphp
<body class="{{ session('clearstay_simple_mode') ? 'simple-mode' : '' }} theme-{{ $contrastTheme }} font-scale-{{ $fontScale }} {{ $reducedMotion ? 'reduced-motion' : '' }}">
    <!-- Skip to Main Content Link (WCAG 2.4.1) -->
    <a href="#mainContent" class="skip-link">本文へスキップ (Skip to main content)</a>

    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <div class="brand-logo" style="background: linear-gradient(135deg, #287F79, #183B50); border: 1px solid rgba(232, 166, 74, 0.4);">
                <i class="fa-solid fa-hotel" style="color: #E8A64A;"></i>
            </div>
            <div class="brand-text">
                <h1>ClearStay 4.0</h1>
                <span style="color: #2DD4BF;">Enterprise Suite</span>
            </div>
        </div>

        <!-- Mobile Drawer Controls (Visible on mobile screens <= 900px) -->
        <div class="mobile-sidebar-controls">
            <div style="font-size: 11px; font-weight: 700; color: #94A3B8; text-transform: uppercase;">
                <i class="fa-solid fa-building"></i> プロパティ切替 (Property)
            </div>
            <form action="{{ route('hotel.switch') }}" method="POST" id="drawerHotelSwitchForm">
                @csrf
                <select name="hotel_id" onchange="document.getElementById('drawerHotelSwitchForm').submit();" style="width: 100%; padding: 8px 10px; border-radius: 6px; background: #1E293B; color: #ffffff; border: 1px solid #334155; font-size: 12px; font-weight: 600;">
                    @foreach($accessibleHotels as $h)
                        <option value="{{ $h->id }}" {{ $currentHotelId == $h->id ? 'selected' : '' }}>
                            {{ $h->name }} ({{ $h->city }})
                        </option>
                    @endforeach
                </select>
            </form>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <div class="lang-switch">
                    <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    <a href="{{ route('locale.switch', 'ja') }}" class="{{ app()->getLocale() === 'ja' ? 'active' : '' }}">日本語</a>
                </div>
                <a href="{{ route('learning_centre.index') }}" class="btn btn-sm" style="background: rgba(40, 127, 121, 0.3); color: #2DD4BF; border: 1px solid #287F79; font-size: 11px;">
                    <i class="fa-solid fa-film"></i> 動画ガイド
                </a>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-header">Quality Assurance & Tools</li>
            <li class="menu-item {{ request()->routeIs('interview_demo.*') ? 'active' : '' }}">
                <a href="{{ route('interview_demo.index') }}" data-tooltip="Interactive live interview demonstration hub showcasing 10 core engineering workflows.">
                    <i class="fa-solid fa-bullhorn" style="color: #E8A64A;"></i>
                    <span>面接実演 (Interview Hub)</span>
                    <span class="badge" style="background: #E8A64A; color: #183B50; font-size: 10px; font-weight: 800; margin-left: auto; padding: 2px 6px;">10 Demos</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('experience_lab.*') ? 'active' : '' }}">
                <a href="{{ route('experience_lab.index') }}" data-tooltip="QA & developer environment for testing responsive viewports (360px-1920px), zero-clipping scaling, and automated responsive audits.">
                    <i class="fa-solid fa-microscope" style="color: #2DD4BF;"></i>
                    <span>体験ラボ (Experience Lab)</span>
                    <span class="badge" style="background: #287F79; color: #fff; font-size: 10px; margin-left: auto; padding: 2px 6px;">QA</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('easy_mode.*') ? 'active' : '' }}">
                <a href="{{ route('easy_mode.index') }}" data-tooltip="Senior & nontechnical staff operational mode with 18px+ typography and 1-touch arrivals/departures/cleanliness workflows.">
                    <i class="fa-solid fa-person-shelter" style="color: #FBBF24;"></i>
                    <span>かんたん操作 (Easy Mode)</span>
                    <span class="badge" style="background: #E8A64A; color: #183B50; font-size: 10px; font-weight: 700; margin-left: auto; padding: 2px 6px;">Staff</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('learning_centre.*') ? 'active' : '' }}">
                <a href="{{ route('learning_centre.index') }}" data-tooltip="Interactive video learning library containing 8 recorded walkthroughs of core hotel operations and QA features.">
                    <i class="fa-solid fa-graduation-cap" style="color: #38BDF8;"></i>
                    <span>学習センター (Learning)</span>
                    <span class="badge" style="background: #0284C7; color: #fff; font-size: 10px; margin-left: auto; padding: 2px 6px;">8 Videos</span>
                </a>
            </li>

            <li class="menu-header">Core Operations</li>
            <li class="menu-item {{ request()->routeIs('overview.*') ? 'active' : '' }}">
                <a href="{{ route('overview.index') }}">
                    <i class="fa-solid fa-compass" style="color: #38BDF8;"></i>
                    <span>統合画面 (Overview)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>{{ __('clearstay.nav.dashboard') }}</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('board.index') ? 'active' : '' }}">
                <a href="{{ route('board.index') }}">
                    <i class="fa-solid fa-border-all"></i>
                    <span>空室ボード (Room Board)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                <a href="{{ route('reservations.index') }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>予約管理 (Reservations)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('housekeeping.*') ? 'active' : '' }}">
                <a href="{{ route('housekeeping.index') }}">
                    <i class="fa-solid fa-broom"></i>
                    <span>清掃管理 (Housekeeping)</span>
                </a>
            </li>

            <li class="menu-header">Distribution & Revenue</li>
            <li class="menu-item {{ request()->routeIs('channels.*') ? 'active' : '' }}">
                <a href="{{ route('channels.index') }}">
                    <i class="fa-solid fa-globe" style="color: #34D399;"></i>
                    <span>チャネル連携 (Channels)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('bulk_editor.*') ? 'active' : '' }}">
                <a href="{{ route('bulk_editor.index') }}">
                    <i class="fa-solid fa-layer-group" style="color: #FBBF24;"></i>
                    <span>一括更新 (Bulk Studio)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('rates.*') ? 'active' : '' }}">
                <a href="{{ route('rates.index') }}">
                    <i class="fa-solid fa-tags" style="color: #F472B6;"></i>
                    <span>料金管理 (Rate Studio)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('inventory_reconciliation.*') ? 'active' : '' }}">
                <a href="{{ route('inventory_reconciliation.index') }}">
                    <i class="fa-solid fa-arrows-split-up-and-left" style="color: #2DD4BF;"></i>
                    <span>在庫照合 (Inventory)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('payment_reconciliation.*') ? 'active' : '' }}">
                <a href="{{ route('payment_reconciliation.index') }}">
                    <i class="fa-solid fa-building-columns" style="color: #38BDF8;"></i>
                    <span>決済照合 (Payment)</span>
                </a>
            </li>

            <li class="menu-header">Partner Assurance</li>
            <li class="menu-item {{ request()->routeIs('partner_assurance.*') ? 'active' : '' }}">
                <a href="{{ route('partner_assurance.index') }}">
                    <i class="fa-solid fa-handshake" style="color: #60A5FA;"></i>
                    <span>パートナー品質 (Assurance)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('event_trace.*') ? 'active' : '' }}">
                <a href="{{ route('event_trace.index') }}">
                    <i class="fa-solid fa-wave-square" style="color: #A78BFA;"></i>
                    <span>イベント追跡 (EventTrace)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('readiness.*') ? 'active' : '' }}">
                <a href="{{ route('readiness.index') }}">
                    <i class="fa-solid fa-certificate" style="color: #F59E0B;"></i>
                    <span>接続準備 (Readiness)</span>
                </a>
            </li>

            <li class="menu-header">Governance & Security</li>
            <li class="menu-item {{ request()->routeIs('access_governance.index') ? 'active' : '' }}">
                <a href="{{ route('access_governance.index') }}" data-tooltip="Manager-controlled permission elevation, two-person approval, and temporary access control.">
                    <i class="fa-solid fa-user-shield" style="color: #2DD4BF;"></i>
                    <span>アクセス統制 (Access Control)</span>
                    <span class="badge" style="background: #287F79; color: #fff; font-size: 10px; margin-left: auto; padding: 2px 6px;">PoLP</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('access_governance.demo') ? 'active' : '' }}">
                <a href="{{ route('access_governance.demo') }}" data-tooltip="Live 5-scenario security demonstration with role simulation and blocked privilege escalation.">
                    <i class="fa-solid fa-play-circle" style="color: #38BDF8;"></i>
                    <span>5シナリオ検証 (Live Demo)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('privacy_centre.*') ? 'active' : '' }}">
                <a href="{{ route('privacy_centre.index') }}" data-tooltip="Privacy-by-Design centre enforcing DPDP Act 2023, default PII masking, and audited unmasking.">
                    <i class="fa-solid fa-user-lock" style="color: #A78BFA;"></i>
                    <span>プライバシー統制 (Privacy)</span>
                    <span class="badge" style="background: #6366F1; color: #fff; font-size: 10px; margin-left: auto; padding: 2px 6px;">DPDP</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('accessibility.*') ? 'active' : '' }}">
                <a href="{{ route('accessibility.index') }}" data-tooltip="WCAG 2.2 AA Universal Accessibility engine with contrast profiles and 100%-300% reflow scaling.">
                    <i class="fa-solid fa-universal-access" style="color: #F472B6;"></i>
                    <span>アクセシビリティ (a11y)</span>
                    <span class="badge" style="background: #DB2777; color: #fff; font-size: 10px; margin-left: auto; padding: 2px 6px;">WCAG AA</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                <a href="{{ route('staff.index') }}">
                    <i class="fa-solid fa-users-gear" style="color: #38BDF8;"></i>
                    <span>全従業員一覧 (Staff Roster)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('device_trust.*') ? 'active' : '' }}">
                <a href="{{ route('device_trust.index') }}">
                    <i class="fa-solid fa-laptop-file" style="color: #34D399;"></i>
                    <span>端末信頼性 (Device Trust)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('security_governance.*') ? 'active' : '' }}">
                <a href="{{ route('security_governance.index') }}">
                    <i class="fa-solid fa-brain" style="color: #EC4899;"></i>
                    <span>AIガバナンス (Security AI)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('security.*') ? 'active' : '' }}">
                <a href="{{ route('security.index') }}">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>セキュリティ脅威 (Threats)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('health.*') ? 'active' : '' }}">
                <a href="{{ route('health.index') }}">
                    <i class="fa-solid fa-heart-pulse" style="color: #10B981;"></i>
                    <span>稼働状態 (System Health)</span>
                </a>
            </li>

            <li class="menu-header">Executive & Analytics</li>
            <li class="menu-item {{ request()->routeIs('booking_analytics.*') ? 'active' : '' }}">
                <a href="{{ route('booking_analytics.index') }}">
                    <i class="fa-solid fa-chart-line" style="color: #60A5FA;"></i>
                    <span>予約分析 (BA+)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <a href="{{ route('reports.index') }}">
                    <i class="fa-solid fa-file-lines" style="color: #FBBF24;"></i>
                    <span>帳票出力 (Reports)</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                <a href="{{ route('analytics.index') }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>{{ __('clearstay.nav.analytics') }}</span>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('csv.*') ? 'active' : '' }}">
                <a href="{{ route('csv.index') }}">
                    <i class="fa-solid fa-file-csv"></i>
                    <span>{{ __('clearstay.nav.csv_import') }}</span>
                </a>
            </li>

            <li class="menu-header">Public Website</li>
            <li class="menu-item">
                <a href="{{ route('public.home') }}" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>{{ __('clearstay.nav.home') }}</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-pill">
                <div class="user-avatar" style="background: #183B50; color: #E8A64A; border: 1px solid #287F79;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role" style="color: #2DD4BF;">
                        {{ auth()->user()->role }}
                        @if(auth()->user()->department)
                            <span style="opacity: 0.7; font-size: 10px;">· {{ auth()->user()->department }}</span>
                        @endif
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary" title="{{ __('clearstay.nav.logout') }}" style="padding: 6px 8px;">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        <header class="top-header">
            <div style="display: flex; align-items: center; gap: 14px;">
                <!-- Mobile Sidebar Hamburger Toggle -->
                <button type="button" class="btn btn-sm btn-secondary mobile-menu-toggle" id="mobileMenuBtn" onclick="toggleMobileSidebar()" title="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="header-title">
                    <h2>@yield('header_title', __('clearstay.app_name'))</h2>
                    <p>@yield('header_subtitle', __('clearstay.tagline'))</p>
                </div>
            </div>

            <div class="header-controls">
                <!-- Global Command Palette Search (Ctrl+K) -->
                <div class="search-trigger" onclick="openGlobalSearch()" style="cursor: pointer; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 6px 14px; display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-muted); min-width: 200px;" title="Press Ctrl+K to search">
                    <i class="fa-solid fa-magnifying-glass text-primary"></i>
                    <span style="flex: 1;">Search everything...</span>
                    <kbd style="background: #e2e8f0; color: #475569; font-size: 11px; padding: 2px 6px; border-radius: 4px; font-family: monospace;">Ctrl K</kbd>
                </div>

                <!-- Notification Bell & Drawer -->
                <div class="notification-wrapper" style="position: relative;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleNotificationDrawer()" id="notifBellBtn" style="padding: 7px 11px; position: relative;" title="System Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notif-badge" id="notifBadge" style="position: absolute; top: -4px; right: -4px; background: #EF4444; color: #fff; font-size: 9px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">3</span>
                    </button>
                    
                    <!-- Notification Dropdown Menu -->
                    <div class="notification-dropdown" id="notifDropdown" style="display: none; position: absolute; right: 0; top: 40px; width: 340px; background: #ffffff; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 1000; overflow: hidden; animation: modalPop 0.2s ease-out;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                            <strong style="font-size: 13px; color: var(--text-main);"><i class="fa-solid fa-bell text-primary"></i> System Notifications</strong>
                            <button type="button" onclick="clearNotifications()" style="border: none; background: transparent; color: #287F79; font-size: 11px; font-weight: 600; cursor: pointer;">Mark all read</button>
                        </div>
                        <div id="notifList" style="max-height: 280px; overflow-y: auto;">
                            <a href="{{ route('channels.index') }}" style="display: block; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; transition: background 0.15s;">
                                <div style="font-size: 11px; color: #10B981; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> Channel Sync Completed</div>
                                <div style="font-size: 12px; color: var(--text-main);">Booking.com & Agoda outbox events synced successfully.</div>
                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">2 minutes ago</div>
                            </a>
                            <a href="{{ route('security_governance.index') }}" style="display: block; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; transition: background 0.15s;">
                                <div style="font-size: 11px; color: #6366F1; font-weight: 700;"><i class="fa-solid fa-brain"></i> AI Security Advisory</div>
                                <div style="font-size: 12px; color: var(--text-main);">IEEE sweep generated advisory on TLS 1.3 enforcement.</div>
                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">15 minutes ago</div>
                            </a>
                            <a href="{{ route('experience_lab.index') }}" style="display: block; padding: 10px 14px; text-decoration: none; color: inherit; transition: background 0.15s;">
                                <div style="font-size: 11px; color: #2DD4BF; font-weight: 700;"><i class="fa-solid fa-microscope"></i> Experience Lab Ready</div>
                                <div style="font-size: 12px; color: var(--text-main);">ClearStay 3.0 Responsive Verification Engine online.</div>
                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">Just now</div>
                            </a>
                        </div>
                        <div style="padding: 8px; text-align: center; background: #f8fafc; border-top: 1px solid var(--border);">
                            <a href="{{ route('event_trace.index') }}" style="font-size: 11px; color: #287F79; font-weight: 600; text-decoration: none;">View All System Traces &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Easy Mode Quick Switch -->
                <a href="{{ route('easy_mode.index') }}" class="btn btn-sm" style="background: #183B50; color: #E8A64A; border: 1px solid #287F79; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" data-tooltip="Switch to Senior & Nontechnical Easy Mode: larger typography, 52px+ buttons, and 1-touch arrivals/departures.">
                    <i class="fa-solid fa-person-shelter"></i>
                    <span class="hide-on-xs">かんたん操作</span>
                </a>

                <!-- Learning Centre Videos (Hidden on mobile header; in sidebar drawer) -->
                <a href="{{ route('learning_centre.index') }}" class="btn btn-sm btn-secondary hide-on-mobile" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" data-tooltip="Watch 8 recorded video walkthroughs demonstrating full reservation, bulk update, and QA workflows.">
                    <i class="fa-solid fa-film" style="color: #287F79;"></i>
                    <span>動画ガイド</span>
                </a>

                <!-- Onboarding Setup Guide Trigger (Hidden on mobile header; in sidebar drawer) -->
                <button type="button" class="btn btn-sm btn-secondary hide-on-mobile" onclick="openOnboardingModal()" id="btnOnboardingTrigger" style="display: inline-flex; align-items: center; gap: 6px;" data-tooltip="Track the 7-step enterprise setup checklist reflecting live database state.">
                    <i class="fa-solid fa-rocket" style="color: #E8A64A;"></i>
                    <span>ガイド</span>
                    <span class="badge" id="onboardingHeaderBadge" style="background: #287F79; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 10px;">...</span>
                </button>

                <!-- Multi-Tenant Hotel Switcher (Hidden on mobile header; in sidebar drawer) -->
                <div class="hotel-selector hotel-selector-header">
                    <i class="fa-solid fa-building text-primary"></i>
                    <form action="{{ route('hotel.switch') }}" method="POST" id="hotelSwitchForm">
                        @csrf
                        <select name="hotel_id" onchange="document.getElementById('hotelSwitchForm').submit();">
                            @php
                                $currentHotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
                                $accessibleHotels = (auth()->user()->role === 'superadmin' || auth()->user()->role === 'owner')
                                    ? \App\Models\Hotel::where('is_active', true)->get() 
                                    : \App\Models\Hotel::where('id', auth()->user()->hotel_id)->get();
                            @endphp
                            @foreach($accessibleHotels as $h)
                                <option value="{{ $h->id }}" {{ $currentHotelId == $h->id ? 'selected' : '' }}>
                                    {{ $h->name }} ({{ $h->city }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <!-- Language Switcher (Hidden on extra small devices; mirrored in sidebar drawer) -->
                <div class="lang-switch hide-on-xs">
                    <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    <a href="{{ route('locale.switch', 'ja') }}" class="{{ app()->getLocale() === 'ja' ? 'active' : '' }}">日本語</a>
                </div>
            </div>
        </header>

        <main class="page-content" id="mainContent" tabindex="-1">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Please check the errors below:</strong>
                        <ul style="margin-left: 20px; margin-top: 4px;">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Global Pop-up Preview & Date Change Modal (jQuery Driven) -->
    <div id="reservationModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div>
                    <h3 id="modalCode" style="font-size: 18px; font-weight: 700; color: var(--primary);">CS-PREVIEW</h3>
                    <p id="modalSubtitle" style="font-size: 12px; color: var(--text-muted);">Guest Details & Concurrency Date Management</p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeReservationModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="modal-body" id="modalBody">
                <div id="modalLoading" style="text-align: center; padding: 40px;">
                    <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                    <p style="margin-top: 10px; font-size: 13px; color: var(--text-muted);">Loading reservation details...</p>
                </div>

                <div id="modalContent" style="display: none;">
                    <!-- Guest & Room Summary Card -->
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <div>
                                <h4 id="modalGuestName" style="font-size: 16px; font-weight: 700;">-</h4>
                                <p style="font-size: 12px; color: var(--text-muted);"><span id="modalGuestEmail">-</span> | <span id="modalGuestPhone">-</span></p>
                            </div>
                            <div style="text-align: right;">
                                <span id="modalStatusBadge" class="badge badge-confirmed">Confirmed</span>
                                <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-top: 4px;">¥<span id="modalTotalPrice">0</span></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px; border-top: 1px solid var(--border); padding-top: 12px; margin-top: 8px; font-size: 13px;">
                            <div><strong>Room:</strong> <span id="modalRoomNumber">-</span> (<span id="modalRoomType">-</span>)</div>
                            <div><strong>Nights:</strong> <span id="modalNights">0</span></div>
                            <div><strong>Guests:</strong> <span id="modalAdults">1</span> Adults, <span id="modalChildren">0</span> Children</div>
                        </div>
                    </div>

                    <!-- Token Guest Portal Quick Link -->
                    <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px 14px; margin-bottom: 20px; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fa-solid fa-link text-primary"></i> <strong>Guest Self-Service Link:</strong></span>
                        <a id="modalGuestPortalLink" href="#" target="_blank" class="btn btn-sm btn-primary" style="padding: 4px 10px;">
                            Open Guest Portal <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>

                    <!-- Concurrency-Safe Date Change Form (Row Locking SELECT ... FOR UPDATE) -->
                    <div style="border: 1px solid #fed7aa; background: #fff7ed; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                        <h5 style="font-size: 14px; font-weight: 700; color: #9a3412; margin-bottom: 4px;">
                            <i class="fa-solid fa-lock"></i> {{ __('clearstay.reservations.change_dates') }}
                        </h5>
                        <p style="font-size: 11px; color: #c2410c; margin-bottom: 14px;">
                            {{ __('clearstay.reservations.concurrency_notice') }}
                        </p>

                        <form id="changeDatesForm">
                            <input type="hidden" id="changeDatesReservationId" name="reservation_id">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">{{ __('clearstay.reservations.check_in') }}</label>
                                    <input type="date" id="modalCheckInDate" name="check_in_date" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">{{ __('clearstay.reservations.check_out') }}</label>
                                    <input type="date" id="modalCheckOutDate" name="check_out_date" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                                </div>
                            </div>

                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Room Reassignment (Optional)</label>
                                <select id="modalRoomSelect" name="room_id" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                                    <!-- Populated via AJAX -->
                                </select>
                            </div>

                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Modification Reason</label>
                                <input type="text" id="modalChangeReason" name="reason" placeholder="e.g. Guest requested 2-day extension" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                            </div>

                            <div id="changeDatesAlert" style="display: none; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 12px;"></div>

                            <button type="submit" id="btnSubmitChangeDates" class="btn btn-primary btn-sm" style="width: 100%;">
                                <i class="fa-solid fa-shield-halved"></i> Apply Transactional Date Update
                            </button>
                        </form>
                    </div>

                    <!-- Change History Audit Log Accordion -->
                    <div id="changeHistorySection" style="margin-top: 14px;">
                        <h6 style="font-size: 13px; font-weight: 700; margin-bottom: 8px; color: var(--text-muted);">
                            <i class="fa-solid fa-clock-rotate-left"></i> Date Change Audit Trail
                        </h6>
                        <div id="changeHistoryList" style="font-size: 12px; max-height: 120px; overflow-y: auto;">
                            <!-- Populated via AJAX -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReservationModal()">{{ __('clearstay.general.close') }}</button>
            </div>
        </div>
    </div>

    <!-- 7-Step Enterprise Onboarding Checklist Modal -->
    <div id="onboardingModal" class="modal-overlay">
        <div class="modal-container" style="max-width: 680px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0F1D2F, #183B50); color: #ffffff;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #E8A64A;">
                        <i class="fa-solid fa-rocket"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 17px; font-weight: 700; color: #ffffff; margin: 0;">Enterprise Setup Checklist (導入設定ガイド)</h4>
                        <div style="font-size: 12px; color: #94A3B8; margin-top: 2px;">Live progress evaluated from database records</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeOnboardingModal()" style="color: #ffffff; background: rgba(255,255,255,0.1); border: none;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body" style="padding: 24px;">
                <!-- Live Progress Bar -->
                <div style="margin-bottom: 20px; background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-main);">Setup Completion</span>
                        <span id="onboardingProgressLabel" style="font-size: 13px; font-weight: 800; color: #287F79;">0 / 7 Steps (0%)</span>
                    </div>
                    <div style="background: #e2e8f0; border-radius: 9999px; height: 10px; overflow: hidden;">
                        <div id="onboardingProgressBar" style="background: linear-gradient(90deg, #287F79, #2DD4BF); height: 100%; width: 0%; transition: width 0.4s ease;"></div>
                    </div>
                </div>

                <!-- 7 Checklist Steps List -->
                <div id="onboardingStepsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Dynamically populated via /api/onboarding/status -->
                    <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                        <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: #287F79;"></i>
                        <p style="margin-top: 10px; font-size: 13px;">Loading system setup status...</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="resetOnboarding()">
                    <i class="fa-solid fa-rotate-left"></i> Reset Progress
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="closeOnboardingModal()">Close Checklist</button>
            </div>
        </div>
    </div>

    <!-- Contextual Guided Next-Step Floating Card -->
    <div id="nextStepPopup" class="next-step-floater" role="status" aria-live="polite">
        <div class="next-step-header">
            <span class="next-step-tag"><i class="fa-solid fa-lightbulb"></i> Recommended Next Step</span>
            <button type="button" onclick="dismissNextStepPopup()" style="background: transparent; border: none; color: #94A3B8; cursor: pointer; font-size: 13px;" title="Dismiss">&times;</button>
        </div>
        <div class="next-step-title" id="nextStepTitle">Configure Your Property</div>
        <div class="next-step-desc" id="nextStepDesc">Complete this recommended operational step to optimize your hotel distribution.</div>
        <div class="next-step-actions">
            <a href="#" id="nextStepActionBtn" class="btn btn-sm btn-primary" style="font-size: 12px; padding: 6px 14px; text-decoration: none;">
                Proceed &rarr;
            </a>
            <button type="button" onclick="openOnboardingModal()" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 6px 12px;">
                View All Steps (7)
            </button>
        </div>
    </div>

    <!-- Universal Interactive Tooltip Bubble Container -->
    <div id="clearstayTooltipBubble" class="clearstay-tooltip-bubble" role="tooltip" aria-hidden="true">
        <div class="tooltip-title">
            <i class="fa-solid fa-circle-info"></i>
            <span id="tooltipHeader">Information</span>
            <button type="button" class="tooltip-close" onclick="hideClearstayTooltip()">&times;</button>
        </div>
        <div id="tooltipBody" class="tooltip-body"></div>
    </div>

    <!-- Global Command Palette Modal (Ctrl+K) -->
    <div id="globalSearchModal" class="search-modal-backdrop">
        <div class="search-modal-dialog">
            <div class="search-input-box">
                <i class="fa-solid fa-magnifying-glass text-primary" style="font-size: 18px;"></i>
                <input type="text" id="globalSearchInput" placeholder="Type a room, reservation code, partner, or command..." autocomplete="off">
                <span style="font-size: 11px; color: var(--text-muted); background: #f1f5f9; padding: 2px 8px; border-radius: 4px; border: 1px solid #cbd5e1;">ESC to exit</span>
            </div>
            
            <div class="search-results-list" id="searchResultsContainer">
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-keyboard fa-2x" style="opacity: 0.4; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px;">Search across ClearStay 3.0 operations, live reservations, and partners...</p>
                </div>
            </div>
            
            <div style="padding: 10px 16px; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: var(--text-muted);">
                <span>Navigation: <kbd>&uarr;</kbd> <kbd>&darr;</kbd> to navigate, <kbd>&crarr;</kbd> to select</span>
                <span style="color: #287F79; font-weight: 600;">ClearStay 3.0 Spotlight Engine</span>
            </div>
        </div>
    </div>

    <!-- jQuery & Chart.js -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Setup CSRF token for jQuery AJAX calls
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        // Register PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch((err) => {
                    console.log('SW registration note:', err);
                });
            });
        }

        // Mobile Sidebar Toggle
        function toggleMobileSidebar() {
            $('#appSidebar').toggleClass('mobile-open');
        }

        // Notification Drawer Toggle
        function toggleNotificationDrawer() {
            $('#notifDropdown').toggle();
        }

        function clearNotifications() {
            $('#notifBadge').fadeOut(200);
            $('#notifList').html('<div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 12px;"><i class="fa-solid fa-check-double text-success"></i> All notifications caught up!</div>');
        }

        // Close dropdowns on click outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.notification-wrapper').length) {
                $('#notifDropdown').hide();
            }
        });

        // Global Command Palette (Ctrl+K)
        let searchDebounceTimer = null;

        function openGlobalSearch() {
            $('#globalSearchModal').addClass('active');
            $('#globalSearchInput').val('').focus();
            loadSearchQuery('');
        }

        function closeGlobalSearch() {
            $('#globalSearchModal').removeClass('active');
        }

        // Keyboard Shortcut: Ctrl+K / Cmd+K and ESC
        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if ($('#globalSearchModal').hasClass('active')) {
                    closeGlobalSearch();
                } else {
                    openGlobalSearch();
                }
            } else if (e.key === 'Escape') {
                closeGlobalSearch();
                closeReservationModal();
            }
        });

        $('#globalSearchModal').on('click', function (e) {
            if ($(e.target).is('#globalSearchModal')) {
                closeGlobalSearch();
            }
        });

        $('#globalSearchInput').on('input', function () {
            const query = $(this).val();
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                loadSearchQuery(query);
            }, 180);
        });

        function loadSearchQuery(q) {
            $.ajax({
                url: '{{ route("api.search") }}',
                method: 'GET',
                data: { q: q },
                success: function (res) {
                    if (res.success) {
                        renderSearchResults(res.results, q);
                    }
                }
            });
        }

        function renderSearchResults(data, query) {
            const container = $('#searchResultsContainer');
            container.empty();

            if (data.quick_actions) {
                container.append('<div class="search-category-header"><i class="fa-solid fa-bolt text-primary"></i> Quick Navigation & Tools</div>');
                data.quick_actions.forEach(function (qa) {
                    container.append(`
                        <a href="${qa.url}" class="search-result-item">
                            <div class="search-result-icon"><i class="fa-solid ${qa.icon}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600;">${qa.title}</div>
                            </div>
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 10px;">${qa.badge}</span>
                        </a>
                    `);
                });
                return;
            }

            let hasAnyResult = false;

            // Pages
            if (data.pages && data.pages.length > 0) {
                hasAnyResult = true;
                container.append('<div class="search-category-header"><i class="fa-solid fa-compass text-primary"></i> System Pages</div>');
                data.pages.forEach(function (p) {
                    container.append(`
                        <a href="${p.url}" class="search-result-item">
                            <div class="search-result-icon"><i class="fa-solid ${p.icon}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600;">${p.title}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">${p.category}</div>
                            </div>
                            <i class="fa-solid fa-chevron-right" style="font-size: 11px; color: #cbd5e1;"></i>
                        </a>
                    `);
                });
            }

            // Reservations
            if (data.reservations && data.reservations.length > 0) {
                hasAnyResult = true;
                container.append('<div class="search-category-header"><i class="fa-solid fa-calendar-check text-primary"></i> Reservations</div>');
                data.reservations.forEach(function (r) {
                    container.append(`
                        <div class="search-result-item" onclick="${r.action}; closeGlobalSearch();">
                            <div class="search-result-icon" style="background: #ecfdf5; color: #059669;"><i class="fa-solid ${r.icon}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: var(--primary);">${r.title}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">${r.subtitle}</div>
                            </div>
                            <span class="badge badge-info" style="font-size: 10px;">Preview</span>
                        </div>
                    `);
                });
            }

            // Rooms
            if (data.rooms && data.rooms.length > 0) {
                hasAnyResult = true;
                container.append('<div class="search-category-header"><i class="fa-solid fa-door-open text-primary"></i> Rooms</div>');
                data.rooms.forEach(function (rm) {
                    container.append(`
                        <a href="${rm.url}" class="search-result-item">
                            <div class="search-result-icon" style="background: #fef3c7; color: #d97706;"><i class="fa-solid ${rm.icon}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600;">${rm.title}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">${rm.subtitle}</div>
                            </div>
                        </a>
                    `);
                });
            }

            // Channels
            if (data.channels && data.channels.length > 0) {
                hasAnyResult = true;
                container.append('<div class="search-category-header"><i class="fa-solid fa-globe text-primary"></i> Channels & OTAs</div>');
                data.channels.forEach(function (ch) {
                    container.append(`
                        <a href="${ch.url}" class="search-result-item">
                            <div class="search-result-icon" style="background: #f0fdf4; color: #16a34a;"><i class="fa-solid ${ch.icon}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600;">${ch.title}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">${ch.subtitle}</div>
                            </div>
                        </a>
                    `);
                });
            }

            // Partners
            if (data.partners && data.partners.length > 0) {
                hasAnyResult = true;
                container.append('<div class="search-category-header"><i class="fa-solid fa-handshake text-primary"></i> Integration Partners</div>');
                data.partners.forEach(function (pt) {
                    container.append(`
                        <a href="${pt.url}" class="search-result-item">
                            <div class="search-result-icon" style="background: #eff6ff; color: #2563eb;"><i class="fa-solid ${pt.icon}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600;">${pt.title}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">${pt.subtitle}</div>
                            </div>
                        </a>
                    `);
                });
            }

            if (!hasAnyResult) {
                container.html(`
                    <div style="padding: 32px; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-magnifying-glass fa-2x" style="opacity: 0.3; margin-bottom: 8px;"></i>
                        <p style="font-size: 13px;">No results found for "${query}"</p>
                    </div>
                `);
            }
        }

        // Global Pop-up Preview Modal Handler
        function openReservationModal(reservationId) {
            $('#reservationModal').addClass('active');
            $('#modalLoading').show();
            $('#modalContent').hide();
            $('#changeDatesAlert').hide();

            $.ajax({
                url: '/reservations/' + reservationId,
                method: 'GET',
                success: function (res) {
                    if (res.success) {
                        const r = res.reservation;
                        $('#modalCode').text(r.confirmation_code);
                        $('#modalGuestName').text(r.guest_name);
                        $('#modalGuestEmail').text(r.guest_email);
                        $('#modalGuestPhone').text(r.guest_phone);
                        $('#modalTotalPrice').text(r.total_price);
                        $('#modalRoomNumber').text(r.room_number);
                        $('#modalRoomType').text(r.room_type);
                        $('#modalNights').text(r.nights);
                        $('#modalAdults').text(r.adults);
                        $('#modalChildren').text(r.children);
                        $('#modalGuestPortalLink').attr('href', r.guest_portal_url);

                        $('#modalStatusBadge')
                            .attr('class', 'badge badge-' + r.status)
                            .text(r.status.toUpperCase());

                        // Form values
                        $('#changeDatesReservationId').val(r.id);
                        $('#modalCheckInDate').val(r.check_in_date);
                        $('#modalCheckOutDate').val(r.check_out_date);

                        // Populate room select
                        const roomSelect = $('#modalRoomSelect');
                        roomSelect.empty();
                        res.available_rooms.forEach(function (room) {
                            const isCurrent = room.id === r.room_id;
                            roomSelect.append(
                                `<option value="${room.id}" ${isCurrent ? 'selected' : ''}>Room ${room.number} - ${room.type} (¥${Number(room.price).toLocaleString()}/night)</option>`
                            );
                        });

                        // Populate change history
                        const historyList = $('#changeHistoryList');
                        historyList.empty();
                        if (r.changes && r.changes.length > 0) {
                            r.changes.forEach(function (c) {
                                historyList.append(
                                    `<div style="padding: 6px 0; border-bottom: 1px solid var(--border);">
                                        <strong>${c.date}</strong> by <em>${c.user}</em>: 
                                        <span style="color: #991b1b;">${c.old_dates}</span> &rarr; 
                                        <span style="color: #065f46; font-weight: 600;">${c.new_dates}</span>
                                        ${c.reason ? `<div style="color: var(--text-muted); font-size: 11px;">Reason: ${c.reason}</div>` : ''}
                                    </div>`
                                );
                            });
                        } else {
                            historyList.append('<div style="color: var(--text-muted);">No date changes recorded yet.</div>');
                        }

                        $('#modalLoading').hide();
                        $('#modalContent').fadeIn(200);
                    }
                },
                error: function (xhr) {
                    alert('Error loading reservation preview.');
                    closeReservationModal();
                }
            });
        }

        function closeReservationModal() {
            $('#reservationModal').removeClass('active');
        }

        // Close on click outside
        $('#reservationModal').on('click', function(e) {
            if ($(e.target).is('#reservationModal')) {
                closeReservationModal();
            }
        });

        // Submit Concurrency-Safe Date Change Form
        $('#changeDatesForm').on('submit', function (e) {
            e.preventDefault();
            const resId = $('#changeDatesReservationId').val();
            const submitBtn = $('#btnSubmitChangeDates');
            const alertBox = $('#changeDatesAlert');

            submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Locking rows & updating...');
            alertBox.hide();

            $.ajax({
                url: '/reservations/' + resId + '/change-dates',
                method: 'POST',
                data: {
                    check_in_date: $('#modalCheckInDate').val(),
                    check_out_date: $('#modalCheckOutDate').val(),
                    room_id: $('#modalRoomSelect').val(),
                    reason: $('#modalChangeReason').val()
                },
                success: function (res) {
                    submitBtn.prop('disabled', false).html('<i class="fa-solid fa-shield-halved"></i> Apply Transactional Date Update');
                    if (res.success) {
                        alertBox.attr('class', 'alert alert-success')
                            .html('<i class="fa-solid fa-circle-check"></i> ' + res.message)
                            .fadeIn();
                        
                        // Reload modal data
                        setTimeout(() => openReservationModal(resId), 1200);

                        // If board or reservation table exists, reload
                        if (typeof reloadBoard === 'function') {
                            reloadBoard();
                        }
                    }
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fa-solid fa-shield-halved"></i> Apply Transactional Date Update');
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error updating dates.';
                    alertBox.attr('class', 'alert alert-danger')
                        .html('<i class="fa-solid fa-triangle-exclamation"></i> ' + msg)
                        .fadeIn();
                }
            });
        });

        /* ==========================================================================
           Interactive Universal Tooltip Engine (Hover, Keyboard Focus & Touch)
           ========================================================================== */
        const tooltipBubble = document.getElementById('clearstayTooltipBubble');
        const tooltipHeader = document.getElementById('tooltipHeader');
        const tooltipBody = document.getElementById('tooltipBody');
        let currentTooltipTarget = null;

        function showClearstayTooltip(target, text, headerText = 'Explanation') {
            if (!tooltipBubble || !text) return;
            currentTooltipTarget = target;
            tooltipHeader.textContent = headerText;
            tooltipBody.textContent = text;
            tooltipBubble.classList.add('active');
            tooltipBubble.setAttribute('aria-hidden', 'false');

            const rect = target.getBoundingClientRect();
            const bubbleRect = tooltipBubble.getBoundingClientRect();
            const padding = 10;

            let top = rect.top - bubbleRect.height - 8;
            if (top < 50) {
                // Flip below if not enough room above
                top = rect.bottom + 8;
            }

            let left = rect.left + (rect.width / 2) - (bubbleRect.width / 2);
            if (left < padding) left = padding;
            if (left + bubbleRect.width > window.innerWidth - padding) {
                left = window.innerWidth - padding - bubbleRect.width;
            }

            tooltipBubble.style.top = Math.round(top) + 'px';
            tooltipBubble.style.left = Math.round(left) + 'px';
        }

        function hideClearstayTooltip() {
            if (!tooltipBubble) return;
            tooltipBubble.classList.remove('active', 'touch-active');
            tooltipBubble.setAttribute('aria-hidden', 'true');
            currentTooltipTarget = null;
        }

        // Global Event Delegation for Tooltips
        document.addEventListener('mouseover', function(e) {
            const el = e.target.closest('[data-tooltip]');
            if (el && !tooltipBubble.classList.contains('touch-active')) {
                const text = el.getAttribute('data-tooltip');
                const title = el.getAttribute('data-tooltip-title') || 'Guidance';
                showClearstayTooltip(el, text, title);
            }
        });

        document.addEventListener('mouseout', function(e) {
            const el = e.target.closest('[data-tooltip]');
            if (el && !tooltipBubble.classList.contains('touch-active')) {
                hideClearstayTooltip();
            }
        });

        document.addEventListener('focusin', function(e) {
            const el = e.target.closest('[data-tooltip]');
            if (el) {
                const text = el.getAttribute('data-tooltip');
                const title = el.getAttribute('data-tooltip-title') || 'Guidance';
                showClearstayTooltip(el, text, title);
            }
        });

        document.addEventListener('focusout', function(e) {
            const el = e.target.closest('[data-tooltip]');
            if (el && !tooltipBubble.classList.contains('touch-active')) {
                hideClearstayTooltip();
            }
        });

        // Touch & Tap Support for Mobile / Tablet Devices
        document.addEventListener('click', function(e) {
            const el = e.target.closest('[data-tooltip], .help-cue');
            if (el) {
                const text = el.getAttribute('data-tooltip');
                const title = el.getAttribute('data-tooltip-title') || 'Guidance';
                if (tooltipBubble.classList.contains('active') && currentTooltipTarget === el) {
                    hideClearstayTooltip();
                } else {
                    showClearstayTooltip(el, text, title);
                    tooltipBubble.classList.add('touch-active');
                }
            } else if (!e.target.closest('#clearstayTooltipBubble')) {
                hideClearstayTooltip();
            }
        });

        /* ==========================================================================
           Onboarding Checklist & Guided Next-Step Controller
           ========================================================================== */
        function openOnboardingModal() {
            $('#onboardingModal').addClass('active');
            loadOnboardingStatus();
        }

        function closeOnboardingModal() {
            $('#onboardingModal').removeClass('active');
        }

        $('#onboardingModal').on('click', function(e) {
            if ($(e.target).is('#onboardingModal')) {
                closeOnboardingModal();
            }
        });

        function loadOnboardingStatus() {
            $.ajax({
                url: '/api/onboarding/status',
                method: 'GET',
                success: function(res) {
                    if (!res.success) return;

                    // Update Header Badge
                    $('#onboardingHeaderBadge').text(res.completed_count + '/' + res.total_count);

                    // Update Modal Progress
                    $('#onboardingProgressLabel').text(res.completed_count + ' / ' + res.total_count + ' Steps (' + res.progress_percent + '%)');
                    $('#onboardingProgressBar').css('width', res.progress_percent + '%');

                    // Render Steps List
                    const container = $('#onboardingStepsContainer');
                    container.empty();

                    res.steps.forEach(function(s) {
                        const statusBadge = s.is_completed
                            ? `<span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px;"><i class="fa-solid fa-circle-check"></i> Completed</span>`
                            : `<span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px;"><i class="fa-solid fa-clock"></i> Action Needed</span>`;

                        const rowBg = s.is_completed ? '#ffffff' : '#f0fdf4';
                        const borderCol = s.is_completed ? 'var(--border)' : '#86efac';

                        container.append(`
                            <div style="background: ${rowBg}; border: 1px solid ${borderCol}; border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: ${s.is_completed ? '#E2E8F0' : '#287F79'}; color: ${s.is_completed ? '#475569' : '#ffffff'}; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                        <i class="fa-solid ${s.icon}"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 700; color: var(--text-main);">${s.title}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">${s.description}</div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    ${statusBadge}
                                    <a href="${s.url}" class="btn btn-sm btn-secondary" style="font-size: 11px; padding: 4px 10px; text-decoration: none;">
                                        ${s.is_completed ? 'Review' : 'Configure'} &rarr;
                                    </a>
                                </div>
                            </div>
                        `);
                    });

                    // Contextual Next-Step Floater
                    if (res.next_step && !res.is_dismissed && res.completed_count < res.total_count) {
                        $('#nextStepTitle').text(res.next_step.title);
                        $('#nextStepDesc').text(res.next_step.description);
                        $('#nextStepActionBtn').attr('href', res.next_step.url);
                        $('#nextStepPopup').addClass('visible');
                    } else {
                        $('#nextStepPopup').removeClass('visible');
                    }
                }
            });
        }

        function dismissNextStepPopup() {
            $('#nextStepPopup').removeClass('visible');
            $.ajax({
                url: '/api/onboarding/dismiss',
                method: 'POST'
            });
        }

        function resetOnboarding() {
            $.ajax({
                url: '/api/onboarding/reset',
                method: 'POST',
                success: function() {
                    loadOnboardingStatus();
                }
            });
        }

        // Initialize Onboarding check on page load
        $(document).ready(function() {
            loadOnboardingStatus();
        });
    </script>

    @stack('scripts')
</body>
</html>

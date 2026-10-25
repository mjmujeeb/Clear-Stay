<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'ClearStay'); ?> - <?php echo e(__('clearstay.tagline')); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --primary-light: #e0f2fe;
            --secondary: #0f172a;
            --sidebar-bg: #0b1329;
            --sidebar-text: #94a3b8;
            --sidebar-active: #38bdf8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --info: #3b82f6;
            --info-light: #dbeafe;
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
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
        }
    </style>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fa-solid fa-hotel"></i>
            </div>
            <div class="brand-text">
                <h1>ClearStay</h1>
                <span>Operations v1.0</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-header">Front Desk & Board</li>
            <li class="menu-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('dashboard')); ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span><?php echo e(__('clearstay.nav.dashboard')); ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo e(request()->routeIs('board.index') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('board.index')); ?>">
                    <i class="fa-solid fa-border-all"></i>
                    <span><?php echo e(__('clearstay.nav.room_board')); ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo e(request()->routeIs('reservations.*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('reservations.index')); ?>">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span><?php echo e(__('clearstay.nav.reservations')); ?></span>
                </a>
            </li>

            <li class="menu-header">Housekeeping & Operations</li>
            <li class="menu-item <?php echo e(request()->routeIs('housekeeping.*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('housekeeping.index')); ?>">
                    <i class="fa-solid fa-broom"></i>
                    <span><?php echo e(__('clearstay.nav.housekeeping')); ?></span>
                </a>
            </li>

            <?php if(auth()->user()->isManager()): ?>
            <li class="menu-header">Management & Growth</li>
            <li class="menu-item <?php echo e(request()->routeIs('analytics.*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('analytics.index')); ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span><?php echo e(__('clearstay.nav.analytics')); ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo e(request()->routeIs('csv.*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('csv.index')); ?>">
                    <i class="fa-solid fa-file-csv"></i>
                    <span><?php echo e(__('clearstay.nav.csv_import')); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <li class="menu-header">Public Website</li>
            <li class="menu-item">
                <a href="<?php echo e(route('public.home')); ?>" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span><?php echo e(__('clearstay.nav.home')); ?> (Public)</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-pill">
                <div class="user-avatar">
                    <?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?>

                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo e(auth()->user()->name); ?></div>
                    <div class="user-role"><?php echo e(auth()->user()->role); ?></div>
                </div>
                <form action="<?php echo e(route('logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-sm btn-secondary" title="<?php echo e(__('clearstay.nav.logout')); ?>" style="padding: 6px 8px;">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        <header class="top-header">
            <div class="header-title">
                <h2><?php echo $__env->yieldContent('header_title', __('clearstay.app_name')); ?></h2>
                <p><?php echo $__env->yieldContent('header_subtitle', __('clearstay.tagline')); ?></p>
            </div>

            <div class="header-controls">
                <!-- Multi-Tenant Hotel Switcher -->
                <div class="hotel-selector">
                    <i class="fa-solid fa-building text-primary"></i>
                    <form action="<?php echo e(route('hotel.switch')); ?>" method="POST" id="hotelSwitchForm">
                        <?php echo csrf_field(); ?>
                        <select name="hotel_id" onchange="document.getElementById('hotelSwitchForm').submit();">
                            <?php
                                $currentHotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
                                $accessibleHotels = auth()->user()->isSuperAdmin() 
                                    ? \App\Models\Hotel::where('is_active', true)->get() 
                                    : \App\Models\Hotel::where('id', auth()->user()->hotel_id)->get();
                            ?>
                            <?php $__currentLoopData = $accessibleHotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($h->id); ?>" <?php echo e($currentHotelId == $h->id ? 'selected' : ''); ?>>
                                    <?php echo e($h->name); ?> (<?php echo e($h->city); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </form>
                </div>

                <!-- Language Switcher -->
                <div class="lang-switch">
                    <a href="<?php echo e(route('locale.switch', 'en')); ?>" class="<?php echo e(app()->getLocale() === 'en' ? 'active' : ''); ?>">EN</a>
                    <a href="<?php echo e(route('locale.switch', 'ja')); ?>" class="<?php echo e(app()->getLocale() === 'ja' ? 'active' : ''); ?>">日本語</a>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo e(session('success')); ?></span>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo e(session('error')); ?></span>
                </div>
            <?php endif; ?>

            <?php if(session('info')): ?>
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><?php echo e(session('info')); ?></span>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Please check the errors below:</strong>
                        <ul style="margin-left: 20px; margin-top: 4px;">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
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
                            <i class="fa-solid fa-lock"></i> <?php echo e(__('clearstay.reservations.change_dates')); ?>

                        </h5>
                        <p style="font-size: 11px; color: #c2410c; margin-bottom: 14px;">
                            <?php echo e(__('clearstay.reservations.concurrency_notice')); ?>

                        </p>

                        <form id="changeDatesForm">
                            <input type="hidden" id="changeDatesReservationId" name="reservation_id">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;"><?php echo e(__('clearstay.reservations.check_in')); ?></label>
                                    <input type="date" id="modalCheckInDate" name="check_in_date" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;"><?php echo e(__('clearstay.reservations.check_out')); ?></label>
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
                <button type="button" class="btn btn-secondary" onclick="closeReservationModal()"><?php echo e(__('clearstay.general.close')); ?></button>
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
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/layouts/app.blade.php ENDPATH**/ ?>
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClearStay Easy Mode - かんたん操作')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sar: env(safe-area-inset-right, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
            --easy-bg: #F0F4F8;
            --easy-card: #FFFFFF;
            --easy-navy: #0F1D2F;
            --easy-teal: #1F6B66;
            --easy-blue: #1D4ED8;
            --easy-green: #047857;
            --easy-amber: #B45309;
            --easy-red: #B91C1C;
            --easy-border: #CBD5E1;
            --easy-text: #0F172A;
            --easy-muted: #475569;
            --easy-font-size: 19px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Noto Sans JP', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--easy-bg);
            color: var(--easy-text);
            font-size: var(--easy-font-size);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 100vw;
            overflow-x: hidden;
            padding-top: var(--sat);
            padding-right: var(--sar);
            padding-bottom: var(--sab);
            padding-left: var(--sal);
        }

        /* Top Header Bar */
        .easy-header {
            background: #ffffff;
            border-bottom: 3px solid var(--easy-teal);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(15, 29, 47, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .easy-brand {
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--easy-navy);
        }

        .easy-brand-badge {
            background: var(--easy-teal);
            color: #ffffff;
            font-size: 26px;
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(31, 107, 102, 0.3);
        }

        .easy-brand-text h1 {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .easy-brand-text span {
            font-size: 14px;
            color: var(--easy-teal);
            font-weight: 700;
            background: #E6F4F1;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .easy-header-controls {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Large Action Buttons */
        .easy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 52px;
            padding: 12px 24px;
            font-size: 19px;
            font-weight: 700;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .easy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .easy-btn-primary {
            background: var(--easy-teal);
            color: #ffffff;
            border-color: #175450;
        }

        .easy-btn-success {
            background: #059669;
            color: #ffffff;
            border-color: #047857;
        }

        .easy-btn-secondary {
            background: #ffffff;
            color: var(--easy-navy);
            border-color: var(--easy-border);
        }

        .easy-btn-switch {
            background: #FEF3C7;
            color: #92400E;
            border-color: #F59E0B;
            font-size: 16px;
            min-height: 46px;
            padding: 8px 18px;
        }

        /* Container */
        .easy-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 20px;
            flex: 1;
        }

        /* Large Cards */
        .easy-card {
            background: #ffffff;
            border: 2px solid var(--easy-border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 16px rgba(15, 29, 47, 0.05);
            margin-bottom: 24px;
        }

        /* Alerts */
        .easy-alert {
            padding: 18px 24px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .easy-alert-success {
            background: #DCFCE7;
            color: #14532D;
            border: 2px solid #86EFAC;
        }

        .easy-alert-info {
            background: #DBEAFE;
            color: #1E3A8A;
            border: 2px solid #93C5FD;
        }

        .easy-alert-danger {
            background: #FEE2E2;
            color: #7F1D1D;
            border: 2px solid #FCA5A5;
        }

        /* Font Size Zoom Controller */
        .font-size-control {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 4px;
            border: 1px solid var(--easy-border);
        }

        .font-size-btn {
            padding: 6px 12px;
            font-weight: 800;
            font-size: 15px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 6px;
            color: var(--easy-navy);
        }

        .font-size-btn:hover {
            background: #ffffff;
        }

        @media (max-width: 768px) {
            .easy-header {
                flex-direction: column;
                gap: 16px;
                padding: 16px;
                text-align: center;
            }
            .easy-header-controls {
                flex-wrap: wrap;
                justify-content: center;
            }
            .easy-btn {
                width: 100%;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Header -->
    <header class="easy-header">
        <a href="{{ route('easy_mode.index') }}" class="easy-brand">
            <div class="easy-brand-badge">
                <i class="fa-solid fa-hands-holding-circle"></i>
            </div>
            <div class="easy-brand-text">
                <h1>ClearStay 3.0</h1>
                <span>かんたん操作モード (Easy Mode)</span>
            </div>
        </a>

        <div class="easy-header-controls">
            <!-- Text Size Scaler -->
            <div class="font-size-control" title="文字サイズの変更">
                <span style="font-size: 13px; font-weight: 700; padding: 0 6px; color: var(--easy-muted);">文字:</span>
                <button type="button" class="font-size-btn" onclick="adjustFontSize(-2)">小 A-</button>
                <button type="button" class="font-size-btn" onclick="adjustFontSize(0)">標準</button>
                <button type="button" class="font-size-btn" onclick="adjustFontSize(3)">特大 A+</button>
            </div>

            <!-- Return to Standard Mode -->
            <form action="{{ route('easy_mode.toggle') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="easy-btn easy-btn-switch">
                    <i class="fa-solid fa-laptop"></i> 標準モードに戻る (Standard)
                </button>
            </form>
        </div>
    </header>

    <!-- Main Content -->
    <main class="easy-container">
        @if(session('success'))
            <div class="easy-alert easy-alert-success">
                <i class="fa-solid fa-circle-check fa-xl"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="easy-alert easy-alert-info">
                <i class="fa-solid fa-circle-info fa-xl"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="easy-alert easy-alert-danger">
                <i class="fa-solid fa-triangle-exclamation fa-xl"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer style="background: #ffffff; border-top: 1px solid var(--easy-border); padding: 20px; text-align: center; color: var(--easy-muted); font-size: 15px; font-weight: 600;">
        <p>ClearStay 3.0 シニア・非技術者向け安心操作モード · Temairazu Commercial Hospitality Platform</p>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function adjustFontSize(delta) {
            let current = parseInt(localStorage.getItem('clearstay_easy_font') || 19);
            if (delta === 0) {
                current = 19;
            } else {
                current = Math.min(26, Math.max(16, current + delta));
            }
            localStorage.setItem('clearstay_easy_font', current);
            document.documentElement.style.setProperty('--easy-font-size', current + 'px');
        }

        // Apply saved font size on load
        const savedSize = localStorage.getItem('clearstay_easy_font');
        if (savedSize) {
            document.documentElement.style.setProperty('--easy-font-size', savedSize + 'px');
        }
    </script>
    @stack('scripts')
</body>
</html>

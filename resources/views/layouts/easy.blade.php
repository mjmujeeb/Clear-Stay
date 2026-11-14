<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClearStay Easy Mode — Simple Hotel Operations / ClearStay かんたん操作')</title>

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
            --easy-bg: #F4F6F9;
            --easy-card: #FFFFFF;
            --easy-navy: #0F1D2F;
            --easy-teal: #1F6B66;
            --easy-teal-hover: #175450;
            --easy-blue: #1D4ED8;
            --easy-green: #047857;
            --easy-amber: #B45309;
            --easy-red: #B91C1C;
            --easy-border: #CBD5E1;
            --easy-border-subtle: #E2E8F0;
            --easy-text: #0F172A;
            --easy-muted: #475569;
            --easy-font-size: 19px;
            --touch-target: 48px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Noto Sans JP', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            -webkit-font-smoothing: antialiased;
        }

        /* Top Header Bar */
        .easy-header {
            background: #ffffff;
            border-bottom: 3px solid var(--easy-teal);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 14px rgba(15, 29, 47, 0.08);
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
            background: linear-gradient(135deg, #1F6B66 0%, #0F1D2F 100%);
            color: #ffffff;
            font-size: 26px;
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(31, 107, 102, 0.3);
            flex-shrink: 0;
        }

        .easy-brand-text h1 {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: var(--easy-navy);
            line-height: 1.2;
        }

        .easy-brand-text .sub-title {
            font-size: 15px;
            color: var(--easy-teal);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
        }

        .easy-header-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Large Action Buttons */
        .easy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: var(--touch-target);
            padding: 10px 22px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            touch-action: manipulation;
        }

        .easy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .easy-btn:active {
            transform: translateY(0);
        }

        .easy-btn:focus-visible {
            outline: 3px solid var(--easy-teal);
            outline-offset: 3px;
        }

        .easy-btn-primary {
            background: var(--easy-teal);
            color: #ffffff;
            border-color: #175450;
        }

        .easy-btn-primary:hover {
            background: var(--easy-teal-hover);
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

        .easy-btn-guide {
            background: #EFF6FF;
            color: #1E40AF;
            border-color: #93C5FD;
            font-size: 16px;
            min-height: 44px;
            padding: 8px 16px;
        }

        .easy-btn-switch {
            background: #FEF3C7;
            color: #92400E;
            border-color: #F59E0B;
            font-size: 16px;
            min-height: 44px;
            padding: 8px 16px;
        }

        /* Container */
        .easy-container {
            max-width: 1240px;
            width: 100%;
            margin: 0 auto;
            padding: 28px 20px 48px;
            flex: 1;
        }

        /* Large Cards */
        .easy-card {
            background: #ffffff;
            border: 2px solid var(--easy-border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 4px 16px rgba(15, 29, 47, 0.05);
            margin-bottom: 24px;
        }

        /* Alerts */
        .easy-alert {
            padding: 18px 24px;
            border-radius: 14px;
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
            background: #F1F5F9;
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
            min-height: 36px;
        }

        .font-size-btn:hover {
            background: #ffffff;
        }

        /* Onboarding Modal */
        .guide-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 29, 47, 0.7);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .guide-modal {
            background: #ffffff;
            border-radius: 24px;
            max-width: 680px;
            width: 100%;
            padding: 36px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            border: 3px solid var(--easy-teal);
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .guide-step {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .guide-step-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .easy-header {
                flex-direction: column;
                gap: 16px;
                padding: 16px;
                text-align: center;
            }
            .easy-header-controls {
                justify-content: center;
                width: 100%;
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
        <a href="{{ route('easy_mode.index') }}" class="easy-brand" title="ClearStay かんたん操作ホーム">
            <div class="easy-brand-badge">
                <i class="fa-solid fa-hotel"></i>
            </div>
            <div class="easy-brand-text">
                <h1>ClearStay Easy Mode — Simple Hotel Operations</h1>
                <div class="sub-title">
                    <span>ClearStay かんたん操作</span>
                    <span style="background: #E0F2FE; color: #0369A1; font-size: 12px; font-weight: 800; padding: 2px 8px; border-radius: 6px;">v4.2</span>
                </div>
            </div>
        </a>

        <div class="easy-header-controls">
            <!-- Text Size Scaler -->
            <div class="font-size-control" title="文字サイズの変更 (Adjust text size)">
                <span style="font-size: 13px; font-weight: 700; padding: 0 6px; color: var(--easy-muted);">文字:</span>
                <button type="button" class="font-size-btn" onclick="adjustFontSize(-2)" aria-label="文字サイズ小">小 A-</button>
                <button type="button" class="font-size-btn" onclick="adjustFontSize(0)" aria-label="文字サイズ標準">標準</button>
                <button type="button" class="font-size-btn" onclick="adjustFontSize(3)" aria-label="文字サイズ特大">特大 A+</button>
            </div>

            <!-- Help / Guide Button -->
            <button type="button" class="easy-btn easy-btn-guide" onclick="openGuideModal()">
                <i class="fa-solid fa-circle-question"></i> 操作ガイド (Guide)
            </button>

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
            <div class="easy-alert easy-alert-success" role="alert">
                <i class="fa-solid fa-circle-check fa-xl"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="easy-alert easy-alert-info" role="alert">
                <i class="fa-solid fa-circle-info fa-xl"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="easy-alert easy-alert-danger" role="alert">
                <i class="fa-solid fa-triangle-exclamation fa-xl"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- First-Time Onboarding & Help Modal -->
    <div id="guideModal" class="guide-modal-overlay">
        <div class="guide-modal" role="dialog" aria-labelledby="guideTitle">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 id="guideTitle" style="font-size: 26px; font-weight: 900; color: var(--easy-navy);">
                    <i class="fa-solid fa-circle-info text-teal" style="color: var(--easy-teal);"></i> かんたん操作ガイド (Quick Start)
                </h2>
                <button type="button" onclick="closeGuideModal(false)" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--easy-muted);">&times;</button>
            </div>

            <p style="font-size: 17px; color: var(--easy-muted); margin-bottom: 24px;">
                ClearStay かんたん操作は、大きなボタンと見やすい文字で、どなたでも迷わず安全にホテルの日常業務を完了できる専用モードです。
            </p>

            <div class="guide-step">
                <div class="guide-step-icon" style="background: #E6F4F1; color: var(--easy-teal);">
                    <i class="fa-solid fa-door-open"></i>
                </div>
                <div>
                    <h3 style="font-size: 19px; font-weight: 800; color: var(--easy-navy);">1. 到着・チェックイン案内</h3>
                    <p style="font-size: 16px; color: var(--easy-muted);">「チェックイン」を押すと5ステップの案内が表示されます。お客様の名前を確認し、部屋の鍵をお渡しするだけで安全に完了します。</p>
                </div>
            </div>

            <div class="guide-step">
                <div class="guide-step-icon" style="background: #EFF6FF; color: var(--easy-blue);">
                    <i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i>
                </div>
                <div>
                    <h3 style="font-size: 19px; font-weight: 800; color: var(--easy-navy);">2. 出発・チェックアウト</h3>
                    <p style="font-size: 16px; color: var(--easy-muted);">ご出発のお客様の「チェックアウト完了」を1回タッチすると、自動的にお部屋が清掃待ち（Dirty）に切り替わり、清掃タスクが作成されます。</p>
                </div>
            </div>

            <div class="guide-step">
                <div class="guide-step-icon" style="background: #FEF3C7; color: var(--easy-amber);">
                    <i class="fa-solid fa-broom"></i>
                </div>
                <div>
                    <h3 style="font-size: 19px; font-weight: 800; color: var(--easy-navy);">3. お部屋の清掃状態</h3>
                    <p style="font-size: 16px; color: var(--easy-muted);">「部屋一覧・清掃管理」から、清掃完了（Clean）や点検中（Inspecting）のボタンをタッチするだけで、部屋の状態を即座に更新できます。</p>
                </div>
            </div>

            <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="easy-btn easy-btn-primary" onclick="closeGuideModal(true)" style="min-width: 200px;">
                    <i class="fa-solid fa-check"></i> わかりました (Got it)
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background: #ffffff; border-top: 1px solid var(--easy-border); padding: 24px; text-align: center; color: var(--easy-muted); font-size: 15px; font-weight: 600;">
        <p>ClearStay 4.2 シニア・非技術者向け安心操作モード · Simple Hotel Operations · Temairazu Commercial Hospitality Platform</p>
    </footer>

    <!-- Scripts -->
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

        // Onboarding Modal Handling
        function openGuideModal() {
            $('#guideModal').css('display', 'flex');
        }

        function closeGuideModal(persist) {
            $('#guideModal').hide();
            if (persist) {
                localStorage.setItem('clearstay_easy_onboarding_dismissed', 'true');
            }
        }

        // Show guide on first load if not previously dismissed
        $(document).ready(function() {
            if (!localStorage.getItem('clearstay_easy_onboarding_dismissed')) {
                // Show modal on first visit
                openGuideModal();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>

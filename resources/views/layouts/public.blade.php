<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#0F1D2F">
    <link rel="manifest" href="/manifest.json">
    
    <!-- SEO Primary Meta Tags -->
    <title>@yield('meta_title', 'ClearStay 3.0 - Connected Hotel Operations & Multi-Channel Distribution')</title>
    <meta name="title" content="@yield('meta_title', 'ClearStay 2.0 - Connected Hotel Operations & Multi-Channel Distribution')">
    <meta name="description" content="@yield('meta_description', 'ClearStay 2.0 is an enterprise hotel operations, central inventory, multi-channel distribution, and integration assurance platform.')">
    <meta name="keywords" content="hotel operations platform, channel manager, hotel pms, central inventory, Temairazu, multi-channel distribution, ClearStay">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical & Alternate Hreflang Tags -->
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}?lang=en">
    <link rel="alternate" hreflang="ja" href="{{ url()->current() }}?lang=ja">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sar: env(safe-area-inset-right, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
            --primary: #287F79;
            --primary-dark: #1E605B;
            --primary-light: #E6F3F2;
            --navy: #183B50;
            --navy-dark: #0F1D2F;
            --accent: #E8A64A;
            --bg: #F8FAFC;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans JP', sans-serif;
            background: #FFFFFF;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
            max-width: 100vw;
            padding-top: var(--sat);
            padding-right: var(--sar);
            padding-bottom: var(--sab);
            padding-left: var(--sal);
        }

        /* Navigation */
        .pub-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 16px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .pub-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--navy);
        }

        .pub-brand .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #287F79, #183B50);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(40, 127, 121, 0.25);
        }

        .pub-brand span {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: var(--navy);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            transition: color 0.15s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lang-pills {
            display: flex;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            font-size: 12px;
        }

        .lang-pills a {
            padding: 5px 9px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 700;
        }

        .lang-pills a.active {
            background: var(--navy);
            color: #ffffff;
        }

        .btn-demo {
            background: var(--primary);
            color: white;
            padding: 9px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(40, 127, 121, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-demo:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-login-outline {
            border: 1px solid var(--border);
            color: var(--text-dark);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.2s;
        }

        .btn-login-outline:hover {
            border-color: var(--navy);
            color: var(--navy);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--navy);
            cursor: pointer;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Footer */
        .pub-footer {
            background: var(--navy-dark);
            color: #94A3B8;
            padding: 70px 0 35px;
            font-size: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            color: #FFFFFF;
            font-size: 15px;
            margin-bottom: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul a {
            color: #94A3B8;
            text-decoration: none;
            transition: color 0.15s;
        }

        .footer-col ul a:hover {
            color: #FFFFFF;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            flex-wrap: wrap;
            gap: 12px;
        }

        @media (max-width: 900px) {
            .nav-links {
                display: none;
            }
            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 44px;
                min-height: 44px;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .pub-nav {
                padding: 12px 16px;
            }
        }

        @media (max-width: 640px) {
            .pub-brand span {
                font-size: 16px;
            }
            .btn-demo {
                padding: 6px 12px;
                font-size: 12px;
            }
            .btn-login-outline {
                padding: 6px 10px;
                font-size: 12px;
            }
            .container {
                padding: 0 16px;
            }
        }

        @media (max-width: 420px) {
            .pub-brand small {
                display: none;
            }
            .btn-login-outline {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="pub-nav">
        <a href="{{ route('public.home') }}" class="pub-brand">
            <div class="logo-icon"><i class="fa-solid fa-hotel"></i></div>
            <span>ClearStay <small style="font-size: 11px; font-weight: 800; background: var(--primary-light); color: var(--primary); padding: 2px 6px; border-radius: 4px; margin-left: 2px;">3.0</small></span>
        </a>

        <ul class="nav-links">
            <li><a href="{{ route('public.home') }}#three-products" style="color: var(--primary); font-weight: 700;"><i class="fa-solid fa-layer-group"></i> 3 Products</a></li>
            <li><a href="{{ route('experience_lab.index') }}"><i class="fa-solid fa-microscope"></i> 体験ラボ (QA)</a></li>
            <li><a href="{{ route('easy_mode.index') }}"><i class="fa-solid fa-person-shelter"></i> 簡単モード</a></li>
            <li><a href="{{ route('learning_centre.index') }}"><i class="fa-solid fa-film"></i> 動画ガイド</a></li>
            <li><a href="{{ route('public.features') }}">Architecture</a></li>
            <li><a href="{{ route('public.contact') }}">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <div class="lang-pills">
                <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                <a href="{{ route('locale.switch', 'ja') }}" class="{{ app()->getLocale() === 'ja' ? 'active' : '' }}">日本語</a>
            </div>

            @auth
                <a href="{{ route('dashboard') }}" class="btn-demo">
                    <i class="fa-solid fa-gauge"></i> Open Console
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-login-outline">Sign In</a>
                <a href="{{ route('login') }}" class="btn-demo">
                    <i class="fa-solid fa-play"></i> Live Demo
                </a>
            @endauth

            <button class="mobile-menu-btn" onclick="toggleMobileNav()" aria-label="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Nav Drawer -->
    <div id="mobileNavDrawer" style="display: none; background: white; border-bottom: 2px solid var(--border); padding: 20px; position: sticky; top: 68px; z-index: 999;">
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 14px;">
            <li><a href="#product-demo" onclick="toggleMobileNav()" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">Product Tour</a></li>
            <li><a href="#features-section" onclick="toggleMobileNav()" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">Features</a></li>
            <li><a href="#integrations-section" onclick="toggleMobileNav()" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">Channels &amp; OTAs</a></li>
            <li><a href="#workflow-section" onclick="toggleMobileNav()" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">Distribution Pipeline</a></li>
            <li><a href="#security-section" onclick="toggleMobileNav()" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">Security</a></li>
            <li><a href="{{ route('public.features') }}" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">Architecture &amp; Docs</a></li>
            <li><a href="{{ route('public.contact') }}" style="text-decoration: none; color: var(--primary); font-weight: 800;">Request Private Demo</a></li>
        </ul>
    </div>

    <main>
        @yield('content')
    </main>

    <!-- Professional Hospitality Footer -->
    <footer class="pub-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <div class="pub-brand logo-icon" style="width: 32px; height: 32px; font-size: 16px;"><i class="fa-solid fa-hotel"></i></div>
                        <span style="color: white; font-size: 18px; font-weight: 800;">ClearStay 3.0</span>
                    </div>
                    <p style="font-size: 13px; line-height: 1.7; color: #94A3B8; max-width: 340px;">
                        Smart Hotel Operations, Multi-Channel Distribution, Integration Assurance and Intelligent Access Governance Platform.
                    </p>
                    <div style="margin-top: 14px; display: flex; gap: 12px; font-size: 16px;">
                        <span title="PHP 8.3 & Laravel 11/12"><i class="fa-brands fa-laravel" style="color: #F43F5E;"></i></span>
                        <span title="Transactional Outbox"><i class="fa-solid fa-database" style="color: #38BDF8;"></i></span>
                        <span title="Context-Aware Security"><i class="fa-solid fa-shield-halved" style="color: #34D399;"></i></span>
                        <span title="Japanese & English UI"><i class="fa-solid fa-language" style="color: #FBBF24;"></i></span>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Platform Capabilities</h4>
                    <ul>
                        <li><a href="{{ route('public.home') }}#product-demo">Interactive Product Tour</a></li>
                        <li><a href="{{ route('public.home') }}#features-section">Central Inventory Matrix</a></li>
                        <li><a href="{{ route('public.home') }}#integrations-section">Channel Distribution</a></li>
                        <li><a href="{{ route('public.home') }}#workflow-section">Smart Rate Studio</a></li>
                        <li><a href="{{ route('public.home') }}#security-section">Access Governance</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Workspaces &amp; Auth</h4>
                    <ul>
                        <li><a href="{{ route('login') }}">Staff Operations Portal</a></li>
                        <li><a href="{{ route('public.contact') }}">Schedule Live Consultation</a></li>
                        <li><a href="{{ route('public.features') }}">Architecture Whitepaper</a></li>
                        <li><a href="{{ url('/sitemap.xml') }}">Sitemap</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Enterprise Quality</h4>
                    <p style="font-size: 12px; margin-bottom: 8px; color: #94A3B8;"><i class="fa-solid fa-check" style="color: var(--primary);"></i> Row-Locked Transactions (<code style="color: #38BDF8;">FOR UPDATE</code>)</p>
                    <p style="font-size: 12px; margin-bottom: 8px; color: #94A3B8;"><i class="fa-solid fa-check" style="color: var(--primary);"></i> Transactional Outbox Pattern</p>
                    <p style="font-size: 12px; margin-bottom: 8px; color: #94A3B8;"><i class="fa-solid fa-check" style="color: var(--primary);"></i> Multi-Tenant Property Isolation</p>
                    <p style="font-size: 12px; color: #94A3B8;"><i class="fa-solid fa-check" style="color: var(--primary);"></i> 100% Deterministic Testing</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} ClearStay Hospitality Platform. Built for Enterprise Hotel Operations &amp; Distribution.</p>
                <p>Designed for Japanese and Global Accommodation Ecosystems (英語 / 日本語対応)</p>
            </div>
        </div>
    </footer>

    <script>
    function toggleMobileNav() {
        const drawer = document.getElementById('mobileNavDrawer');
        drawer.style.display = drawer.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>
</html>

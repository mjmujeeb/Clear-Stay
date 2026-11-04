<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Primary Meta Tags -->
    <title>@yield('meta_title', 'ClearStay - Modern Hospitality & Hotel Stay Management System')</title>
    <meta name="title" content="@yield('meta_title', 'ClearStay - Modern Hospitality & Hotel Stay Management System')">
    <meta name="description" content="@yield('meta_description', 'ClearStay is an enterprise-grade multi-tenant hotel reservation and operations management system with concurrency row-locking and real-time room boards.')">
    <meta name="keywords" content="hotel management system, hotel pms, room board, reservation system, hospitality software, multi-tenant hotel, ClearStay">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical & Alternate Hreflang Tags -->
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}?lang=en">
    <link rel="alternate" hreflang="ja" href="{{ url()->current() }}?lang=ja">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('meta_title', 'ClearStay - Modern Hospitality & Hotel Stay Management')">
    <meta property="og:description" content="@yield('meta_description', 'High-concurrency hotel operations platform with live room board, multi-tenancy, and analytics.')">
    <meta property="og:image" content="{{ asset('images/clearstay-og.png') }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ja' ? 'ja_JP' : 'en_US' }}">

    <!-- Twitter Card -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('meta_title', 'ClearStay - Modern Hospitality & Hotel Stay Management')">
    <meta property="twitter:description" content="@yield('meta_description', 'High-concurrency hotel operations platform with live room board, multi-tenancy, and analytics.')">

    <!-- JSON-LD Structured Data Schema (LodgingBusiness / Hotel) -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "LodgingBusiness",
      "name": "ClearStay Hospitality Network",
      "description": "Enterprise-grade hotel stay management system providing seamless reservations, real-time room dispatch, and guest self-service portals.",
      "url": "{{ url('/') }}",
      "priceRange": "¥12,000 - ¥85,000",
      "address": {
        "@@type": "PostalAddress",
        "addressLocality": "Tokyo",
        "addressRegion": "Kanto",
        "addressCountry": "JP"
      },
      "openingHours": "Mo-Su 00:00-24:00"
    }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --secondary: #0f172a;
            --accent: #38bdf8;
            --bg: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans JP', sans-serif;
            background: #ffffff;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Public Navigation */
        .pub-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .pub-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-dark);
        }

        .pub-brand .logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .pub-brand span {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .lang-pills {
            display: flex;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            font-size: 12px;
        }

        .lang-pills a {
            padding: 4px 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
        }

        .lang-pills a.active {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-portal {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: background 0.2s;
        }

        .btn-portal:hover {
            background: var(--primary-dark);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Footer */
        .pub-footer {
            background: var(--secondary);
            color: #94a3b8;
            padding: 60px 0 30px;
            margin-top: 80px;
            font-size: 14px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            color: #ffffff;
            font-size: 16px;
            margin-bottom: 16px;
            font-weight: 700;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-col ul a:hover {
            color: #ffffff;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="pub-nav">
        <a href="{{ route('public.home') }}" class="pub-brand">
            <div class="logo"><i class="fa-solid fa-hotel"></i></div>
            <span>ClearStay</span>
        </a>

        <ul class="nav-links">
            <li><a href="{{ route('public.home') }}" class="{{ request()->routeIs('public.home') ? 'active' : '' }}">{{ __('clearstay.nav.home') }}</a></li>
            <li><a href="{{ route('public.rooms') }}" class="{{ request()->routeIs('public.rooms') ? 'active' : '' }}">{{ __('clearstay.nav.rooms') }}</a></li>
            <li><a href="{{ route('public.features') }}" class="{{ request()->routeIs('public.features') ? 'active' : '' }}">{{ __('clearstay.nav.features') }}</a></li>
            <li><a href="{{ route('public.contact') }}" class="{{ request()->routeIs('public.contact') ? 'active' : '' }}">{{ __('clearstay.nav.contact') }}</a></li>
        </ul>

        <div class="nav-actions">
            <div class="lang-pills">
                <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                <a href="{{ route('locale.switch', 'ja') }}" class="{{ app()->getLocale() === 'ja' ? 'active' : '' }}">日本語</a>
            </div>

            @auth
                <a href="{{ route('dashboard') }}" class="btn-portal"><i class="fa-solid fa-gauge"></i> Console</a>
            @else
                <a href="{{ route('login') }}" class="btn-portal"><i class="fa-solid fa-key"></i> Staff Login</a>
            @endauth
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="pub-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <div class="pub-brand logo" style="width: 32px; height: 32px; font-size: 16px;"><i class="fa-solid fa-hotel"></i></div>
                        <span style="color: white; font-size: 18px; font-weight: 800;">ClearStay</span>
                    </div>
                    <p style="font-size: 13px; line-height: 1.7;">
                        {{ __('clearstay.description') }} Built with PHP, Laravel 11/12, MySQL, Chart.js, and jQuery for modern hospitality.
                    </p>
                </div>
                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="{{ route('public.home') }}">Home</a></li>
                        <li><a href="{{ route('public.rooms') }}">Rooms & Suites</a></li>
                        <li><a href="{{ route('public.features') }}">Architecture & Features</a></li>
                        <li><a href="{{ route('public.contact') }}">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Operations</h4>
                    <ul>
                        <li><a href="{{ route('login') }}">Staff Portal</a></li>
                        <li><a href="{{ route('register') }}">Staff Registration</a></li>
                        <li><a href="{{ url('/sitemap.xml') }}">Sitemap (XML)</a></li>
                        <li><a href="{{ url('/robots.txt') }}">Robots.txt</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Security & Stack</h4>
                    <p style="font-size: 12px; margin-bottom: 8px;"><i class="fa-solid fa-shield-halved"></i> DB Row-Locked Transactions</p>
                    <p style="font-size: 12px; margin-bottom: 8px;"><i class="fa-solid fa-network-wired"></i> Multi-Tenant (hotel_id)</p>
                    <p style="font-size: 12px;"><i class="fa-solid fa-server"></i> AWS & Ubuntu Nginx Ready</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} ClearStay Hospitality System. All rights reserved.</p>
                <p>Designed for Japanese Hotel Operations (英語 / 日本語対応)</p>
            </div>
        </div>
    </footer>
</body>
</html>

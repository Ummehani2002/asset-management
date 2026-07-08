<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1F2A44">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Work Log">
    <link rel="manifest" href="{{ asset('work-log-manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/work-log-icon-192.png') }}">
    <title>@yield('title', 'Work Log') — Tanseeq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #1F2A44;
            --secondary: #C6A87D;
            --bg: #F4F6F9;
            --card: #FFFFFF;
            --muted: #6B7280;
            --success: #198754;
            --warning: #FFC107;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--primary);
            min-height: 100vh;
            padding-bottom: calc(72px + env(safe-area-inset-bottom));
        }

        body.no-nav {
            padding-bottom: env(safe-area-inset-bottom);
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: linear-gradient(135deg, var(--primary), #2C3E66);
            color: #fff;
            padding: 14px 16px;
            padding-top: calc(14px + env(safe-area-inset-top));
            box-shadow: 0 2px 12px rgba(31, 42, 68, 0.2);
        }

        .app-header h1 {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
        }

        .app-header small {
            opacity: 0.85;
            font-size: 0.75rem;
        }

        .app-content {
            padding: 16px;
            max-width: 640px;
            margin: 0 auto;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .stat-card {
            background: var(--card);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .stat-card .num {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card .lbl {
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .log-card {
            background: var(--card);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border-left: 4px solid var(--secondary);
        }

        .log-card.completed { border-left-color: var(--success); }
        .log-card.pending { border-left-color: var(--warning); }

        .form-card {
            background: var(--card);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .form-control, .form-select {
            font-size: 16px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #D1D5DB;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31, 42, 68, 0.12);
        }

        .info-bar {
            background: #EEF2F7;
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .btn-app {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
        }

        .btn-app:active { opacity: 0.9; }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            padding-bottom: calc(8px + env(safe-area-inset-bottom));
            z-index: 100;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--muted);
            font-size: 0.68rem;
            padding: 4px 16px;
            gap: 2px;
        }

        .bottom-nav a i { font-size: 1.3rem; }

        .bottom-nav a.active {
            color: var(--primary);
            font-weight: 600;
        }

        .bottom-nav a.nav-new {
            color: var(--secondary);
        }

        .bottom-nav a.nav-new i {
            background: var(--primary);
            color: #fff;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: -20px;
            box-shadow: 0 4px 12px rgba(31,42,68,0.3);
        }

        .install-banner {
            background: #1F2A44;
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            display: none;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
        }

        .install-banner.show { display: flex; }

        .install-help {
            background: #fff;
            border: 1px solid #D1D5DB;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 14px;
            font-size: 0.85rem;
        }

        .install-help h6 {
            margin: 0 0 8px;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .install-help ol {
            margin: 0;
            padding-left: 18px;
        }

        .install-help li { margin-bottom: 6px; }

        .badge-status {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 20px;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(160deg, #1F2A44 0%, #2C3E66 50%, #F4F6F9 50%);
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px 22px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            margin-top: 40px;
        }

        .login-logo {
            text-align: center;
            color: #fff;
            margin-bottom: 0;
        }

        .login-logo i { font-size: 2.5rem; }
        .login-logo h1 { font-size: 1.3rem; margin: 8px 0 4px; }
        .login-logo p { opacity: 0.85; font-size: 0.85rem; margin: 0; }
    </style>
    @stack('styles')
</head>
<body class="{{ auth()->check() && !View::hasSection('header') ? '' : 'no-nav' }}">
    @hasSection('header')
        @yield('header')
    @else
        <header class="app-header d-flex justify-content-between align-items-center">
            <div>
                <h1>@yield('page-title', 'Work Log')</h1>
                <small>{{ Auth::user()->name ?? '' }}</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('worklog.index') }}" class="btn btn-sm btn-outline-light" title="{{ Auth::user()?->isTimeManagementAdmin() ? 'Team Progress' : 'My Tickets' }}">
                    <i class="bi bi-{{ Auth::user()?->isTimeManagementAdmin() ? 'people' : 'ticket-perforated' }}"></i>
                </a>
                <form action="{{ route('worklog.logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light border-0">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </header>
    @endif

    <main class="app-content">
        <div id="installHelp" class="install-help" style="display:none;">
            <h6><i class="bi bi-phone me-1"></i> Add to Home Screen</h6>
            <div id="installHelpIos" style="display:none;">
                <ol>
                    <li>Tap the <strong>Share</strong> button at the bottom of Safari (square with arrow).</li>
                    <li>Scroll down and tap <strong>Add to Home Screen</strong>.</li>
                    <li>Tap <strong>Add</strong>.</li>
                </ol>
            </div>
            <div id="installHelpAndroid" style="display:none;">
                <ol>
                    <li>Tap the <strong>3 dots</strong> at the top right in Chrome.</li>
                    <li>Tap <strong>Add to Home screen</strong> or <strong>Install app</strong>.</li>
                    <li>Tap <strong>Add</strong> or <strong>Install</strong>.</li>
                </ol>
            </div>
            <button type="button" id="installHelpDismiss" class="btn btn-sm btn-outline-secondary mt-2">Got it</button>
        </div>
        @yield('content')
    </main>

    @auth
        @unless(View::hasSection('header'))
        <nav class="bottom-nav">
            <a href="{{ route('worklog.create') }}" class="{{ request()->routeIs('worklog.create') ? 'active' : '' }}">
                <i class="bi bi-pencil-square"></i>
                New Log
            </a>
            <a href="{{ route('worklog.index') }}" class="{{ request()->routeIs('worklog.index') ? 'active' : '' }}">
                <i class="bi bi-{{ Auth::user()->isTimeManagementAdmin() ? 'people' : 'ticket-perforated' }}"></i>
                {{ Auth::user()->isTimeManagementAdmin() ? 'All Tickets' : 'My Tickets' }}
            </a>
        </nav>
        @endunless
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('work-log-sw.js') }}').catch(() => {});
        }

        let deferredPrompt;
        const installBanner = document.getElementById('installBanner');
        const installBtn = document.getElementById('installBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installBanner) installBanner.classList.add('show');
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                if (installBanner) installBanner.classList.remove('show');
            });
        }

        const installHelp = document.getElementById('installHelp');
        const installHelpIos = document.getElementById('installHelpIos');
        const installHelpAndroid = document.getElementById('installHelpAndroid');
        const installHelpDismiss = document.getElementById('installHelpDismiss');
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isAndroid = /android/i.test(navigator.userAgent);
        const helpDismissed = localStorage.getItem('worklog_install_help_dismissed') === '1';

        if (installHelp && !isStandalone && !helpDismissed) {
            installHelp.style.display = 'block';
            if (isIos && installHelpIos) installHelpIos.style.display = 'block';
            if (isAndroid && installHelpAndroid) installHelpAndroid.style.display = 'block';
            if (!isIos && !isAndroid) {
                if (installHelpIos) installHelpIos.style.display = 'block';
                if (installHelpAndroid) installHelpAndroid.style.display = 'block';
            }
        }

        if (installHelpDismiss) {
            installHelpDismiss.addEventListener('click', () => {
                localStorage.setItem('worklog_install_help_dismissed', '1');
                if (installHelp) installHelp.style.display = 'none';
            });
        }
    </script>
    @stack('scripts')
</body>
</html>

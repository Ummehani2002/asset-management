<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') - Tanseeq Investment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1F2A44;
            --accent: #C6A87D;
            --accent-light: #d4b98f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
        }

        .auth-split {
            display: flex;
            min-height: 100vh;
        }

        /* Left column - promotional */
        .auth-promo {
            flex: 1;
            background: linear-gradient(160deg, #0f1729 0%, #1F2A44 50%, #243050 100%);
            position: relative;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-promo::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(198, 168, 125, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(198, 168, 125, 0.03) 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
        }

        .promo-tagline {
            color: #fff;
            font-size: 1.85rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .promo-desc {
            color: rgba(255, 255, 255, 0.75);
            font-size: 1rem;
            line-height: 1.6;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }

        /* Right column - form */
        .auth-form-col {
            width: 100%;
            max-width: 480px;
            background: #fff;
            padding: 40px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 48px;
        }

        .auth-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1px;
            text-decoration: none;
        }

        .auth-logo:hover {
            color: var(--primary);
        }

        .auth-logo img {
            max-height: 72px;
            width: auto;
        }

        .auth-logo .logo-text {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1px;
        }

        .auth-account-link {
            font-size: 0.9rem;
            color: #666;
        }

        .auth-account-link a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-account-link a:hover {
            color: var(--primary);
        }

        .auth-welcome {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(198, 168, 125, 0.2);
            outline: none;
        }

        .auth-forgot {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 16px;
        }

        .auth-forgot a {
            color: var(--accent);
            font-size: 0.9rem;
            text-decoration: none;
        }

        .auth-forgot a:hover {
            color: var(--primary);
        }

        .btn-auth {
            background: linear-gradient(135deg, var(--accent-light) 0%, var(--accent) 50%, #b8956a 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 14px 24px;
            border-radius: 8px;
            width: 100%;
            font-size: 1rem;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-auth:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(198, 168, 125, 0.4);
        }

        .auth-divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: #999;
            font-size: 0.85rem;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .auth-divider span {
            padding: 0 16px;
        }

        .auth-alt-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .auth-alt-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid #ddd;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.2rem;
            transition: background 0.2s, border-color 0.2s;
        }

        .auth-alt-btn:hover {
            background: #f8f8f8;
            border-color: #ccc;
            color: #333;
        }

        .auth-footer-text {
            text-align: center;
            margin-top: 24px;
            font-size: 0.9rem;
            color: #666;
        }

        .auth-footer-text a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-text a:hover {
            color: var(--primary);
        }

        .alert {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .auth-split {
                flex-direction: column;
            }

            .auth-promo {
                padding: 32px 24px;
                min-height: auto;
            }

            .promo-tagline {
                font-size: 1.5rem;
            }

            .auth-form-col {
                max-width: none;
                padding: 32px 24px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="auth-split">
        <!-- Left: Promotional -->
        <div class="auth-promo">
            <h2 class="promo-tagline">Track, Organize, and Protect Your Assets</h2>
            <p class="promo-desc">Access your asset management dashboard to track inventory, assign assets to employees, and maintain your organization's equipment lifecycle.</p>
        </div>

        <!-- Right: Form -->
        <div class="auth-form-col">
            <div class="auth-header">
                <a href="{{ url('/') }}" class="auth-logo d-flex align-items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Tanseeq" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span class="logo-text" style="display:none;">Tanseeq</span>
                </a>
                @hasSection('account-link')
                    @yield('account-link')
                @endif
            </div>

            @yield('content')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema Administrativo') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Rhythm CSS -->
        <link rel="stylesheet" href="{{ asset('css/rhythm.css') }}">
    </head>
    <body>
        <div class="r-login-wrap">
            <a href="/" class="r-login-logo">
                <div class="r-login-logo-icon">SA</div>
                <span>Sistema Administrativo</span>
            </a>

            <div class="r-login-card">
                @yield('content')
            </div>
        </div>

        <style>
            body {
                margin: 0;
                background: var(--color-paper);
            }
            .r-login-wrap {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: var(--page-margin);
                background: var(--color-paper);
                background-image:
                    radial-gradient(circle at 20% 30%, rgba(226,161,59,0.06) 0%, transparent 50%),
                    radial-gradient(circle at 80% 70%, rgba(91,110,82,0.05) 0%, transparent 50%);
            }
            .r-login-logo {
                display: flex;
                align-items: center;
                gap: var(--space-3);
                text-decoration: none;
                margin-bottom: var(--space-8);
            }
            .r-login-logo-icon {
                width: 48px;
                height: 48px;
                background: var(--color-marigold);
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: var(--font-display);
                font-weight: 700;
                font-size: 1rem;
                color: var(--color-ink);
            }
            .r-login-logo span {
                font-family: var(--font-display);
                font-weight: 600;
                font-size: 1.125rem;
                color: var(--color-ink);
            }
            .r-login-card {
                width: 100%;
                max-width: 420px;
                background: var(--color-paper-raised);
                border: 1px solid var(--color-line);
                border-radius: var(--border-radius);
                padding: var(--space-8);
                box-shadow: var(--shadow-md);
            }
            .r-login-card .r-label {
                font-family: var(--font-mono);
                font-size: 0.6875rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--color-ink-soft);
                margin-bottom: var(--space-1);
                display: block;
            }
            .r-login-card .r-input {
                width: 100%;
                margin-bottom: var(--space-4);
            }
            .r-login-card .r-btn {
                width: 100%;
                justify-content: center;
                margin-top: var(--space-4);
            }
            .r-login-card .r-flex-between {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-top: var(--space-3);
            }
            .r-login-card .r-link {
                font-family: var(--font-body);
                font-size: 0.8125rem;
                color: var(--color-ink-soft);
                text-decoration: underline;
                transition: color var(--duration-fast) var(--ease-rhythm);
            }
            .r-login-card .r-link:hover {
                color: var(--color-marigold);
            }
            .r-login-card .r-flash {
                padding: var(--space-3) var(--space-4);
                border-radius: var(--border-radius-sm);
                margin-bottom: var(--space-4);
                font-family: var(--font-body);
                font-size: 0.875rem;
            }
            .r-login-card .r-flash-success {
                background: var(--color-moss-pale);
                color: var(--color-moss);
                border: 1px solid rgba(91,110,82,0.2);
            }
            .r-login-card .r-error {
                color: #dc2626;
                font-size: 0.8125rem;
                margin-top: calc(-1 * var(--space-2));
                margin-bottom: var(--space-3);
            }
            .r-login-card .r-checkbox-wrap {
                display: flex;
                align-items: center;
                gap: var(--space-2);
            }
            .r-login-card .r-checkbox-wrap input[type="checkbox"] {
                width: 16px;
                height: 16px;
                accent-color: var(--color-marigold);
            }
            .r-login-card .r-checkbox-wrap label {
                font-family: var(--font-body);
                font-size: 0.875rem;
                color: var(--color-ink-soft);
            }
        </style>
    </body>
</html>

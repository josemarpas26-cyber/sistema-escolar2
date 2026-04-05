<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Login') — SIGA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
            background: #f8fafc;
        }

        /* ── LEFT PANEL ── */
        .auth-left {
            display: none;
            width: 45%;
            background: linear-gradient(160deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%);
            position: relative;
            overflow: hidden;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
        }
        @media (min-width: 1024px) { .auth-left { display: flex; } }

        /* Decorative circles */
        .auth-left::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -120px;
            left: -60px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
        }

        .auth-left-top { position: relative; z-index: 1; }
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 64px;
        }
        .auth-brand-logo {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .auth-brand-logo img { width: 28px; height: 28px; object-fit: contain; }
        .auth-brand-name {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.4px;
        }

        .auth-headline {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -.6px;
            margin-bottom: 16px;
        }
        .auth-sub {
            font-size: 16px;
            color: rgba(255,255,255,.7);
            line-height: 1.6;
            max-width: 340px;
        }

        /* Features */
        .auth-features {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .auth-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255,255,255,.09);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.12);
        }
        .auth-feature-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            flex-shrink: 0;
        }
        .auth-feature-text {
            font-size: 13.5px;
            font-weight: 600;
            color: rgba(255,255,255,.9);
        }
        .auth-feature-desc {
            font-size: 12px;
            color: rgba(255,255,255,.55);
            margin-top: 1px;
        }

        /* School name badge */
        .auth-school-badge {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 9999px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,.8);
        }

        /* ── RIGHT PANEL ── */
        .auth-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            overflow-y: auto;
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
        }

        /* Mobile brand */
        .auth-mobile-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 32px;
        }
        @media (min-width: 1024px) { .auth-mobile-brand { display: none; } }
        .auth-mobile-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .auth-mobile-logo img { width: 32px; height: 32px; object-fit: contain; }

        /* Form */
        .auth-title {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.5px;
            margin-bottom: 6px;
        }
        .auth-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
        }

        .auth-field {
            margin-bottom: 16px;
        }
        .auth-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            letter-spacing: .01em;
        }
        .auth-input-wrap { position: relative; }
        .auth-input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }
        .auth-input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: #0f172a;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
        }
        .auth-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .auth-input.error { border-color: #dc2626; }
        .auth-error {
            font-size: 12px;
            color: #dc2626;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .auth-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .auth-checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
        }
        .auth-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            accent-color: #2563eb;
        }
        .auth-checkbox-label {
            font-size: 13px;
            color: #475569;
            font-weight: 500;
        }
        .auth-link {
            font-size: 13px;
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
        }
        .auth-link:hover { text-decoration: underline; }

        .auth-submit {
            width: 100%;
            height: 46px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .15s, transform .1s;
            letter-spacing: .01em;
        }
        .auth-submit:hover { background: #1d4ed8; }
        .auth-submit:active { transform: scale(.99); }

        /* Alert */
        .auth-alert {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }
        .auth-alert.success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
        .auth-alert.error   { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .auth-alert.warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }

        /* Demo creds */
        .auth-demo {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .auth-demo-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 12px;
        }
        .auth-demo-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 12.5px;
        }
        .auth-demo-role { font-weight: 700; color: #334155; }
        .auth-demo-cred { color: #64748b; font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>

    <!-- Left panel (desktop) -->
    <div class="auth-left">
        <div class="auth-left-top">
            <div class="auth-brand">
                <div class="auth-brand-logo">
                    <img src="{{ asset('images/logo1.png') }}" alt="SIGA">
                </div>
                <span class="auth-brand-name">SIGA</span>
            </div>

            <h1 class="auth-headline">Gestão escolar<br>simplificada.</h1>
            <p class="auth-sub">Sistema completo para lançamento e visualização de notas, relatórios e estatísticas académicas.</p>
        </div>

        <div class="auth-features">
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-bolt"></i></div>
                <div>
                    <div class="auth-feature-text">Boletins em menos de 1 min</div>
                    <div class="auth-feature-desc">Antes demorava 30 minutos por aluno</div>
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="auth-feature-text">Sem erros de cálculo</div>
                    <div class="auth-feature-desc">Médias calculadas automaticamente</div>
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-chart-bar"></i></div>
                <div>
                    <div class="auth-feature-text">Relatórios e estatísticas</div>
                    <div class="auth-feature-desc">Visibilidade completa do desempenho</div>
                </div>
            </div>
        </div>

        <div>
            <div class="auth-school-badge">
                <i class="fas fa-school"></i>
                IPIKK-NV — Instituto Politécnico Industrial
            </div>
        </div>
    </div>

    <!-- Right panel -->
    <div class="auth-right">
        <div class="auth-card">

            <!-- Mobile brand -->
            <div class="auth-mobile-brand">
                <div class="auth-mobile-logo">
                    <img src="{{ asset('images/logo1.png') }}" alt="SIGA">
                </div>
                <span style="font-size:22px;font-weight:800;color:#0f172a;">SIGA</span>
            </div>

            <!-- Alerts -->
            @if(session('success'))
            <div class="auth-alert success"><i class="fas fa-check-circle"></i><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
            <div class="auth-alert error"><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>
            @endif
            @if(session('warning'))
            <div class="auth-alert warning"><i class="fas fa-triangle-exclamation"></i><span>{{ session('warning') }}</span></div>
            @endif
            @if($errors->any())
            <div class="auth-alert error">
                <i class="fas fa-circle-exclamation" style="flex-shrink:0"></i>
                <div>
                    @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
            @endif

            @yield('content')

        </div>
    </div>

</body>
</html>
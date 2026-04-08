<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('siga-theme');
                if (theme === 'dark') document.documentElement.classList.add('dark');
                if (theme === 'light') document.documentElement.classList.remove('dark');
            } catch (error) {}
        })();
    </script>

    <title>@yield('title', 'Login') - SIGA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            font-family: var(--font-body, 'Plus Jakarta Sans', system-ui, sans-serif);
            color: var(--text-primary, #0f172a);
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 34%),
                radial-gradient(circle at bottom right, rgba(15, 118, 110, .08), transparent 28%),
                linear-gradient(180deg, var(--surface-2, #f8fafc), var(--bg, #f1f5f9));
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1080px) {
            .auth-shell {
                grid-template-columns: minmax(360px, 44%) minmax(0, 56%);
            }
        }

        .auth-left {
            display: none;
            position: relative;
            overflow: hidden;
            padding: clamp(32px, 5vw, 56px);
            background:
                radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 30%),
                radial-gradient(circle at bottom left, rgba(255,255,255,.12), transparent 34%),
                linear-gradient(160deg, #0f2c7a 0%, #1d4ed8 48%, #2563eb 100%);
            color: #fff;
        }

        .auth-left::before,
        .auth-left::after {
            content: '';
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
        }

        .auth-left::before {
            top: -120px;
            right: -80px;
            width: 320px;
            height: 320px;
            background: rgba(255,255,255,.08);
        }

        .auth-left::after {
            bottom: -140px;
            left: -80px;
            width: 420px;
            height: 420px;
            background: rgba(255,255,255,.05);
        }

        @media (min-width: 1080px) {
            .auth-left {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
        }

        .auth-left-top,
        .auth-features,
        .auth-school-wrap {
            position: relative;
            z-index: 1;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: clamp(40px, 8vh, 84px);
        }

        .auth-brand-logo,
        .auth-mobile-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: rgba(255,255,255,.16);
            backdrop-filter: blur(10px);
        }

        .auth-brand-logo img,
        .auth-mobile-logo img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .auth-brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.04em;
            color: #fff;
        }

        .auth-headline {
            margin: 0 0 16px;
            font-size: clamp(32px, 4vw, 46px);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -.05em;
            color: #fff;
        }

        .auth-sub {
            max-width: 34rem;
            margin: 0;
            font-size: 16px;
            line-height: 1.7;
            color: rgba(255,255,255,.78);
        }

        .auth-features {
            display: grid;
            gap: 12px;
        }

        .auth-feature {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.10);
            backdrop-filter: blur(12px);
        }

        .auth-feature-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
            color: #fff;
            background: rgba(255,255,255,.14);
        }

        .auth-feature-text {
            font-size: 13.5px;
            font-weight: 700;
            color: rgba(255,255,255,.94);
        }

        .auth-feature-desc {
            margin-top: 2px;
            font-size: 12px;
            color: rgba(255,255,255,.65);
        }

        .auth-school-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 9999px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.12);
            color: rgba(255,255,255,.84);
            font-size: 12px;
            font-weight: 600;
        }

        .auth-right {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 72px 20px 28px;
            overflow-y: auto;
        }

        .auth-theme-toggle {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--surface-border, #e2e8f0);
            background: rgba(255,255,255,.78);
            color: var(--text-secondary, #334155);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(12px);
            transition: background var(--dur-fast), color var(--dur-fast), border-color var(--dur-fast), transform var(--dur-fast);
        }

        .auth-theme-toggle:hover {
            transform: translateY(-1px);
            background: var(--surface-card, #fff);
            color: var(--text-primary, #0f172a);
            border-color: var(--surface-border-strong, #cbd5e1);
        }

        .auth-card {
            width: min(100%, 440px);
            padding: clamp(22px, 3vw, 34px);
            border-radius: 24px;
            border: 1px solid var(--surface-border, #e2e8f0);
            background: rgba(255,255,255,.86);
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(16px);
        }

        .dark .auth-card {
            background: rgba(13,17,23,.82);
        }

        .auth-mobile-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        @media (min-width: 1080px) {
            .auth-mobile-brand {
                display: none;
            }
        }

        .auth-mobile-logo {
            background: linear-gradient(145deg, var(--blue-500, #3b82f6), var(--blue-700, #1d4ed8));
        }

        .auth-mobile-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.04em;
            color: var(--text-primary, #0f172a);
        }

        .auth-title {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.04em;
            color: var(--text-primary, #0f172a);
        }

        .auth-subtitle {
            margin: 0 0 28px;
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-secondary, #64748b);
        }

        .auth-field {
            margin-bottom: 16px;
        }

        .auth-label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .03em;
            color: var(--text-muted, #475569);
        }

        .auth-input-wrap {
            position: relative;
        }

        .auth-input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary, #94a3b8);
            font-size: 14px;
            pointer-events: none;
        }

        .auth-input {
            width: 100%;
            height: 46px;
            padding: 0 14px 0 40px;
            border: 1.5px solid var(--inp-border, #e2e8f0);
            border-radius: 12px;
            font-size: 14px;
            color: var(--inp-tx, #0f172a);
            background: var(--inp-bg, #fff);
            outline: none;
            transition: border-color var(--dur-fast), box-shadow var(--dur-fast), background var(--dur-fast);
        }

        .auth-input:focus {
            border-color: var(--inp-border-focus, #2563eb);
            box-shadow: var(--shadow-ring);
        }

        .auth-input.error {
            border-color: var(--err-ico, #dc2626);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
        }

        .auth-error {
            margin-top: 6px;
            font-size: 12px;
            color: var(--err-tx, #dc2626);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .auth-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }

        .auth-checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .auth-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            accent-color: var(--blue-600, #2563eb);
        }

        .auth-checkbox-label {
            font-size: 13px;
            color: var(--text-secondary, #475569);
            font-weight: 500;
        }

        .auth-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--blue-600, #2563eb);
            text-decoration: none;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .auth-submit {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--blue-600, #2563eb), var(--blue-700, #1d4ed8));
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(37, 99, 235, .18);
            transition: transform var(--dur-fast), box-shadow var(--dur-fast), filter var(--dur-fast);
        }

        .auth-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(37, 99, 235, .24);
            filter: brightness(1.02);
        }

        .auth-alert {
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }

        .auth-alert.success { background: var(--ok-bg); border-color: var(--ok-bd); color: var(--ok-tx); }
        .auth-alert.error   { background: var(--err-bg); border-color: var(--err-bd); color: var(--err-tx); }
        .auth-alert.warning { background: var(--warn-bg); border-color: var(--warn-bd); color: var(--warn-tx); }

        .auth-demo {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--surface-border, #e2e8f0);
        }

        .auth-demo-title {
            margin-bottom: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            text-align: center;
            color: var(--text-tertiary, #94a3b8);
        }

        .auth-demo-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 12px;
            margin-bottom: 8px;
            border-radius: 12px;
            background: var(--surface-2, #f8fafc);
            border: 1px solid var(--surface-border, #e2e8f0);
            font-size: 12.5px;
        }

        .auth-demo-role {
            font-weight: 700;
            color: var(--text-primary, #334155);
        }

        .auth-demo-cred {
            color: var(--text-secondary, #64748b);
            font-family: var(--font-mono-ui, 'JetBrains Mono', monospace);
        }
       
        .dark {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e293b;
            --border-color: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --input-bg: #0f172a;
            --input-border: #334155;
            --brand-blue: #2563eb;
            --brand-blue-hover: #1d4ed8;
            --brand-blue-light: #3b82f6;
            --feature-text: #93c5fd;
            --feature-subtext: #bfdbfe;
            --left-panel-bg: #1d4ed8;
            --left-panel-card-bg: rgba(37, 99, 235, 0.5);
            --left-panel-card-border: #3b82f6;
        }

        .dark body {
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, .16), transparent 34%),
                radial-gradient(circle at bottom right, rgba(29, 78, 216, .14), transparent 28%),
                linear-gradient(180deg, var(--bg-secondary), var(--bg-primary));
        }

        .dark .auth-left {
            background:
                radial-gradient(circle at top right, rgba(255,255,255,.12), transparent 30%),
                radial-gradient(circle at bottom left, rgba(255,255,255,.08), transparent 34%),
                linear-gradient(160deg, var(--brand-blue-hover) 0%, var(--left-panel-bg) 52%, var(--brand-blue) 100%);
            color: #ffffff;
        }

        .dark .auth-headline,
        .dark .auth-brand-name,
        .dark .auth-feature-icon {
            color: #ffffff;
        }

        .dark .auth-sub,
        .dark .auth-school-badge {
            color: var(--feature-subtext);
        }

        .dark .auth-feature {
            background: var(--left-panel-card-bg);
            border-color: var(--left-panel-card-border);
        }

        .dark .auth-feature-text {
            color: var(--feature-text);
        }

        .dark .auth-feature-desc {
            color: var(--feature-subtext);
        }

        .dark .auth-right {
            background: #111111;
        }

        .dark .auth-theme-toggle {
            background: rgba(30, 41, 59, .8);
            border-color: var(--border-color);
            color: var(--text-secondary);
            box-shadow: 0 10px 18px rgba(2, 6, 23, .34);
        }

        .dark .auth-theme-toggle:hover {
            background: var(--bg-card);
            color: var(--text-primary);
            border-color: #475569;
        }

        @media (max-width: 640px) {
            .auth-right {
                padding: 64px 14px 20px;
            }

            .auth-card {
                padding: 22px 18px;
                border-radius: 20px;
            }

            .auth-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .auth-demo-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        .dark .auth-card {
            background: var(--bg-card);
            border-color: var(--border-color);
            box-shadow: 0 24px 42px rgba(2, 6, 23, .45);
        }

        .dark .auth-mobile-name,
        .dark .auth-title,
        .dark .auth-label,
        .dark .auth-checkbox-label,
        .dark .auth-demo-role {
            color: var(--text-primary);
        }

        .dark .auth-subtitle {
            color: #cbd5e1;
        }

        .dark .auth-input {
            color: #ffffff;
            background: var(--input-bg);
            border-color: var(--input-border);
        }

        .dark .auth-input::placeholder {
            color: #64748b;
            opacity: 1;
        }

        .dark .auth-input-icon {
            color: #94a3b8;
        }

        .dark .auth-submit {
            background: linear-gradient(135deg, var(--brand-blue), var(--brand-blue-hover));
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, .22);
        }

        .dark .auth-submit:hover {
            box-shadow: 0 16px 28px rgba(37, 99, 235, .28);
        }

        .dark .auth-link {
            color: #93c5fd;
        }

        .dark .auth-demo {
            border-top-color: var(--border-color);
        }

        .dark .auth-demo-title,
        .dark .auth-demo-cred {
            color: #cbd5e1;
        }

        .dark .auth-demo-row {
            background: #1e293b;
            border-color: var(--border-color);
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="auth-shell">
    <aside class="auth-left">
        <div class="auth-left-top">
            <div class="auth-brand">
                <div class="auth-brand-logo">
                    <img src="{{ asset('images/logo1.png') }}" alt="SIGA">
                </div>
                <span class="auth-brand-name">SIGA</span>
            </div>

            <h1 class="auth-headline">Gestao escolar com mais clareza, velocidade e confianca.</h1>
            <p class="auth-sub">Centralize lancamento de notas, relatorios e acompanhamento academico numa experiencia mais simples, responsiva e pronta para o dia a dia da escola.</p>
        </div>

        <div class="auth-features">
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-bolt"></i></div>
                <div>
                    <div class="auth-feature-text">Fluxos mais rapidos</div>
                    <div class="auth-feature-desc">Menos cliques, menos retrabalho e acesso rapido aos pontos essenciais.</div>
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="auth-feature-text">Tema escuro funcional</div>
                    <div class="auth-feature-desc">Contraste consistente e leitura confortavel em qualquer horario.</div>
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-mobile-screen"></i></div>
                <div>
                    <div class="auth-feature-text">Experiencia responsiva</div>
                    <div class="auth-feature-desc">Acesso mais fluido no computador, tablet e telefone.</div>
                </div>
            </div>
        </div>

        <div class="auth-school-wrap">
            <div class="auth-school-badge">
                <i class="fas fa-school"></i>
                IPIKK-NV - Instituto Politecnico Industrial
            </div>
        </div>
    </aside>

    <main class="auth-right">
        <button class="auth-theme-toggle" id="authThemeToggle" type="button" aria-label="Alternar tema">
            <i id="authThemeIcon" class="fas fa-moon"></i>
        </button>

        <div class="auth-card">
            <div class="auth-mobile-brand">
                <div class="auth-mobile-logo">
                    <img src="{{ asset('images/logo1.png') }}" alt="SIGA">
                </div>
                <span class="auth-mobile-name">SIGA</span>
            </div>

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
    </main>
</div>

@stack('scripts')

</body>
</html>

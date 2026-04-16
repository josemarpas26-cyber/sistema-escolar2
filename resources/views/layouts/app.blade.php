<!DOCTYPE html>
<html lang="pt" class="{{ (session('theme', 'light') === 'dark') ? 'dark' : '' }}">
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

    <title>@yield('title', config('app.name', 'SIGA')) — @yield('page-title', 'Dashboard')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/logo1.png') }}">

    <link rel="preload" as="style"
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
      onload="this.onload=null;this.rel='stylesheet'">

    {{-- JetBrains Mono apenas quando necessário --}}
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">

    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
    </noscript>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ═══════════════════════════════════════════════════════
           SIGA DESIGN TOKENS — v2.1
           REGRA: Nenhuma cor hardcoded fora deste bloco.
           Todo componente usa var(). O dark mode só troca tokens.
        ═══════════════════════════════════════════════════════ */
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;

            /* Palette fixa (não inverte) */
            --blue-400: #60a5fa;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;

            /* ── Superfícies ── */
            --bg:             #f1f5f9;
            --surface:        #ffffff;
            --surface-sunken: #f8fafc;
            --border:         #e2e8f0;
            --border-strong:  #cbd5e1;

            /* ── Texto ── */
            --tx-1: #0f172a;
            --tx-2: #334155;
            --tx-3: #64748b;
            --tx-4: #94a3b8;
            --tx-5: #cbd5e1;

            /* ── Interação ── */
            --hover-bg:  #f1f5f9;
            --active-bg: #e2e8f0;

            /* ── Semânticas ── */
            --ok-bg:   #f0fdf4; --ok-bd:   #bbf7d0; --ok-tx:   #15803d; --ok-ico:  #16a34a;
            --warn-bg: #fffbeb; --warn-bd: #fde68a; --warn-tx: #92400e; --warn-ico: #d97706;
            --err-bg:  #fef2f2; --err-bd:  #fecaca; --err-tx:  #b91c1c; --err-ico:  #dc2626;
            --info-bg: #eff6ff; --info-bd: #bfdbfe; --info-tx: #1d4ed8; --info-ico: #2563eb;

            /* ── Sidebar ── */
            --sb-bg:           #ffffff;
            --sb-border:       #e2e8f0;
            --nav-active-bg:   #eff6ff;
            --nav-active-tx:   #1d4ed8;
            --nav-active-bar:  #2563eb;
            --nav-hover-bg:    #f1f5f9;

            /* ── Inputs ── */
            --inp-bg:          #ffffff;
            --inp-border:      #e2e8f0;
            --inp-border-focus:#2563eb;
            --inp-tx:          #0f172a;
            --inp-placeholder: #94a3b8;
            --inp-disabled-bg: #f1f5f9;
            --inp-disabled-tx: #94a3b8;

            /* ── Tabelas ── */
            --tbl-head-bg:  #f8fafc;
            --tbl-head-tx:  #94a3b8;
            --tbl-hover-bg: #eff6ff;
            --tbl-border:   #f1f5f9;

            /* ── Badges ── */
            --badge-blue-bg:  #dbeafe; --badge-blue-tx:  #1d4ed8;
            --badge-green-bg: #dcfce7; --badge-green-tx: #15803d;
            --badge-red-bg:   #fee2e2; --badge-red-tx:   #b91c1c;
            --badge-amber-bg: #fef3c7; --badge-amber-tx: #92400e;
            --badge-gray-bg:  #f1f5f9; --badge-gray-tx:  #475569;
            --badge-teal-bg:  #cffafe; --badge-teal-tx:  #0e7490;

            /* ── Ícone botões ── */
            --ib-bg:          #f1f5f9;
            --ib-tx:          #64748b;
            --ib-view-bg:     #eff6ff; --ib-view-tx:     #2563eb;
            --ib-edit-bg:     #dbeafe; --ib-edit-tx:     #1d4ed8;
            --ib-delete-bg:   #fee2e2; --ib-delete-tx:   #dc2626;
            --ib-warn-bg:     #fef3c7; --ib-warn-tx:     #92400e;
            --ib-ok-bg:       #dcfce7; --ib-ok-tx:       #15803d;

            /* ── Dimensões ── */
            --sidebar-w:  256px;
            --topbar-h:   64px;
            --r-sm:  6px;
            --r-md:  10px;
            --r-lg:  14px;
            --r-xl:  20px;
            --r-max: 9999px;

            /* ── Sombras ── */
            --sh-xs: 0 1px 2px rgba(0,0,0,.04);
            --sh-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --sh-md: 0 4px 6px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.04);
            --sh-lg: 0 10px 15px rgba(0,0,0,.07);
            --sh-xl: 0 20px 30px rgba(0,0,0,.09);
        }

        /* ═══════════════════════════════════════════════════════
           DARK MODE — troca apenas os tokens.
           Qualquer elemento que usa var() adapta automaticamente.
        ═══════════════════════════════════════════════════════ */
        .dark {
            /* Superfícies */
            --bg:             #0d1117;
            --surface:        #161b22;
            --surface-sunken: #0d1117;
            --border:         #30363d;
            --border-strong:  #484f58;

            /* Texto — contraste WCAG AA mínimo 4.5:1 */
            --tx-1: #e6edf3;   /* branco suave — não puro para menos fadiga */
            --tx-2: #c9d1d9;
            --tx-3: #9ba5b0;
            --tx-4: #7d8896;
            --tx-5: #484f58;

            /* Interação */
            --hover-bg:  #21262d;
            --active-bg: #30363d;

            /* Semânticas dark — versões escuras das caixas */
            --ok-bg:   #0d2116; --ok-bd:   #1a4731; --ok-tx:   #56d364; --ok-ico:  #3fb950;
            --warn-bg: #1a1200; --warn-bd: #4d3800; --warn-tx: #e3b341; --warn-ico: #e3b341;
            --err-bg:  #2d0b0b; --err-bd:  #6b1c1c; --err-tx:  #f85149; --err-ico:  #f85149;
            --info-bg: #0c1d30; --info-bd: #1a3a5c; --info-tx: #79c0ff; --info-ico: #388bfd;

            /* Sidebar */
            --sb-bg:           #161b22;
            --sb-border:       #30363d;
            --nav-active-bg:   rgba(56,139,253,.15);
            --nav-active-tx:   #79c0ff;
            --nav-active-bar:  #388bfd;
            --nav-hover-bg:    #21262d;

            /* Inputs */
            --inp-bg:          #0d1117;
            --inp-border:      #30363d;
            --inp-border-focus:#388bfd;
            --inp-tx:          #e6edf3;
            --inp-placeholder: #6e7681;
            --inp-disabled-bg: #161b22;
            --inp-disabled-tx: #484f58;

            /* Tabelas */
            --tbl-head-bg:  #161b22;
            --tbl-head-tx:  #6e7681;
            --tbl-hover-bg: rgba(56,139,253,.07);
            --tbl-border:   #21262d;

            /* Badges — tons suaves no escuro */
            --badge-blue-bg:  rgba(56,139,253,.15); --badge-blue-tx:  #79c0ff;
            --badge-green-bg: rgba(63,185,80,.13);  --badge-green-tx: #56d364;
            --badge-red-bg:   rgba(248,81,73,.13);  --badge-red-tx:   #f85149;
            --badge-amber-bg: rgba(227,179,65,.13); --badge-amber-tx: #2a3140;
            --badge-gray-bg:  #21262d;              --badge-gray-tx:  #c8d1da;
            --badge-teal-bg:  rgba(0,178,212,.12);  --badge-teal-tx:  #39d353;

            /* Ícone botões */
            --ib-bg:          #21262d;
            --ib-tx:          #8b949e;
            --ib-view-bg:     rgba(56,139,253,.18); --ib-view-tx:     #79c0ff;
            --ib-edit-bg:     rgba(56,139,253,.18); --ib-edit-tx:     #79c0ff;
            --ib-delete-bg:   rgba(248,81,73,.18);  --ib-delete-tx:   #f85149;
            --ib-warn-bg:     rgba(227,179,65,.15); --ib-warn-tx:     #e3b341;
            --ib-ok-bg:       rgba(63,185,80,.15);  --ib-ok-tx:       #56d364;

            /* Sombras mais fortes no escuro */
            --sh-sm: 0 1px 4px rgba(0,0,0,.4);
            --sh-md: 0 4px 12px rgba(0,0,0,.5);
            --sh-lg: 0 10px 24px rgba(0,0,0,.5);
            --sh-xl: 0 20px 40px rgba(0,0,0,.6);
        }

        /* ═══════════════════════════════════════════════════════
           BASE
        ═══════════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; }
        html { color-scheme: light; }
        .dark { color-scheme: dark; }
        body {
            font-family: var(--font-sans);
            background: var(--bg);
            color: var(--tx-1);
            margin: 0;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.5;
        }
        a { text-decoration: none; color: inherit; }

        /* ═══════════════════════════════════════════════════════
           LAYOUT
        ═══════════════════════════════════════════════════════ */
        .layout { display: flex; min-height: 100vh; }

        /* ═══════════════════════════════════════════════════════
           SIDEBAR
        ═══════════════════════════════════════════════════════ */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            max-width: min(86vw, var(--sidebar-w));
            background: var(--sb-bg);
            border-right: 1px solid var(--sb-border);
            display: flex;
            flex-direction: column;
            z-index: 40;
            transform: translateX(-100%);
            transition: transform .25s cubic-bezier(.4,0,.2,1);
        }
        .sidebar.open { transform: translateX(0); }
        @media (min-width: 1024px) { .sidebar { transform: translateX(0); } }

        .sidebar-brand {
            height: var(--topbar-h);
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--sb-border);
            flex-shrink: 0;
        }
        .sidebar-brand-logo {
            width: 36px; height: 36px;
            border-radius: var(--r-md);
            overflow: hidden;
            flex-shrink: 0;
            background: var(--hover-bg);
        }
        .sidebar-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-brand-name {
            font-size: 17px; font-weight: 800;
            color: var(--blue-600);
            letter-spacing: -.4px;
        }
        .dark .sidebar-brand-name { color: var(--blue-400); }
        .sidebar-brand-tagline {
            font-size: 10px;
            color: var(--tx-4);
            font-weight: 500;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-close-btn {
            margin-left: auto;
            background: none; border: none;
            color: var(--tx-4); cursor: pointer;
            padding: 8px; border-radius: var(--r-sm);
            display: flex; align-items: center;
            transition: color .15s;
        }
        .sidebar-close-btn:hover { color: var(--tx-1); }
        @media (min-width: 1024px) { .sidebar-close-btn { display: none; } }

        .sidebar-nav {
            flex: 1; overflow-y: auto;
            padding: 16px 12px;
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section-label {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            color: var(--tx-4);
            padding: 16px 12px 6px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px;
            border-radius: var(--r-md);
            font-size: 13.5px; font-weight: 500;
            color: var(--tx-3);
            cursor: pointer;
            transition: background .12s, color .12s;
            position: relative;
            text-decoration: none;
        }
        .nav-item:hover { background: var(--nav-hover-bg); color: var(--tx-1); }
        .nav-item.active {
            background: var(--nav-active-bg);
            color: var(--nav-active-tx);
            font-weight: 600;
        }
        .nav-item.active::before {
            content: '';
            position: absolute; left: 0;
            top: 50%; transform: translateY(-50%);
            width: 3px; height: 60%;
            background: var(--nav-active-bar);
            border-radius: 0 3px 3px 0;
        }
        .nav-item-icon {
            width: 18px; height: 18px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 13px;
        }
        .nav-item-count {
            margin-left: auto;
            background: var(--err-bg);
            color: var(--err-tx);
            border: 1px solid var(--err-bd);
            font-size: 10px; font-weight: 700;
            padding: 1px 6px;
            border-radius: var(--r-max);
            min-width: 18px; text-align: center;
        }

        .nav-dropdown { overflow: hidden; }
        .nav-dropdown-trigger {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px;
            border-radius: var(--r-md);
            font-size: 13.5px; font-weight: 500;
            color: var(--tx-3);
            cursor: pointer;
            transition: background .12s, color .12s;
            width: 100%; background: none; border: none;
            text-align: left;
            font-family: var(--font-sans);
        }
        .nav-dropdown-trigger:hover { background: var(--nav-hover-bg); color: var(--tx-1); }
        .nav-dropdown-trigger.active-parent { color: var(--nav-active-tx); font-weight: 600; }
        .nav-dropdown-arrow { margin-left: auto; font-size: 10px; transition: transform .2s; }
        .nav-dropdown.open .nav-dropdown-arrow { transform: rotate(180deg); }
        .nav-dropdown-items { display: none; padding-left: 30px; padding-top: 2px; }
        .nav-dropdown.open .nav-dropdown-items { display: block; }

        .nav-sub-item {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 12px;
            border-radius: var(--r-sm);
            font-size: 13px; font-weight: 500;
            color: var(--tx-3);
            text-decoration: none;
            transition: background .12s, color .12s;
        }
        .nav-sub-item:hover { color: var(--nav-active-tx); background: var(--nav-active-bg); }
        .nav-sub-item.active { color: var(--nav-active-tx); font-weight: 600; }
        .nav-sub-dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--border-strong);
            flex-shrink: 0;
        }
        .nav-sub-item.active .nav-sub-dot { background: var(--blue-500); }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--sb-border);
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px;
            border-radius: var(--r-md);
            cursor: pointer; text-decoration: none;
            transition: background .12s;
        }
        .sidebar-user:hover { background: var(--nav-hover-bg); }
        .sidebar-user-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            object-fit: cover; flex-shrink: 0;
            border: 2px solid var(--border);
        }
        .sidebar-user-name {
            font-size: 13px; font-weight: 600;
            color: var(--tx-1);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 130px;
        }
        .sidebar-user-role { font-size: 11px; color: var(--tx-4); margin-top: 1px; }
        .sidebar-logout-btn {
            background: none; border: none;
            color: var(--tx-4);
            cursor: pointer; padding: 8px;
            border-radius: var(--r-sm);
            margin-left: auto; flex-shrink: 0;
            transition: color .15s;
        }
        .sidebar-logout-btn:hover { color: var(--err-ico); }

        /* ═══════════════════════════════════════════════════════
           TOPBAR
        ═══════════════════════════════════════════════════════ */
        .topbar {
            position: sticky; top: 0; z-index: 30;
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 16px;
            padding: 0 var(--page-gutter, 24px);
        }
        .topbar-menu-btn {
            background: none; border: none;
            color: var(--tx-3); cursor: pointer;
            padding: 8px; border-radius: var(--r-sm);
            display: flex; align-items: center;
            font-size: 18px; transition: color .15s;
        }
        .topbar-menu-btn:hover { color: var(--tx-1); }
        @media (min-width: 1024px) { .topbar-menu-btn { display: none; } }

        .topbar-title { flex: 1; min-width: 0; }
        .topbar-page-title {
            font-size: 20px; font-weight: 700;
            color: var(--tx-1);
            letter-spacing: -.4px; line-height: 1.2;
            word-break: break-word;
        }
        .topbar-breadcrumb {
            display: none; align-items: center; gap: 6px;
            font-size: 11.5px; color: var(--tx-4);
            margin-top: 1px;
        }
        @media (min-width: 640px) { .topbar-breadcrumb { display: flex; } }
        .topbar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        .theme-toggle {
            width: 36px; height: 36px;
            border-radius: var(--r-md);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--tx-3);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            transition: background .15s, color .15s, border-color .15s;
        }
        .theme-toggle:hover {
            background: var(--hover-bg);
            color: var(--tx-1);
            border-color: var(--border-strong);
        }

        /* ═══════════════════════════════════════════════════════
           MAIN
        ═══════════════════════════════════════════════════════ */
        .main-wrap {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            margin-left: 0;
        }
        @media (min-width: 1024px) { .main-wrap { margin-left: var(--sidebar-w); } }

        .main-content {
            flex: 1;
            padding: 24px var(--page-gutter, 24px) 32px;
            width: 100%;
            max-width: var(--content-max, 1440px);
            margin: 0 auto;
        }
                @media (min-width: 1024px) {
            .main-content {
                padding: 32px var(--page-gutter, 32px) 40px;
            }
        }
        @media (min-width: 640px) { .main-content { padding-top: 28px; } }

        .alerts-wrap {
            width: 100%;
            max-width: var(--content-max, 1440px);
            margin: 0 auto;
            padding: 20px var(--page-gutter, 24px) 0;
        }

        @media (max-width: 640px) {
            .topbar {
                gap: 12px;
                padding-inline: 14px;
            }
            .topbar-page-title {
                font-size: 18px;
            }
            .topbar-actions {
                gap: 8px;
            }
            .main-content {
                padding: 18px 14px 24px;
            }
            .alerts-wrap {
                padding: 14px 14px 0;
            }
        }

        /* ═══════════════════════════════════════════════════════
           ALERTS
        ═══════════════════════════════════════════════════════ */
        .siga-alert {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 18px;
            border-radius: var(--r-md);
            border: 1px solid transparent;
            margin-bottom: 16px;
            font-size: 13.5px; font-weight: 500;
            animation: sAlertIn .25s ease;
        }
        @keyframes sAlertIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .siga-alert-icon { flex-shrink: 0; font-size: 15px; margin-top: 1px; }
        .siga-alert-close {
            margin-left: auto; background: none; border: none;
            cursor: pointer; color: inherit; opacity: .5;
            padding: 0; display: flex; align-items: center;
            font-size: 13px; transition: opacity .15s;
        }
        .siga-alert-close:hover { opacity: 1; }

        .siga-alert.success { background: var(--ok-bg);   border-color: var(--ok-bd);   color: var(--ok-tx); }
        .siga-alert.error   { background: var(--err-bg);  border-color: var(--err-bd);  color: var(--err-tx); }
        .siga-alert.warning { background: var(--warn-bg); border-color: var(--warn-bd); color: var(--warn-tx); }
        .siga-alert.info    { background: var(--info-bg); border-color: var(--info-bd); color: var(--info-tx); }

        /* ═══════════════════════════════════════════════════════
           OVERLAY
        ═══════════════════════════════════════════════════════ */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 35;
            backdrop-filter: blur(2px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 250ms ease;
        }

        .sidebar-overlay.visible {
            opacity: 1;
            pointer-events: auto;
        }
        @media (min-width: 1024px) {
            .sidebar-overlay { display: none !important; }
        }
        /* ═══════════════════════════════════════════════════════
           BUTTONS
        ═══════════════════════════════════════════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 36px;
        padding: 0 16px;
        border-radius: var(--r-md);
        font-size: 13px;
        font-weight: 600;
        font-family: var(--font-sans);
        border: 1px solid transparent;
        cursor: pointer;
        transition: background .15s, border-color .15s, color .15s;
        white-space: nowrap;
        text-decoration: none;
        letter-spacing: .01em;
    }

    .btn:focus-visible {
        outline: 2.5px solid var(--blue-500);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }

    .dark .btn:focus-visible {
        outline-color: var(--blue-400);
        box-shadow: 0 0 0 4px rgba(56, 139, 253, 0.2);
    }

    .btn:disabled {
        opacity: .4;
        cursor: not-allowed;
        pointer-events: none;
    }

    .btn-primary {
        background: var(--blue-600);
        color: #fff;
        border-color: var(--blue-600);
    }

    .btn-primary:hover {
        background: var(--blue-700);
        border-color: var(--blue-700);
    }

    .btn-outline {
        background: var(--surface);
        color: var(--tx-3);
        border-color: var(--border);
    }

    .btn-outline:hover {
        background: var(--hover-bg);
        color: var(--tx-1);
        border-color: var(--border-strong);
    }

    .btn-success {
        background: var(--ok-ico);
        color: #fff;
        border-color: var(--ok-ico);
    }

    .btn-success:hover {
        filter: brightness(1.1);
    }

    .btn-danger {
        background: var(--err-bg);
        color: var(--err-tx);
        border-color: var(--err-bd);
    }

    .btn-danger:hover {
        background: var(--err-bd);
    }

    .btn-warning {
        background: var(--warn-bg);
        color: var(--warn-tx);
        border-color: var(--warn-bd);
    }

    .btn-warning:hover {
        background: var(--warn-bd);
    }

    .nav-item:focus-visible,
    .nav-dropdown-trigger:focus-visible,
    .sidebar-user:focus-visible {
        outline: 2px solid var(--blue-500);
        outline-offset: -2px;
        border-radius: var(--r-md);
    }
        /* ═══════════════════════════════════════════════════════
           COMPAT — views legadas com classes Tailwind hardcoded
        ═══════════════════════════════════════════════════════ */

        /* superfícies */
        .dark .bg-white      { background-color: var(--surface) !important; color: var(--tx-1) !important; }
        .dark .bg-gray-50    { background-color: var(--surface-sunken) !important; }
        .dark .bg-gray-100   { background-color: var(--hover-bg) !important; }
        .dark .bg-primary-50 { background-color: var(--info-bg) !important; }
        .dark .bg-blue-50    { background-color: var(--info-bg) !important; }
        .dark .bg-green-50   { background-color: var(--ok-bg) !important; }
        .dark .bg-red-50     { background-color: var(--err-bg) !important; }
        .dark .bg-yellow-50  { background-color: var(--warn-bg) !important; }
        .dark .bg-amber-50   { background-color: var(--warn-bg) !important; }

        /* bordas */
        .dark .border-gray-100,
        .dark .border-gray-200,
        .dark .border-gray-300 { border-color: var(--border) !important; }
        .dark .border-blue-200   { border-color: var(--info-bd) !important; }
        .dark .border-green-200  { border-color: var(--ok-bd) !important; }
        .dark .border-red-200    { border-color: var(--err-bd) !important; }
        .dark .border-yellow-200 { border-color: var(--warn-bd) !important; }

        /* divisores */
        .dark .divide-y > *, .dark .divide-gray-200 > * { border-color: var(--tbl-border) !important; }

        /* hover de linha de tabela */
        .dark .hover\:bg-gray-50:hover  { background-color: var(--tbl-hover-bg) !important; }
        .dark .hover\:bg-gray-100:hover { background-color: var(--hover-bg) !important; }

        /* texto */
        .dark .text-gray-900,
        .dark .text-gray-800     { color: var(--tx-1) !important; }
        .dark .text-gray-700,
        .dark .text-gray-600     { color: var(--tx-2) !important; }
        .dark .text-gray-500,
        .dark .text-gray-400     { color: var(--tx-3) !important; }
        .dark .text-gray-300     { color: var(--tx-4) !important; }

        .dark .text-blue-600,
        .dark .text-blue-800,
        .dark .text-blue-900,
        .dark .text-primary-600,
        .dark .text-primary-700  { color: var(--info-tx) !important; }

        .dark .text-green-600,
        .dark .text-green-700,
        .dark .text-green-800    { color: var(--ok-tx) !important; }

        .dark .text-red-600,
        .dark .text-red-700,
        .dark .text-red-800      { color: var(--err-tx) !important; }

        .dark .text-yellow-600,
        .dark .text-yellow-700,
        .dark .text-yellow-800   { color: var(--warn-tx) !important; }


        /* Inputs com classes Tailwind inline (create/edit forms) */
        .dark input[class*="border-gray"],
        .dark select[class*="border-gray"],
        .dark input[class*="pl-10"],
        .dark input[class*="px-4"] {
            background-color: var(--inp-bg) !important;
            border-color: var(--inp-border) !important;
            color: var(--inp-tx) !important;
        }
        .dark input[class*="border-gray"]::placeholder {
            color: var(--inp-placeholder) !important;
        }

        /* inputs nativos do Tailwind */
        .dark input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]),
        .dark select,
        .dark textarea {
            background-color: var(--inp-bg) !important;
            color: var(--inp-tx) !important;
            border-color: var(--inp-border) !important;
        }
        .dark input::placeholder,
        .dark textarea::placeholder { color: var(--inp-placeholder) !important; opacity: 1; }

        /* labels/spans no escuro */
        .dark label { color: var(--tx-2); }

        /* badge Tailwind inline */
        .dark .bg-green-100 { background-color: var(--badge-green-bg) !important; }
        .dark .text-green-800 { color: var(--badge-green-tx) !important; }
        .dark .bg-red-100   { background-color: var(--badge-red-bg) !important; }
        .dark .text-red-800  { color: var(--badge-red-tx) !important; }
        .dark .bg-blue-100  { background-color: var(--badge-blue-bg) !important; }
        .dark .text-blue-800 { color: var(--badge-blue-tx) !important; }
        .dark .bg-yellow-100 { background-color: var(--badge-amber-bg) !important; }
        .dark .text-yellow-800 { color: var(--badge-amber-tx) !important; }
        .dark .bg-gray-100  { background-color: var(--badge-gray-bg) !important; }
        .dark .text-gray-800 { color: var(--badge-gray-tx) !important; }
        .dark .bg-primary-100 { background-color: var(--badge-blue-bg) !important; }
        .dark .text-primary-800 { color: var(--badge-blue-tx) !important; }

        /* cabeçalhos de tabela */
        .dark thead tr,
        .dark .bg-gray-50 thead,
        .dark table thead tr { background-color: var(--tbl-head-bg) !important; }
        .dark thead th,
        .dark .text-xs.font-medium.text-gray-500 { color: var(--tbl-head-tx) !important; }

        /* cards genéricos */
        .dark .shadow-sm { box-shadow: var(--sh-sm); }

        /* bordas de separação */
        .dark .border-t,
        .dark .border-b { border-color: var(--border) !important; }

        /* forms inline */
        .dark .bg-primary-50.border.border-primary-200 {
            background-color: var(--info-bg) !important;
            border-color: var(--info-bd) !important;
        }
        .dark .text-primary-700,
        .dark .text-primary-900 { color: var(--info-tx) !important; }

        /* ═══ SCROLLBAR ═══ */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--tx-4); }

        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
    @stack('head-scripts')
</head>
<body>

<div class="layout">

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <img src="{{ asset('images/logo1.png') }}" alt="SIGA">
            </div>
            <div style="min-width:0">
                <div class="sidebar-brand-name">SIGA</div>
                <div class="sidebar-brand-tagline">Gestão Académica</div>
            </div>
            <button class="sidebar-close-btn" onclick="closeSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="sidebar-nav">

            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-house"></i></span>
                Dashboard
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
            <div class="nav-section-label">Gestão</div>

            <div class="nav-dropdown {{ request()->routeIs('users.*') && !request()->routeIs('users.lixeira') ? 'open' : '' }}"
                 id="navDropUsers">
                <button class="nav-dropdown-trigger {{ request()->routeIs('users.*') && !request()->routeIs('users.lixeira') ? 'active-parent' : '' }}"
                        onclick="toggleDropdown('navDropUsers')">
                    <span class="nav-item-icon"><i class="fas fa-users"></i></span>
                    Utilizadores
                    <i class="fas fa-chevron-down nav-dropdown-arrow"></i>
                </button>
                <div class="nav-dropdown-items">
                    <a href="{{ route('users.index') }}"
                       class="nav-sub-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <span class="nav-sub-dot"></span>Todos
                    </a>
                    <a href="{{ route('users.alunos') }}"
                       class="nav-sub-item {{ request()->routeIs('users.alunos') ? 'active' : '' }}">
                        <span class="nav-sub-dot"></span>Alunos
                    </a>
                    <a href="{{ route('users.professores') }}"
                       class="nav-sub-item {{ request()->routeIs('users.professores') ? 'active' : '' }}">
                        <span class="nav-sub-dot"></span>Professores
                    </a>
                </div>
            </div>

            <a href="{{ route('cursos.index') }}"
               class="nav-item {{ request()->routeIs('cursos.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-book"></i></span>Cursos
            </a>
            <a href="{{ route('disciplinas.index') }}"
               class="nav-item {{ request()->routeIs('disciplinas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-book-open"></i></span>Disciplinas
            </a>
            <a href="{{ route('turmas.index') }}"
               class="nav-item {{ request()->routeIs('turmas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-chalkboard"></i></span>Turmas
            </a>
            <a href="{{ route('anos-letivos.index') }}"
               class="nav-item {{ request()->routeIs('anos-letivos.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-calendar-alt"></i></span>Anos Letivos
            </a>
            @endif

            <div class="nav-section-label">Académico</div>

            @if(auth()->user()->isAluno())
            <a href="{{ route('relatorios.meu-historico') }}"
               class="nav-item {{ request()->routeIs('relatorios.meu-historico') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-history"></i></span>Meu histórico
            </a>
            <a href="{{ route('notas.avaliacoes-continuas.index') }}"
               class="nav-item {{ request()->routeIs('notas.avaliacoes-continuas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-list-check"></i></span>Avaliações contínuas
            </a>
            <a href="{{ route('notas.index') }}"
               class="nav-item {{ request()->routeIs('notas.*') && !request()->routeIs('notas.avaliacoes-continuas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-clipboard-list"></i></span>Notas
            </a>
            @else
            <a href="{{ route('notas.index') }}"
               class="nav-item {{ request()->routeIs('notas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-clipboard-list"></i></span>Notas
            </a>
            <a href="{{ route('relatorios.index') }}"
               class="nav-item {{ request()->routeIs('relatorios.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-file-alt"></i></span>Relatórios
            </a>
            <a href="{{ route('estatisticas.index') }}"
               class="nav-item {{ request()->routeIs('estatisticas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-chart-bar"></i></span>Estatísticas
            </a>


            @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
            <div class="nav-section-label">Sistema</div>

            <a href="{{ route('logs.index') }}"
               class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-history"></i></span>Logs
            </a>

            @if(auth()->user()->isAdmin())
            @php $deletedCount = \App\Models\User::onlyTrashed()->count(); @endphp
            <a href="{{ route('users.lixeira') }}"
               class="nav-item {{ request()->routeIs('users.lixeira') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-trash-alt"></i></span>
                Lixeira
                @if($deletedCount > 0)
                <span class="nav-item-count">{{ $deletedCount }}</span>
                @endif
                
                
                
            </a>
            @endif  {{-- fecha isAdmin --}}
            @endif  {{-- fecha isAdmin || isSecretaria --}}
            @endif  {{-- fecha @else de isAluno --}}
            
            @if(auth()->user()->isProfessor() || auth()->user()->isAluno())
            <a href="{{ route('calendario.index') }}"
               class="nav-item {{ request()->routeIs('calendario.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-calendar-day"></i></span>Calendário
            </a>
            @endif

        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('profile.show') }}" class="sidebar-user">
                <img src="{{ auth()->user()->foto_perfil_url }}"
                     alt="{{ auth()->user()->name }}"
                     class="sidebar-user-avatar">
                <div style="min-width:0">
                    <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                    <div class="sidebar-user-role">{{ auth()->user()->role->display_name }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
                    @csrf
                    <button type="submit" class="sidebar-logout-btn" title="Sair"
                            onclick="event.stopPropagation()">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </a>
        </div>

    </aside>

    <!-- ═══ MAIN ═══ -->
    <div class="main-wrap">

        <header class="topbar">
            <button class="topbar-menu-btn" onclick="openSidebar()" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="topbar-title">
                <div class="topbar-page-title">@yield('page-title', 'Dashboard')</div>
                <div class="topbar-breadcrumb">
                    <span>SIGA</span>
                    <i class="fas fa-chevron-right" style="font-size:8px;opacity:.4"></i>
                    <span>@yield('page-title', 'Dashboard')</span>
                </div>
            </div>

            <div class="topbar-actions">
                @yield('header-actions')
                <button class="theme-toggle" id="darkToggle" aria-label="Alternar tema">
                    <i id="themeIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="alerts-wrap">

            @if(session('success'))
            <div class="siga-alert success auto-dismiss" data-dismiss-after="5000">
                <i class="fas fa-circle-check siga-alert-icon"></i>
                <span>{{ session('success') }}</span>
                <button class="siga-alert-close" onclick="this.closest('.siga-alert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @if(session('error'))
            <div class="siga-alert error auto-dismiss" data-dismiss-after="6000">
                <i class="fas fa-circle-exclamation siga-alert-icon"></i>
                <span>{{ session('error') }}</span>
                <button class="siga-alert-close" onclick="this.closest('.siga-alert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @if(session('warning'))
            <div class="siga-alert warning auto-dismiss" data-dismiss-after="7000">
                <i class="fas fa-triangle-exclamation siga-alert-icon"></i>
                <span>{{ session('warning') }}</span>
                <button class="siga-alert-close" onclick="this.closest('.siga-alert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @if($errors->any())
            <div class="siga-alert error">
                <i class="fas fa-circle-exclamation siga-alert-icon"></i>
                <div style="flex:1">
                    <div style="font-weight:700;margin-bottom:4px">Corrija os seguintes erros:</div>
                    <ul style="margin:0;padding-left:16px;font-size:13px;line-height:1.7">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                <button class="siga-alert-close" onclick="this.closest('.siga-alert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

        </div>

        <main class="main-content">
            @yield('content')
        </main>

    </div>

</div>

<script>
/* ── Aplica tema antes do paint para evitar flash ── */
(function () {
    var t = localStorage.getItem('siga-theme');
    if (t === 'dark')  document.documentElement.classList.add('dark');
    if (t === 'light') document.documentElement.classList.remove('dark');
})();

function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('visible');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('visible');
    document.body.style.overflow = '';
}
function toggleDropdown(id) {
    document.getElementById(id).classList.toggle('open');
}

(function () {
    var btn  = document.getElementById('darkToggle');
    var icon = document.getElementById('themeIcon');
    function sync() {
        icon.className = document.documentElement.classList.contains('dark')
            ? 'fas fa-sun' : 'fas fa-moon';
    }
    sync();
    if (btn) btn.addEventListener('click', function () {
        var dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('siga-theme', dark ? 'dark' : 'light');
        sync();
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auto-dismiss').forEach(function (el) {
        var ms = parseInt(el.dataset.dismissAfter) || 5000;
        setTimeout(function () {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function () { el.remove(); }, 400);
        }, ms);
    });
});

/* Compat */
window.toggleSidebar  = openSidebar;
window.confirmDelete  = function (id, msg) {
    if (confirm(msg || 'Tem a certeza?')) document.getElementById(id).submit();
};
window.formatNota = function (inp) {
    var v = parseFloat(inp.value.replace(',', '.'));
    inp.value = isNaN(v)||v<0 ? '' : v>20 ? '20.00' : v.toFixed(2);
};
window.previewImage = function (inp) {
    if (inp.files && inp.files[0]) {
        var r = new FileReader();
        r.onload = function (e) { var img = document.getElementById('preview-image'); if(img) img.src=e.target.result; };
        r.readAsDataURL(inp.files[0]);
    }
};
</script>

@stack('scripts')

</body>
</html>

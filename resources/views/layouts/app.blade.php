<!DOCTYPE html>
<html lang="pt" class="{{ (session('theme', 'light') === 'dark') ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'SIGA')) — @yield('page-title', 'Dashboard')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/logo1.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ─────────────────────────────────────────
           SIGA DESIGN SYSTEM — v2.0
        ───────────────────────────────────────── */
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;

            /* Core palette */
            --blue-50:  #eff6ff;
            --blue-100: #dbeafe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-900: #1e3a8a;

            /* Neutrals */
            --gray-0:   #ffffff;
            --gray-50:  #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;

            /* Semantic */
            --success:   #16a34a;
            --warning:   #d97706;
            --danger:    #dc2626;
            --info:      #0891b2;

            /* Surfaces */
            --surface-bg:      #f8fafc;
            --surface-card:    #ffffff;
            --surface-sidebar: #ffffff;
            --surface-border:  #e2e8f0;

            /* Typography */
            --text-primary:   #0f172a;
            --text-secondary: #475569;
            --text-tertiary:  #94a3b8;
            --text-on-dark:   #ffffff;

            /* Spacing */
            --space-1:  4px;
            --space-2:  8px;
            --space-3:  12px;
            --space-4:  16px;
            --space-5:  20px;
            --space-6:  24px;
            --space-8:  32px;
            --space-10: 40px;
            --space-12: 48px;

            /* Borders */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 20px;
            --radius-full: 9999px;

            /* Shadows */
            --shadow-xs: 0 1px 2px 0 rgba(0,0,0,.04);
            --shadow-sm: 0 1px 3px 0 rgba(0,0,0,.07), 0 1px 2px -1px rgba(0,0,0,.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,.08), 0 2px 4px -2px rgba(0,0,0,.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.08), 0 4px 6px -4px rgba(0,0,0,.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,.08), 0 8px 10px -6px rgba(0,0,0,.04);

            /* Sidebar */
            --sidebar-w: 256px;
            --topbar-h:  64px;
        }

        /* ── DARK MODE ── */
        .dark {
            --surface-bg:      #0d1117;
            --surface-card:    #161b22;
            --surface-sidebar: #161b22;
            --surface-border:  #21262d;
            --text-primary:    #e6edf3;
            --text-secondary:  #8b949e;
            --text-tertiary:   #484f58;
            --gray-50:  #161b22;
            --gray-100: #21262d;
            --gray-200: #30363d;
        }

        /* ── RESET ── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background: var(--surface-bg);
            color: var(--text-primary);
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        /* ── LAYOUT ── */
        .layout { display: flex; min-height: 100vh; }

        /* ─────────────────────────────────────────
           SIDEBAR
        ───────────────────────────────────────── */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--surface-sidebar);
            border-right: 1px solid var(--surface-border);
            display: flex;
            flex-direction: column;
            z-index: 40;
            transform: translateX(-100%);
            transition: transform .25s cubic-bezier(.4,0,.2,1);
        }
        .sidebar.open, .sidebar.lg-open { transform: translateX(0); }
        @media (min-width: 1024px) {
            .sidebar { transform: translateX(0); }
        }

        /* Brand */
        .sidebar-brand {
            height: var(--topbar-h);
            padding: 0 var(--space-5);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            border-bottom: 1px solid var(--surface-border);
            flex-shrink: 0;
        }
        .sidebar-brand-logo {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            overflow: hidden;
            flex-shrink: 0;
        }
        .sidebar-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-brand-name {
            font-size: 17px;
            font-weight: 800;
            color: var(--blue-600);
            letter-spacing: -.4px;
        }
        .sidebar-brand-tagline {
            font-size: 10px;
            color: var(--text-tertiary);
            font-weight: 500;
            letter-spacing: .02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-close-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: var(--space-2);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
        }
        @media (min-width: 1024px) { .sidebar-close-btn { display: none; } }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: var(--space-4) var(--space-3);
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-tertiary);
            padding: var(--space-4) var(--space-3) var(--space-2);
            margin-top: var(--space-2);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: 10px var(--space-3);
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all .15s;
            position: relative;
            user-select: none;
        }
        .nav-item:hover {
            background: var(--gray-100);
            color: var(--text-primary);
        }
        .nav-item.active {
            background: var(--blue-50);
            color: var(--blue-700);
            font-weight: 600;
        }
        .dark .nav-item.active {
            background: rgba(59,130,246,.12);
            color: #60a5fa;
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--blue-600);
            border-radius: 0 3px 3px 0;
        }
        .nav-item-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }
        .nav-item-count {
            margin-left: auto;
            background: #fee2e2;
            color: #dc2626;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: var(--radius-full);
            min-width: 18px;
            text-align: center;
        }

        /* Dropdown nav */
        .nav-dropdown { overflow: hidden; }
        .nav-dropdown-trigger {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: 10px var(--space-3);
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all .15s;
            width: 100%;
            background: none;
            border: none;
            text-align: left;
        }
        .nav-dropdown-trigger:hover {
            background: var(--gray-100);
            color: var(--text-primary);
        }
        .nav-dropdown-trigger.active-parent {
            color: var(--blue-600);
            font-weight: 600;
        }
        .nav-dropdown-arrow {
            margin-left: auto;
            font-size: 10px;
            transition: transform .2s;
        }
        .nav-dropdown.open .nav-dropdown-arrow { transform: rotate(180deg); }
        .nav-dropdown-items {
            display: none;
            padding-left: 30px;
            padding-top: var(--space-1);
        }
        .nav-dropdown.open .nav-dropdown-items { display: block; }
        .nav-sub-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: 7px var(--space-3);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all .15s;
        }
        .nav-sub-item:hover { color: var(--blue-600); background: var(--blue-50); }
        .nav-sub-item.active { color: var(--blue-600); font-weight: 600; }
        .nav-sub-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--gray-300);
            flex-shrink: 0;
        }
        .nav-sub-item.active .nav-sub-dot { background: var(--blue-500); }

        /* User footer */
        .sidebar-footer {
            padding: var(--space-3);
            border-top: 1px solid var(--surface-border);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background .15s;
            text-decoration: none;
        }
        .sidebar-user:hover { background: var(--gray-100); }
        .sidebar-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid var(--surface-border);
        }
        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 130px;
        }
        .sidebar-user-role {
            font-size: 11px;
            color: var(--text-tertiary);
        }
        .sidebar-logout-btn {
            background: none;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: var(--space-2);
            border-radius: var(--radius-sm);
            margin-left: auto;
            transition: color .15s;
            flex-shrink: 0;
        }
        .sidebar-logout-btn:hover { color: #dc2626; }

        /* ─────────────────────────────────────────
           TOPBAR
        ───────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            height: var(--topbar-h);
            background: var(--surface-card);
            border-bottom: 1px solid var(--surface-border);
            display: flex;
            align-items: center;
            gap: var(--space-4);
            padding: 0 var(--space-6);
        }
        .topbar-menu-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: var(--space-2);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            font-size: 18px;
        }
        @media (min-width: 1024px) { .topbar-menu-btn { display: none; } }

        .topbar-title {
            flex: 1;
        }
        .topbar-page-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -.4px;
            line-height: 1.2;
        }
        .topbar-breadcrumb {
            display: none;
            align-items: center;
            gap: var(--space-2);
            font-size: 11.5px;
            color: var(--text-tertiary);
            margin-top: 1px;
        }
        @media (min-width: 640px) { .topbar-breadcrumb { display: flex; } }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        /* Theme toggle */
        .theme-toggle {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            border: 1px solid var(--surface-border);
            background: var(--surface-card);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all .15s;
        }
        .theme-toggle:hover {
            background: var(--gray-100);
            color: var(--text-primary);
        }

        /* ─────────────────────────────────────────
           MAIN CONTENT
        ───────────────────────────────────────── */
        .main-wrap {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            margin-left: 0;
            transition: margin-left .25s cubic-bezier(.4,0,.2,1);
        }
        @media (min-width: 1024px) {
            .main-wrap { margin-left: var(--sidebar-w); }
        }

        .main-content {
            flex: 1;
            padding: var(--space-6);
            max-width: 1400px;
            width: 100%;
        }
        @media (min-width: 640px) {
            .main-content { padding: var(--space-6) var(--space-8); }
        }

        /* ─────────────────────────────────────────
           ALERTS
        ───────────────────────────────────────── */
        .siga-alert {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4) var(--space-5);
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            margin-bottom: var(--space-5);
            font-size: 14px;
            font-weight: 500;
            animation: slideDown .25s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .siga-alert-icon { flex-shrink: 0; font-size: 15px; margin-top: 1px; }
        .siga-alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            opacity: .6;
            padding: 0;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        .siga-alert-close:hover { opacity: 1; }
        .siga-alert.success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
        .siga-alert.error   { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .siga-alert.warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .siga-alert.info    { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }

        /* ─────────────────────────────────────────
           OVERLAY (mobile sidebar)
        ───────────────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            z-index: 35;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.visible { display: block; }
        @media (min-width: 1024px) { .sidebar-overlay { display: none !important; } }

        /* ─────────────────────────────────────────
           HEADER ACTION BUTTONS
        ───────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            height: 36px;
            padding: 0 var(--space-4);
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 600;
            font-family: var(--font-sans);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--blue-600);
            color: #fff;
            border-color: var(--blue-600);
        }
        .btn-primary:hover { background: var(--blue-700); border-color: var(--blue-700); }
        .btn-outline {
            background: var(--surface-card);
            color: var(--text-secondary);
            border-color: var(--surface-border);
        }
        .btn-outline:hover { background: var(--gray-100); color: var(--text-primary); }
        .btn-success {
            background: var(--success);
            color: #fff;
            border-color: var(--success);
        }
        .btn-success:hover { background: #15803d; }
        .btn-danger {
            background: #fef2f2;
            color: var(--danger);
            border-color: #fecaca;
        }
        .btn-danger:hover { background: #fee2e2; }

        /* ─────────────────────────────────────────
           UTILITY
        ───────────────────────────────────────── */
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
    @stack('head-scripts')
</head>
<body>

<div class="layout">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar">

        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <img src="{{ asset('images/logo1.png') }}" alt="SIGA">
            </div>
            <div style="min-width:0">
                <div class="sidebar-brand-name">SIGA</div>
                <div class="sidebar-brand-tagline">Sistema Integrado de Gestão Académica</div>
            </div>
            <button class="sidebar-close-btn" onclick="closeSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Nav -->
        <nav class="sidebar-nav">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-item-icon">
                    <i class="fas fa-house"></i>
                </span>
                Dashboard
            </a>

            <!-- GESTÃO -->
            @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
            <div class="nav-section-label">Gestão</div>

            <!-- Utilizadores (dropdown) -->
            <div class="nav-dropdown {{ request()->routeIs('users.*') ? 'open' : '' }}" id="navDropUsers">
                <button class="nav-dropdown-trigger {{ request()->routeIs('users.*') ? 'active-parent' : '' }}"
                        onclick="toggleDropdown('navDropUsers')">
                    <span class="nav-item-icon"><i class="fas fa-users"></i></span>
                    Utilizadores
                    <i class="fas fa-chevron-down nav-dropdown-arrow"></i>
                </button>
                <div class="nav-dropdown-items">
                    <a href="{{ route('users.index') }}"
                       class="nav-sub-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <span class="nav-sub-dot"></span>
                        Todos
                    </a>
                    <a href="{{ route('users.alunos') }}"
                       class="nav-sub-item {{ request()->routeIs('users.alunos') ? 'active' : '' }}">
                        <span class="nav-sub-dot"></span>
                        Alunos
                    </a>
                    <a href="{{ route('users.professores') }}"
                       class="nav-sub-item {{ request()->routeIs('users.professores') ? 'active' : '' }}">
                        <span class="nav-sub-dot"></span>
                        Professores
                    </a>
                </div>
            </div>

            <a href="{{ route('cursos.index') }}"
               class="nav-item {{ request()->routeIs('cursos.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-book"></i></span>
                Cursos
            </a>

            <a href="{{ route('disciplinas.index') }}"
               class="nav-item {{ request()->routeIs('disciplinas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-book-open"></i></span>
                Disciplinas
            </a>

            <a href="{{ route('turmas.index') }}"
               class="nav-item {{ request()->routeIs('turmas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-chalkboard"></i></span>
                Turmas
            </a>

            <a href="{{ route('anos-letivos.index') }}"
               class="nav-item {{ request()->routeIs('anos-letivos.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-calendar-alt"></i></span>
                Anos Letivos
            </a>
            @endif

            <!-- ACADÉMICO -->
            <div class="nav-section-label">Académico</div>

            <a href="{{ route('notas.index') }}"
               class="nav-item {{ request()->routeIs('notas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-clipboard-list"></i></span>
                Notas
            </a>

            <a href="{{ route('relatorios.index') }}"
               class="nav-item {{ request()->routeIs('relatorios.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-file-alt"></i></span>
                Relatórios
            </a>

            <a href="{{ route('estatisticas.index') }}"
               class="nav-item {{ request()->routeIs('estatisticas.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-chart-bar"></i></span>
                Estatísticas
            </a>

            <!-- SISTEMA -->
            @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
            <div class="nav-section-label">Sistema</div>

            <a href="{{ route('logs.index') }}"
               class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <span class="nav-item-icon"><i class="fas fa-history"></i></span>
                Logs
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
            @endif
            @endif

        </nav>

        <!-- User Footer -->
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
                    <button type="submit" class="sidebar-logout-btn" title="Sair">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </a>
        </div>

    </aside>

    <!-- ═══════════════════════════════════════
         MAIN
    ═══════════════════════════════════════ -->
    <div class="main-wrap">

        <!-- Topbar -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="openSidebar()" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="topbar-title">
                <div class="topbar-page-title">@yield('page-title', 'Dashboard')</div>
                <div class="topbar-breadcrumb">
                    <span>SIGA</span>
                    <i class="fas fa-chevron-right" style="font-size:8px;opacity:.5"></i>
                    <span>@yield('page-title', 'Dashboard')</span>
                </div>
            </div>

            <div class="topbar-actions">
                @yield('header-actions')

                <button class="theme-toggle" id="darkToggle" aria-label="Alternar tema">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </div>
        </header>

        <!-- Alerts -->
        <div style="padding: 0 var(--space-6); padding-top: var(--space-5);">

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
                <div>
                    <div style="font-weight:700;margin-bottom:4px">Corrija os seguintes erros:</div>
                    <ul style="margin:0;padding-left:16px;font-size:13px;line-height:1.6">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button class="siga-alert-close" onclick="this.closest('.siga-alert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

        </div>

        <!-- Page Content -->
        <main class="main-content">
            @yield('content')
        </main>

    </div>

</div>

<script>
/* ── Sidebar ── */
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

/* ── Nav dropdown ── */
function toggleDropdown(id) {
    const el = document.getElementById(id);
    el.classList.toggle('open');
}

/* ── Theme ── */
(function() {
    const html = document.documentElement;
    const saved = localStorage.getItem('siga-theme');
    if (saved === 'dark') html.classList.add('dark');

    const btn = document.getElementById('darkToggle');
    const icon = document.getElementById('themeIcon');

    function updateIcon() {
        const isDark = html.classList.contains('dark');
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }

    updateIcon();
    if (btn) {
        btn.addEventListener('click', function() {
            html.classList.toggle('dark');
            const isDark = html.classList.contains('dark');
            localStorage.setItem('siga-theme', isDark ? 'dark' : 'light');
            updateIcon();
        });
    }
})();

/* ── Auto-dismiss alerts ── */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.auto-dismiss').forEach(function(el) {
        const ms = parseInt(el.dataset.dismissAfter) || 5000;
        setTimeout(function() {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function() { el.remove(); }, 400);
        }, ms);
    });
});

/* ── Legacy helpers (backwards compat) ── */
window.toggleSidebar = openSidebar;
window.confirmDelete = function(formId, msg) {
    if (confirm(msg || 'Tem a certeza?')) {
        document.getElementById(formId).submit();
    }
};
window.formatNota = function(input) {
    let v = parseFloat(input.value.replace(',', '.'));
    if (isNaN(v) || v < 0) input.value = '';
    else if (v > 20) input.value = '20.00';
    else input.value = v.toFixed(2);
};
window.previewImage = function(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('preview-image');
            if (img) img.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
};
</script>

@stack('scripts')

</body>
</html>
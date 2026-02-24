<!DOCTYPE html>
<html lang="pt" class="{{ (session('theme', 'light') === 'dark') ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Sistema de Notas') - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
    @stack('head-scripts')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 dark:text-gray-100 font-sans antialiased">
    
    <div class="min-h-screen flex">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-30">
            <div class="flex flex-col h-full">
                
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-[#0000] rounded-lg flex items-center justify-center">
                            <img src="{{ asset('images/logo1.png') }}" alt="Logo da escola" class="w-15 h-15">
                        </div>
                        <span class="text-xl font-bold text-gray-900 dark:text-gray-100">SIGA</span>
                    </div>
                    <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                    
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700' : '' }}">
                        <i class="fas fa-home w-5"></i>
                        <span class="ml-3 font-medium">Dashboard</span>
                    </a>

                    @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                    <!-- Gestão -->
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Gestão</p>
                        
    
<div x-data="{ open: {{ request()->routeIs('users.*') ? 'true' : 'false' }} }">

    <div class="flex items-center mt-2">

        {{-- Link principal --}}
        <a href="{{ route('users.index') }}"
           class="flex-1 flex items-center px-4 py-3 rounded-l-lg hover:bg-gray-100
           {{ request()->routeIs('users.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-700' }}">
            
            <i class="fas fa-users w-5"></i>
            <span class="ml-3 font-medium">Usuários</span>
        </a>

        {{-- Botão da seta --}}
        <button @click="open = !open"
                class="px-3 py-3 rounded-r-lg hover:bg-gray-100
                {{ request()->routeIs('users.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-700' }}">
            
            <i class="fas fa-chevron-down text-xs transition-transform"
               :class="{ 'rotate-180': open }"></i>
        </button>

    </div>

    {{-- Submenu --}}
    <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1">
        
        <a href="{{ route('users.alunos') }}"
           class="block px-4 py-2 text-sm rounded hover:bg-gray-100
           {{ request()->routeIs('users.alunos') ? 'text-primary-700 font-semibold' : 'text-gray-600' }}">
            Alunos
        </a>

        <a href="{{ route('users.professores') }}"
           class="block px-4 py-2 text-sm rounded hover:bg-gray-100
           {{ request()->routeIs('users.professores') ? 'text-primary-700 font-semibold' : 'text-gray-600' }}">
            Professores
        </a>

    </div>

</div>




                        <a href="{{ route('cursos.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('cursos.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-book w-5"></i>
                            <span class="ml-3 font-medium">Cursos</span>
                        </a>

                        <a href="{{ route('disciplinas.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('disciplinas.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-book-open w-5"></i>
                            <span class="ml-3 font-medium">Disciplinas</span>
                        </a>

                        <a href="{{ route('turmas.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('turmas.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-chalkboard w-5"></i>
                            <span class="ml-3 font-medium">Turmas</span>
                        </a>

                        <a href="{{ route('anos-letivos.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('anos-letivos.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-calendar-alt w-5"></i>
                            <span class="ml-3 font-medium">Anos Letivos</span>
                        </a>
                    </div>
                    @endif

                    <!-- Notas -->
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Académico</p>
                        
                        <a href="{{ route('notas.index') }}" class="flex items-center px-4 py-3 mt-2 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('notas.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-clipboard-list w-5"></i>
                            <span class="ml-3 font-medium">Notas</span>
                        </a>

                        <a href="{{ route('relatorios.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('relatorios.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-file-alt w-5"></i>
                            <span class="ml-3 font-medium">Relatórios</span>
                        </a>
                    </div>

                    @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                    <!-- Sistema -->
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistema</p>
                        
                        <a href="{{ route('logs.index') }}" class="flex items-center px-4 py-3 mt-2 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('logs.*') ? 'bg-primary-50 text-primary-700' : '' }}">
                            <i class="fas fa-history w-5"></i>
                            <span class="ml-3 font-medium">Logs</span>
                        </a>
                    </div>
                    @endif

                </nav>

                <!-- User Profile -->
                <div class="p-4 border-t border-gray-200">
                    <a href="{{ route('profile.show') }}" class="flex items-center space-x-3 hover:bg-gray-50 rounded-lg p-2 -m-2 transition-colors">
                        <img src="{{ auth()->user()->foto_perfil_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->role->display_name }}</p>
                        </div>
                        <i class="fas fa-cog text-gray-400"></i>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Sair
                        </button>
                    </form>
                </div>

            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            
            <!-- Top Bar -->
            <header class="bg-white border-b bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 sticky top-0 z-20">
                <div class="flex items-center justify-between h-16 px-6">
                    
                    <!-- Mobile menu button -->
                    <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center space-x-4">
                        @yield('header-actions')
                        <button id="dark-toggle"
                        class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:scale-105 transition">
                        <i class="fas fa-moon"></i>
                    </button>

                    </div>

                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                
                <!-- Alerts -->
                @if(session('success'))
                 <div class="alert alert-success auto-dismiss mb-6" data-dismiss-after="60000">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if(session('error'))
                 <div class="alert alert-error auto-dismiss mb-6" data-dismiss-after="5000">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-error mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-semibold mb-2">Erros encontrados:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Content -->
                @yield('content')

            </main>

        </div>

    </div>
        <script>
            const toggle = document.getElementById('dark-toggle');

            if (toggle) {
                toggle.addEventListener('click', () => {
                    const html = document.documentElement;

                    if (html.classList.contains('dark')) {
                        html.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    } else {
                        html.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    }
                });
            }

            // Aplica preferência salva
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        </script>

    @stack('scripts')

</body>
</html>
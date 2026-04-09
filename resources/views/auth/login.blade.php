@extends('layouts.guest')

@section('title', 'Login')

@section('content')

<div class="auth-title">Bem-vindo de volta</div>
<p class="auth-subtitle">Entre com as suas credenciais para aceder ao sistema</p>

<form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- Email ou Nº Processo -->
    <div class="auth-field">
        <label class="auth-label" for="login">E-mail ou Número de Processo</label>
        <div class="auth-input-wrap">
            <i class="fas fa-user auth-input-icon"></i>
            <input
                type="text"
                id="login"
                name="login"
                value="{{ old('login') }}"
                class="auth-input {{ $errors->has('login') ? 'error' : '' }}"
                placeholder="utilizador@escola.ao ou 2024001"
                required
                autofocus
                autocomplete="username"
            >
        </div>
        @error('login')
        <p class="auth-error"><i class="fas fa-circle-exclamation" style="font-size:11px"></i> {{ $message }}</p>
        @enderror
    </div>

    <!-- Senha -->
    <div class="auth-field">
        <label class="auth-label" for="password">Senha</label>
         <div class="auth-input-wrap" x-data="{ mostrar: false }">
            <i class="fas fa-lock auth-input-icon"></i>
            <input
                :type="mostrar ? 'text' : 'password'"
                id="password"
                name="password"
                class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            >
            
            <button
                type="button"
                x-on:click="mostrar = !mostrar"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors duration-150 focus:outline-none"
                :aria-label="mostrar ? 'Ocultar senha' : 'Mostrar senha'"
                tabindex="-1"
            >
                <svg x-show="mostrar" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>

                <svg x-show="!mostrar" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
            </button>
        </div>
        @error('password')
        <p class="auth-error"><i class="fas fa-circle-exclamation" style="font-size:11px"></i> {{ $message }}</p>
        @enderror
    </div>

    <!-- Remember + Forgot -->
    <div class="auth-row">
        <label class="auth-checkbox-wrap">
            <input type="checkbox" name="remember" class="auth-checkbox">
            <span class="auth-checkbox-label">Lembrar-me</span>
        </label>
        <a href="{{ route('password.request') }}" class="auth-link">Esqueceu-se da palavra-passe?</a>
    </div>

    <button type="submit" class="auth-submit">
        <i class="fas fa-arrow-right-to-bracket"></i>
        Entrar no sistema
    </button>

</form>

<!-- Demo credentials -->
<div class="auth-demo">
    <div class="auth-demo-title">Credenciais de demonstração</div>
    <div class="auth-demo-row">
        <span class="auth-demo-role">Administrador</span>
        <span class="auth-demo-cred">admin@escola.ao / password</span>
    </div>
    <div class="auth-demo-row">
        <span class="auth-demo-role">Aluno (Nº Processo)</span>
        <span class="auth-demo-cred">2024001 / password</span>
    </div>
    <div class="auth-demo-row">
        <span class="auth-demo-role">Aluno (email)</span>
        <span class="auth-demo-cred">aluno1@escola.ao / password</span>
    </div>
</div>

@endsection
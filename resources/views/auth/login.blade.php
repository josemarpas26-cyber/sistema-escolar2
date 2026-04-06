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
        <div class="auth-input-wrap">
            <i class="fas fa-lock auth-input-icon"></i>
            <input
                type="password"
                id="password"
                name="password"
                class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            >
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
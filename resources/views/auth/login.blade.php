@extends('layouts.guest')

@section('title', 'Login')

@section('content')

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Bem-vindo de volta!</h2>
    <p class="text-gray-600">Entre com suas credenciais para acessar o sistema</p>
</div>

<form method="POST" action="{{ route('login') }}" class="space-y-6">
    @csrf

    <!-- Email -->
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
            Email
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400"></i>
            </div>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}"
                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('email') border-red-500 @enderror"
                placeholder="seu@email.com"
                required 
                autofocus
            >
        </div>
        @error('email')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
            Senha
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('password') border-red-500 @enderror"
                placeholder="••••••••"
                required
            >
        </div>
        @error('password')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Remember Me & Forgot Password -->
    <div class="flex items-center justify-between">
        <label class="flex items-center">
            <input 
                type="checkbox" 
                name="remember" 
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            >
            <span class="ml-2 text-sm text-gray-600">Lembrar-me</span>
        </label>

        <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
            Esqueceu a senha?
        </a>
    </div>

    <!-- Submit Button -->
    <button 
        type="submit" 
        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors flex items-center justify-center"
    >
        <i class="fas fa-sign-in-alt mr-2"></i>
        Entrar
    </button>

</form>

<!-- Demo Credentials -->
<div class="mt-8 pt-6 border-t border-gray-200">
    <p class="text-xs text-gray-500 text-center mb-3">Credenciais de demonstração:</p>
    <div class="space-y-2 text-xs">
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="font-semibold text-gray-700 mb-1">Admin</p>
            <p class="text-gray-600">admin@escola.ao / password</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="font-semibold text-gray-700 mb-1">Professor</p>
            <p class="text-gray-600">prof.matematica@escola.ao / password</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="font-semibold text-gray-700 mb-1">Aluno</p>
            <p class="text-gray-600">aluno1@escola.ao / password</p>
        </div>
    </div>
</div>

@endsection
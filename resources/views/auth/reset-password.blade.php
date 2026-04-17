@extends('layouts.guest')

@section('title', 'Redefinir Palavra-passe')

@section('content')

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Redefinir Palavra-passe</h2>
    <p class="text-gray-600 text-sm">Introduza a sua nova palavra-passe abaixo</p>
</div>

<form method="POST" action="{{ route('password.update') }}" class="space-y-6">
    @csrf

    <!-- Token -->
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

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
                value="{{ old('email', $request->email) }}"
                class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('email') border-red-500 @enderror"
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
            Nova Senha
        </label>
        <div class="relative" x-data="{ mostrar: false }">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input 
                :type="mostrar ? 'text' : 'password'" 
                id="password" 
                name="password" 
                class="block w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('password') border-red-500 @enderror"
                placeholder="••••••••"
                required
            >
            <button
                type="button"
                x-on:click="mostrar = !mostrar"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 transition-colors duration-150 focus:outline-none"
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
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password Confirmation -->
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
            Confirmar Nova Senha
        </label>
        <div class="relative" x-data="{ mostrar: false }">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input 
                :type="mostrar ? 'text' : 'password'"
                id="password_confirmation" 
                name="password_confirmation" 
                class="block w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                placeholder="••••••••"
                required
            >
            <button
                type="button"
                x-on:click="mostrar = !mostrar"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 transition-colors duration-150 focus:outline-none"
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
    </div>

    <!-- Submit Button -->
    <button 
        type="submit" 
        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors flex items-center justify-center"
    >
        <i class="fas fa-key mr-2"></i>
        Redefinir Palavra-passe
    </button>

</form>

@endsection
@extends('layouts.guest')

@section('title', 'Recuperar palavra-passe')

@section('content')

<div class="mb-6">
    <a href="{{ route('login') }}" 
       class="inline-flex items-center text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 mb-4">
        <i class="fas fa-arrow-left mr-2"></i>
        Voltar ao login
    </a>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        Esqueceu-se da palavra-passe?
    </h2>

    <p class="text-gray-600 dark:text-gray-400 text-sm">
        Sem problemas! Introduza o seu e-mail e enviaremos um link para redefinir a sua palavra-passe.
    </p>
</div>

<form method="POST" action="{{ route('password.email') }}" class="space-y-6">
    @csrf

    <!-- Email -->
    <div>
        <label for="email" 
               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Email
        </label>

        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400 dark:text-gray-500"></i>
            </div>

            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}"
                placeholder="utilizador@exemplo.com"
                required 
                autofocus
                class="block w-full pl-10 pr-3 py-3 rounded-lg border 
                       border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800
                       text-gray-900 dark:text-white
                       placeholder-gray-400 dark:placeholder-gray-500
                       focus:ring-2 focus:ring-primary-500 focus:border-primary-500
                       transition-colors
                       @error('email') border-red-500 @enderror"
            >
        </div>

        @error('email')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ $message }}
        </p>
        @enderror
    </div>

    <!-- Submit Button -->
    <button 
        type="submit" 
        class="w-full bg-primary-600 hover:bg-primary-700 
               dark:bg-primary-500 dark:hover:bg-primary-600
               text-white font-semibold py-3 px-4 rounded-lg
               focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900
               transition-colors flex items-center justify-center"
    >
        <i class="fas fa-paper-plane mr-2"></i>
        Enviar Link de Recuperação
    </button>

</form>

@endsection
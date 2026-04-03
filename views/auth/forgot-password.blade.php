@extends('layouts.guest')

@section('title', 'Recuperar Senha')

@section('content')

<div class="mb-6">
    <a href="{{ route('login') }}" class="inline-flex items-center text-primary-600 hover:text-primary-700 mb-4">
        <i class="fas fa-arrow-left mr-2"></i>
        Voltar ao login
    </a>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Esqueceu sua senha?</h2>
    <p class="text-gray-600 text-sm">Sem problemas! Digite seu email e enviaremos um link para redefinir sua senha.</p>
</div>

<form method="POST" action="{{ route('password.email') }}" class="space-y-6">
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

    <!-- Submit Button -->
    <button 
        type="submit" 
        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors flex items-center justify-center"
    >
        <i class="fas fa-paper-plane mr-2"></i>
        Enviar Link de Recuperação
    </button>

</form>

@endsection
@extends('layouts.app')

@section('page-title', 'Alterar Senha')

@section('content')
<div class="max-w-lg mx-auto mt-10 bg-white rounded-xl shadow p-8">
    <h2 class="text-xl font-bold text-gray-800 mb-1">Alterar Senha</h2>
    <p class="text-sm text-gray-500 mb-6">
        Apenas a senha pode ser alterada. Para atualizar outros dados, contacte o administrador.
    </p>

    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('perfil.senha.update') }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Senha Atual *</label>
            <input type="password" name="current_password"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('current_password') border-red-400 @enderror"
                   placeholder="Digite a sua senha atual">
            @error('current_password')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nova Senha *</label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-400 @enderror"
                   placeholder="Mínimo 8 caracteres">
            @error('password')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Nova Senha *</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Repita a nova senha">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
            Atualizar Senha
        </button>
    </form>
</div>
@endsection
@extends('layouts.app')

@section('page-title', 'Meu Perfil')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-card title="Dados Pessoais" icon="fas fa-user">
            @if($canEditProfile)
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-4">
                        <div>
                            <label class="label">Foto de Perfil</label>
                            <div class="flex items-center space-x-4">
                                <img
                                    id="preview-image"
                                    src="{{ $user->foto_perfil_url }}"
                                    alt="{{ $user->name }}"
                                    class="h-20 w-20 rounded-full border-2 border-gray-200 object-cover"
                                >
                                <div>
                                    <input
                                        type="file"
                                        name="foto_perfil"
                                        id="foto_perfil"
                                        class="hidden"
                                        accept="image/*"
                                        onchange="previewImage(this)"
                                    >
                                    <label for="foto_perfil" class="btn btn-outline cursor-pointer">
                                        <i class="fas fa-camera mr-2"></i>
                                        Alterar Foto
                                    </label>
                                    <p class="mt-2 text-xs text-gray-500">JPG ou PNG, maximo 2MB.</p>
                                </div>
                            </div>
                            @error('foto_perfil')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="label">Nome Completo *</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="label">Número de Processo</label>
                                <input type="text" value="{{ $user->numero_processo ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                                <p class="mt-1 text-xs text-gray-400">Editável apenas pela administração.</p>
                            </div>

                            <div>
                                <label class="label">BI</label>
                                <input type="text" value="{{ $user->bi ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                                <p class="mt-1 text-xs text-gray-400">Editável apenas pela administração.</p>
                            </div>

                            <div>
                                <label class="label">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $user->email) }}"
                                    class="input"
                                    placeholder="utilizador@escola.ao"
                                >
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="label">Telefone</label>
                                <input
                                    type="text"
                                    name="telefone"
                                    value="{{ old('telefone', $user->telefone) }}"
                                    class="input"
                                    maxlength="20"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label class="label">Endereco</label>
                                <input
                                    type="text"
                                    name="endereco"
                                    value="{{ old('endereco', $user->endereco) }}"
                                    class="input"
                                    placeholder="Rua, Bairro, Municipio"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Guardar Alteracoes
                        </button>
                    </div>
                </form>
            @else
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    Os seus dados principais são geridos internamente. Aqui pode consultar as informações e alterar apenas a palavra-passe.
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2 flex items-center space-x-4">
                        <img
                            src="{{ $user->foto_perfil_url }}"
                            alt="{{ $user->name }}"
                            class="h-20 w-20 rounded-full border-2 border-gray-200 object-cover"
                        >
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->role->display_name }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="label">Email</label>
                        <input type="text" value="{{ $user->email ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                    </div>

                    <div>
                        <label class="label">Telefone</label>
                        <input type="text" value="{{ $user->telefone ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                    </div>

                    <div>
                        <label class="label">Número de Processo</label>
                        <input type="text" value="{{ $user->numero_processo ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                    </div>

                    <div>
                        <label class="label">BI</label>
                        <input type="text" value="{{ $user->bi ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Endereco</label>
                        <input type="text" value="{{ $user->endereco ?? '-' }}" class="input cursor-not-allowed bg-gray-50 text-gray-500" disabled>
                    </div>
                </div>
            @endif
        </x-card>

        <x-card title="Alterar Senha" icon="fas fa-lock">
             <form method="POST" action="{{ route('profile.password') }}" x-data="{ mostrarAtual: false, mostrarNova: false, mostrarConfirmar: false }">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="label">Senha Atual *</label>
                        <div class="relative">
                            <input :type="mostrarAtual ? 'text' : 'password'" name="current_password" class="input pr-11" required>
                            <button type="button"
                                    x-on:click="mostrarAtual = !mostrarAtual"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors duration-150 focus:outline-none"
                                    :aria-label="mostrarAtual ? 'Ocultar senha' : 'Mostrar senha'"
                                    tabindex="-1">
                                <svg x-show="mostrarAtual" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="!mostrarAtual" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">Nova Senha *</label>
                             <div class="relative">
                                <input :type="mostrarNova ? 'text' : 'password'" name="password" class="input pr-11" required minlength="8">
                                <button type="button"
                                        x-on:click="mostrarNova = !mostrarNova"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors duration-150 focus:outline-none"
                                        :aria-label="mostrarNova ? 'Ocultar senha' : 'Mostrar senha'"
                                        tabindex="-1">
                                    <svg x-show="mostrarNova" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg x-show="!mostrarNova" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">Confirmar Nova Senha *</label>
                            <div class="relative">
                                <input :type="mostrarConfirmar ? 'text' : 'password'" name="password_confirmation" class="input pr-11" required>
                                <button type="button"
                                        x-on:click="mostrarConfirmar = !mostrarConfirmar"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors duration-150 focus:outline-none"
                                        :aria-label="mostrarConfirmar ? 'Ocultar senha' : 'Mostrar senha'"
                                        tabindex="-1">
                                    <svg x-show="mostrarConfirmar" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg x-show="!mostrarConfirmar" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-key mr-2"></i>
                        Alterar Senha
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="Minha Conta" icon="fas fa-id-card">
            <div class="mb-4 text-center">
                <img
                    src="{{ $user->foto_perfil_url }}"
                    alt="{{ $user->name }}"
                    class="mx-auto h-24 w-24 rounded-full border-4 border-primary-200 object-cover"
                >
                <p class="mt-3 font-bold text-gray-900">{{ $user->name }}</p>
                <x-badge type="primary" class="mt-1">{{ $user->role->display_name }}</x-badge>
            </div>

            <div class="space-y-3 border-t pt-4 text-sm">
                @if($user->numero_processo)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Número de Processo:</span>
                        <span class="font-semibold">{{ $user->numero_processo }}</span>
                    </div>
                @endif

                @if($user->bi)
                    <div class="flex justify-between">
                        <span class="text-gray-500">BI:</span>
                        <span class="font-semibold">{{ $user->bi }}</span>
                    </div>
                @endif

                @if($user->data_nascimento)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nascimento:</span>
                        <span class="font-semibold">{{ $user->data_nascimento->format('d/m/Y') }}</span>
                    </div>
                @endif

                <div class="flex justify-between">
                    <span class="text-gray-500">Status:</span>
                    <x-badge type="{{ $user->ativo ? 'success' : 'danger' }}">
                        {{ $user->ativo ? 'Ativo' : 'Inativo' }}
                    </x-badge>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Membro desde:</span>
                    <span class="font-semibold">{{ $user->created_at->format('d/m/Y') }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Login por:</span>
                    <span class="text-right text-xs font-semibold">
                        @if($user->email && $user->numero_processo)
                            E-mail ou Número de Processo
                        @elseif($user->email)
                            E-mail
                        @elseif($user->numero_processo)
                            Número de Processo
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>
        </x-card>

        @if($user->isAluno())
            <x-card title="Encarregado" icon="fas fa-user-friends">
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-500">Nome:</span>
                        <p class="font-semibold">{{ $user->nome_encarregado ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Contacto:</span>
                        <p class="font-semibold">{{ $user->contacto_encarregado ?? '-' }}</p>
                    </div>
                </div>
            </x-card>
        @endif

        @if($canEditProfile)
            <x-card title="Zona de Perigo" icon="fas fa-exclamation-triangle">
                <p class="mb-4 text-sm text-gray-600">
                    Ao eliminar a conta, os seus dados deixam de estar acessiveis no sistema.
                </p>

                <form
                    method="POST"
                    action="{{ route('profile.destroy') }}"
                    onsubmit="return confirm('Tem a certeza? Esta ação não pode ser desfeita!')"
                    x-data="{ mostrarEliminar: false }"
                >
                    @csrf
                    @method('DELETE')

                    <div class="relative mb-3">
                        <input
                            :type="mostrarEliminar ? 'text' : 'password'"
                            name="password"
                            placeholder="Confirme a sua palavra-passe"
                            class="input pr-11"
                            required
                        >
                        <button type="button"
                                x-on:click="mostrarEliminar = !mostrarEliminar"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors duration-150 focus:outline-none"
                                :aria-label="mostrarEliminar ? 'Ocultar senha' : 'Mostrar senha'"
                                tabindex="-1">
                            <svg x-show="mostrarEliminar" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg x-show="!mostrarEliminar" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>

                    <button type="submit" class="btn btn-danger w-full">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar Conta
                    </button>
                </form>
            </x-card>
        @endif
    </div>
</div>
@endsection

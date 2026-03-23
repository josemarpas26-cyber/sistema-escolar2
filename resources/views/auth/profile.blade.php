@extends('layouts.app')

@section('page-title', 'Meu Perfil')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Formulários principais ── --}}
    <div class="lg:col-span-2 space-y-6">

        @php
            $isRestrito = in_array(auth()->user()->role?->name, ['aluno', 'professor']);
        @endphp

        <div x-data="{ mostrarSenha: false }" class="space-y-6">
        @if($canEditProfile)       
        {{-- Dados Pessoais --}}
        <x-card title="Informações Pessoais" icon="fas fa-user">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="space-y-4">

                    {{-- Foto --}}
                    <div>
                        <label class="label">Foto de Perfil</label>
                        <div class="flex items-center space-x-4">
                            <img id="preview-image"
                                 src="{{ auth()->user()->foto_perfil_url }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                            <div>
                                <input type="file" name="foto_perfil" id="foto_perfil"
                                       class="hidden" accept="image/*"
                                       onchange="previewImage(this)">
                                <label for="foto_perfil" class="btn btn-outline cursor-pointer">
                                    <i class="fas fa-camera mr-2"></i>
                                    Alterar Foto
                                </label>
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG. Máx 2MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nome Completo</label>
                            <input type="text" name="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="input" required>
                            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="label">Email</label>
                            <input type="email" name="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="input" required>
                            @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="label">Telefone</label>
                            <input type="text" name="telefone"
                                   value="{{ old('telefone', $user->telefone) }}"
                                   class="input">
                        </div>

                        <div>
                            <label class="label">BI</label>
                            <input type="text" value="{{ $user->bi }}"
                                   class="input bg-gray-50" disabled
                                   title="Contacte a administração para alterar o BI">
                        </div>
                    </div>

                    <div>
                        <label class="label">Endereço</label>
                        <input type="text" name="endereco"
                               value="{{ old('endereco', $user->endereco) }}"
                               class="input">
                    </div>

                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </x-card>
        @else
        <x-card title="Informações Pessoais" icon="fas fa-user-lock">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                <p class="font-semibold">A edição do perfil foi desativada para o seu papel.</p>
                <p class="mt-1">Alunos e professores podem alterar apenas a própria senha. Para atualizar outros dados, contacte a administração.</p>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Nome Completo</label>
                    <input type="text" value="{{ $user->name }}" class="input bg-gray-50" disabled>
                </div>

                <div>
                    <label class="label">Email</label>
                    <input type="email" value="{{ $user->email }}" class="input bg-gray-50" disabled>
                </div>

                <div>
                    <label class="label">Telefone</label>
                    <input type="text" value="{{ $user->telefone }}" class="input bg-gray-50" disabled>
                </div>

                <div>
                    <label class="label">BI</label>
                    <input type="text" value="{{ $user->bi }}" class="input bg-gray-50" disabled>
                </div>

                <div class="md:col-span-2">
                    <label class="label">Endereço</label>
                    <input type="text" value="{{ $user->endereco }}" class="input bg-gray-50" disabled>
                </div>
            </div>
        </x-card>
        @endif

        @if($isRestrito)
        <div class="flex justify-end">
            <button
                type="button"
                x-on:click="
                    mostrarSenha = !mostrarSenha;
                    if (mostrarSenha) {
                        $nextTick(() => {
                            $refs.secaoSenha.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        });
                    }
                "
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-all duration-200"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 10V7a5 5 0 1 1 10 0v3"/>
                    <rect x="5" y="10" width="14" height="11" rx="2"/>
                    <path d="M12 15v3"/>
                </svg>
                <span x-text="mostrarSenha ? 'Ocultar alteração de senha' : 'Ir para alteração de senha'"></span>
                <svg class="w-3.5 h-3.5 transition-transform duration-300"
                     :class="mostrarSenha ? 'rotate-180' : 'rotate-0'"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2.5">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
        </div>
        @endif

        {{-- Alterar Senha --}}
                <div
            x-ref="secaoSenha"
            @if($isRestrito)
                x-show="mostrarSenha"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-3"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-3"
            @endif
        >
        <x-card title="Alterar Senha" icon="fas fa-lock">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="label">Senha Atual</label>
                        <input type="password" name="current_password" class="input" required>
                        @error('current_password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nova Senha</label>
                            <input type="password" name="password" class="input" required>
                            @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation" class="input" required>
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
        </div>
        
        {{--
            Zona de Perigo — apenas ADM e Secretária.
            Alunos e Professores NÃO podem deletar a própria conta.
        --}}
        @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
        <x-card title="Zona de Perigo" icon="fas fa-exclamation-triangle">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-red-700">
                    <i class="fas fa-warning mr-2"></i>
                    Ao deletar a sua conta, todos os dados associados serão removidos permanentemente.
                    Esta ação não pode ser desfeita.
                </p>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}"
                  onsubmit="return confirm('Tem a certeza? Esta ação é irreversível!')">
                @csrf
                @method('DELETE')

                <input type="password" name="password"
                       placeholder="Confirme a sua senha"
                       class="input mb-3" required>
                @error('password')<p class="text-red-600 text-sm mt-1 mb-2">{{ $message }}</p>@enderror

                <button type="submit" class="btn btn-danger w-full">
                    <i class="fas fa-trash mr-2"></i>
                    Deletar Conta
                </button>
            </form>
        </x-card>
        @endif

    </div>

    {{-- ── Sidebar ── --}}
    <div class="space-y-6">

        <x-card title="Informações da Conta" icon="fas fa-info-circle">
            <div class="space-y-3">

                <div>
                    <p class="text-sm text-gray-600 mb-1">Papel no Sistema</p>
                    <x-badge type="primary" class="text-sm">{{ $user->role->display_name }}</x-badge>
                </div>

                @if($user->numero_processo)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Número de Processo</p>
                    <p class="font-semibold text-gray-900">{{ $user->numero_processo }}</p>
                </div>
                @endif

                <div>
                    <p class="text-sm text-gray-600 mb-1">Data de Nascimento</p>
                    <p class="font-semibold text-gray-900">
                        {{ $user->data_nascimento ? $user->data_nascimento->format('d/m/Y') : 'Não informado' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">Membro desde</p>
                    <p class="font-semibold text-gray-900">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">Status</p>
                    <x-badge type="{{ $user->ativo ? 'success' : 'danger' }}">
                        {{ $user->ativo ? 'Ativo' : 'Inativo' }}
                    </x-badge>
                </div>

            </div>
        </x-card>

        {{-- Dados do encarregado — apenas alunos --}}
        @if($user->isAluno())
        <x-card title="Encarregado" icon="fas fa-user-friends">
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Nome</p>
                    <p class="font-semibold text-gray-900">{{ $user->nome_encarregado ?? 'Não informado' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Contacto</p>
                    <p class="font-semibold text-gray-900">{{ $user->contacto_encarregado ?? 'Não informado' }}</p>
                </div>
            </div>
        </x-card>
        @endif

    </div>

</div>

@endsection
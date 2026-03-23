@extends('layouts.app')

@section('page-title', 'Meu Perfil')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Formulários -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Dados Pessoais -->
        <x-card title="Dados Pessoais" icon="fas fa-user">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="space-y-4">

                    <!-- Foto -->
                    <div>
                        <label class="label">Foto de Perfil</label>
                        <div class="flex items-center space-x-4">
                            <img id="preview-image"
                                 src="{{ auth()->user()->foto_perfil_url }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                            <div>
                                <input type="file"
                                       name="foto_perfil"
                                       id="foto_perfil"
                                       class="hidden"
                                       accept="image/*"
                                       onchange="previewImage(this)">
                                <label for="foto_perfil" class="btn btn-outline cursor-pointer">
                                    <i class="fas fa-camera mr-2"></i>
                                    Alterar Foto
                                </label>
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG. Máx 2MB</p>
                            </div>
                        </div>
                        @error('foto_perfil')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Nome -->
                        <div class="md:col-span-2">
                            <label class="label">Nome Completo *</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="input"
                                   required>
                            @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Número de Processo -->
                        <div>
                            <label class="label">Número de Processo</label>
                            <input type="text"
                                   value="{{ $user->numero_processo ?? '—' }}"
                                   class="input bg-gray-50 text-gray-500 cursor-not-allowed"
                                   disabled
                                   title="Edite pelo painel de administração">
                            <p class="text-xs text-gray-400 mt-1">Apenas o administrador pode alterar</p>
                        </div>

                        <!-- BI -->
                        <div>
                            <label class="label">Bilhete de Identidade (BI)</label>
                            <input type="text"
                                   value="{{ $user->bi ?? '—' }}"
                                   class="input bg-gray-50 text-gray-500 cursor-not-allowed"
                                   disabled
                                   title="Edite pelo painel de administração">
                            <p class="text-xs text-gray-400 mt-1">Apenas o administrador pode alterar</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="label">Email</label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="input"
                                   placeholder="utilizador@escola.ao">
                            @error('email')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Telefone -->
                        <div>
                            <label class="label">Telefone</label>
                            <input type="text"
                                   name="telefone"
                                   value="{{ old('telefone', $user->telefone) }}"
                                   class="input"
                                   placeholder="923000000"
                                   maxlength="15">
                        </div>

                        <!-- Endereço -->
                        <div class="md:col-span-2">
                            <label class="label">Endereço</label>
                            <input type="text"
                                   name="endereco"
                                   value="{{ old('endereco', $user->endereco) }}"
                                   class="input"
                                   placeholder="Rua, Bairro, Município">
                        </div>

                    </div>

                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Guardar Alterações
                    </button>
                </div>
            </form>
        </x-card>

        <!-- Alterar Senha -->
        <x-card title="Alterar Senha" icon="fas fa-lock">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="label">Senha Atual *</label>
                        <input type="password" name="current_password" class="input" required>
                        @error('current_password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nova Senha *</label>
                            <input type="password" name="password" class="input" required minlength="8">
                            @error('password')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Confirmar Nova Senha *</label>
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

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Resumo da Conta -->
        <x-card title="Minha Conta" icon="fas fa-id-card">
            <div class="text-center mb-4">
                <img src="{{ auth()->user()->foto_perfil_url }}"
                     alt="{{ $user->name }}"
                     class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-primary-200">
                <p class="font-bold text-gray-900 mt-3">{{ $user->name }}</p>
                <x-badge type="primary" class="mt-1">{{ $user->role->display_name }}</x-badge>
            </div>

            <div class="space-y-3 text-sm border-t pt-4">

                @if($user->numero_processo)
                <div class="flex justify-between">
                    <span class="text-gray-500">Nº Processo:</span>
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
                    <span class="font-semibold text-xs text-right">
                        @if($user->email && $user->numero_processo)
                            Email ou Nº Processo
                        @elseif($user->email)
                            Email
                        @elseif($user->numero_processo)
                            Nº Processo
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        </x-card>

        <!-- Encarregado (alunos) -->
        @if($user->isAluno())
        <x-card title="Encarregado" icon="fas fa-user-friends">
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-500">Nome:</span>
                    <p class="font-semibold">{{ $user->nome_encarregado ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Contacto:</span>
                    <p class="font-semibold">{{ $user->contacto_encarregado ?? '—' }}</p>
                </div>
            </div>
        </x-card>
        @endif

        <!-- Zona de Perigo -->
        <x-card title="Zona de Perigo" icon="fas fa-exclamation-triangle">
            <p class="text-sm text-gray-600 mb-4">
                Ao eliminar a conta, todos os dados serão permanentemente removidos.
            </p>
            <form method="POST"
                  action="{{ route('profile.destroy') }}"
                  onsubmit="return confirm('Tem a certeza? Esta ação não pode ser desfeita!')">
                @csrf
                @method('DELETE')
                <input type="password"
                       name="password"
                       placeholder="Confirme a sua senha"
                       class="input mb-3"
                       required>
                <button type="submit" class="btn btn-danger w-full">
                    <i class="fas fa-trash mr-2"></i>
                    Eliminar Conta
                </button>
            </form>
        </x-card>

    </div>

</div>

@endsection
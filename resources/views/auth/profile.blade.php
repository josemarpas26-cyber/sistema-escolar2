@extends('layouts.app')

@section('page-title', 'Meu Perfil')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Informações do Perfil -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Dados Pessoais -->
        <x-card title="Informações Pessoais" icon="fas fa-user">
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
                                <br>
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG. Máx 2MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nome -->
                        <div>
                            <label class="label">Nome Completo</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                        </div>

                        <!-- Telefone -->
                        <div>
                            <label class="label">Telefone</label>
                            <input type="text" name="telefone" value="{{ old('telefone', $user->telefone) }}" class="input">
                        </div>

                        <!-- BI -->
                        <div>
                            <label class="label">BI</label>
                            <input type="text" value="{{ $user->bi }}" class="input bg-gray-50" disabled>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div>
                        <label class="label">Endereço</label>
                        <input type="text" name="endereco" value="{{ old('endereco', $user->endereco) }}" class="input">
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

        <!-- Alterar Senha -->
        <x-card title="Alterar Senha" icon="fas fa-lock">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    
                    <!-- Senha Atual -->
                    <div>
                        <label class="label">Senha Atual</label>
                        <input type="password" name="current_password" class="input" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nova Senha -->
                        <div>
                            <label class="label">Nova Senha</label>
                            <input type="password" name="password" class="input" required>
                        </div>

                        <!-- Confirmar Senha -->
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

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Info Card -->
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

        @if($user->isAluno())
        <!-- Info Aluno -->
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

        <!-- Deletar Conta -->
        <x-card title="Zona de Perigo" icon="fas fa-exclamation-triangle">
            <p class="text-sm text-gray-600 mb-4">
                Ao deletar sua conta, todos os seus dados serão permanentemente removidos.
            </p>
            
            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita!')">
                @csrf
                @method('DELETE')
                
                <input type="password" 
                       name="password" 
                       placeholder="Confirme sua senha" 
                       class="input mb-3" 
                       required>
                
                <button type="submit" class="btn btn-danger w-full">
                    <i class="fas fa-trash mr-2"></i>
                    Deletar Conta
                </button>
            </form>
        </x-card>

    </div>

</div>

@endsection
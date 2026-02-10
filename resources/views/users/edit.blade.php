@extends('layouts.app')

@section('page-title', 'Editar Usuário')

@section('content')

<form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário Principal -->
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Dados do Usuário" icon="fas fa-user">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div class="md:col-span-2">
                        <label class="label">Nome Completo *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                        @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label">Papel no Sistema *</label>
                        <select name="role_id" class="input" required>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">BI</label>
                        <input type="text" name="bi" value="{{ old('bi', $user->bi) }}" class="input">
                    </div>

                    <div>
                        <label class="label">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" value="{{ old('data_nascimento', $user->data_nascimento?->format('Y-m-d')) }}" class="input">
                    </div>

                    <div>
                        <label class="label">Gênero</label>
                        <select name="genero" class="input">
                            <option value="">Selecione...</option>
                            <option value="M" {{ $user->genero == 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ $user->genero == 'F' ? 'selected' : '' }}>Feminino</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone', $user->telefone) }}" class="input">
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Endereço</label>
                        <input type="text" name="endereco" value="{{ old('endereco', $user->endereco) }}" class="input">
                    </div>

                </div>
            </x-card>

            @if($user->isAluno())
            <x-card title="Dados do Aluno" icon="fas fa-graduation-cap">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Número de Processo</label>
                        <input type="text" name="numero_processo" value="{{ old('numero_processo', $user->numero_processo) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Nome do Encarregado</label>
                        <input type="text" name="nome_encarregado" value="{{ old('nome_encarregado', $user->nome_encarregado) }}" class="input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Contacto do Encarregado</label>
                        <input type="text" name="contacto_encarregado" value="{{ old('contacto_encarregado', $user->contacto_encarregado) }}" class="input">
                    </div>
                </div>
            </x-card>
            @endif

            <x-card title="Alterar Senha" icon="fas fa-key">
                <p class="text-sm text-gray-600 mb-4">Deixe em branco se não quiser alterar a senha</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Nova Senha</label>
                        <input type="password" name="password" class="input">
                    </div>
                    <div>
                        <label class="label">Confirmar Senha</label>
                        <input type="password" name="password_confirmation" class="input">
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <x-card title="Foto de Perfil" icon="fas fa-camera">
                <div class="text-center">
                    <img id="preview-image" src="{{ $user->foto_perfil_url }}" alt="Preview" 
                         class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200 mb-4">
                    <input type="file" name="foto_perfil" id="foto_perfil" class="hidden" accept="image/*" onchange="previewImage(this)">
                    <label for="foto_perfil" class="btn btn-outline cursor-pointer">
                        <i class="fas fa-upload mr-2"></i>
                        Alterar Foto
                    </label>
                </div>
            </x-card>

            <x-card title="Status" icon="fas fa-toggle-on">
                <label class="flex items-center">
                    <input type="checkbox" name="ativo" value="1" {{ $user->ativo ? 'checked' : '' }} class="h-4 w-4 text-primary-600 rounded">
                    <span class="ml-2 text-sm">Usuário ativo</span>
                </label>
            </x-card>

            <div class="flex flex-col space-y-3">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
                <a href="{{ route('users.show', $user) }}" class="btn btn-outline w-full">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>
        </div>

    </div>
</form>

@endsection

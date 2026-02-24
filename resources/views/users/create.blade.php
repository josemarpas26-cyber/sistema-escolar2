@extends('layouts.app')

@section('page-title', 'Novo Usuário')

@section('content')

<form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário Principal -->
        <div class="lg:col-span-2">
            <x-card title="Dados do Usuário" icon="fas fa-user">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Nome -->
                    <div class="md:col-span-2">
                        <label class="label">Nome Completo *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="input" required>
                        @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="label">Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="input" required>
                        @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Papel -->
                    <div>
                        <label class="label">Papel no Sistema *</label>
                        <select name="role_id" class="input" required>
                            <option value="">Selecione...</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('role_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Senha -->
                    <div>
                        <label class="label">Senha *</label>
                        <input type="password" name="password" class="input" required>
                        @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        
                        <label class="mt-2 inline-flex items-center text-sm text-gray-600">
                            <input type="checkbox" name="generate_random_password" value="1" class="mr-2 w-5 h-5 rounded-md border-gray-400 text-indigo-600 focus:ring-indigo-500" {{ old('generate_random_password') ? 'checked' : '' }} onchange="toggleAutoPassword(this)">
                            Gerar senha aleatória automaticamente
                        </label>
                    </div>

                    <!-- Confirmar Senha -->
                    <div>
                        <label class="label">Confirmar Senha *</label>
                        <input type="password" name="password_confirmation" class="input" required>
                    </div>

                    <!-- BI -->
                    <div>
                        <label class="label">Bilhete de Identidade</label>
                        <input type="text" name="bi" value="{{ old('bi') }}" class="input">
                        @error('bi')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Data Nascimento -->
                    <div>
                        <label class="label">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" value="{{ old('data_nascimento') }}" class="input">
                    </div>

                    <!-- Gênero -->
                    <div>
                        <label class="label">Gênero</label>
                        <select name="genero" class="input">
                            <option value="">Selecione...</option>
                            <option value="M" {{ old('genero') == 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ old('genero') == 'F' ? 'selected' : '' }}>Feminino</option>
                        </select>
                    </div>

                    <!-- Telefone -->
                    <div>
                        <label class="label">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone') }}" class="input">
                    </div>

                    <!-- Endereço -->
                    <div class="md:col-span-2">
                        <label class="label">Endereço</label>
                        <input type="text" name="endereco" value="{{ old('endereco') }}" class="input">
                    </div>

                </div>

            </x-card>

            <!-- Dados do Aluno (condicional) -->
            <x-card title="Dados do Aluno" icon="fas fa-graduation-cap" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Número de Processo -->
                    <div>
                        <label class="label">Número de Processo</label>
                        <input type="text" name="numero_processo" value="{{ old('numero_processo') }}" class="input">
                    </div>

                    <!-- Nome Encarregado -->
                    <div>
                        <label class="label">Nome do Encarregado</label>
                        <input type="text" name="nome_encarregado" value="{{ old('nome_encarregado') }}" class="input">
                    </div>

                    <!-- Contacto Encarregado -->
                    <div class="md:col-span-2">
                        <label class="label">Contacto do Encarregado</label>
                        <input type="text" name="contacto_encarregado" value="{{ old('contacto_encarregado') }}" class="input">
                    </div>

                </div>
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            
            <!-- Foto -->
            <x-card title="Foto de Perfil" icon="fas fa-camera">
                <div class="text-center">
                    <img id="preview-image" src="/storage/fotos_perfil/default.png" alt="Preview" 
                         class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200 mb-4">
                    
                    <input type="file" name="foto_perfil" id="foto_perfil" class="hidden" accept="image/*" onchange="previewImage(this)">
                    
                    <label for="foto_perfil" class="btn btn-outline cursor-pointer">
                        <i class="fas fa-upload mr-2"></i>
                        Selecionar Foto
                    </label>
                    
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG. Máx 2MB</p>
                </div>
                @error('foto_perfil')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                @enderror
            </x-card>

            <!-- Status -->
            <x-card title="Status" icon="fas fa-toggle-on">
                <label class="flex items-center">
                    <input type="checkbox" name="ativo" value="1" checked class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-600">Usuário ativo</span>
                </label>
            </x-card>

            <!-- Ações -->
            <div class="flex flex-col space-y-3">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Usuário
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-outline w-full">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
            </div>

        </div>

    </div>

</form>

<script>
function toggleAutoPassword(checkbox) {
    const passwordInput = document.querySelector('input[name="password"]');
    const confirmationInput = document.querySelector('input[name="password_confirmation"]');

    if (!passwordInput || !confirmationInput) return;

    if (checkbox.checked) {
        passwordInput.value = '';
        confirmationInput.value = '';
        passwordInput.required = false;
        confirmationInput.required = false;
    } else {
        passwordInput.required = true;
        confirmationInput.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.querySelector('input[name="generate_random_password"]');
    if (checkbox) {
        toggleAutoPassword(checkbox);
    }
});
</script>

@endsection

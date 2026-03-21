@extends('layouts.app')

@section('page-title', 'Novo Usuário')

@section('content')

<form method="POST" 
      action="{{ route('users.store') }}" 
      enctype="multipart/form-data"
      x-data="userForm()"
      @submit="handleSubmit">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário Principal -->
        <div class="lg:col-span-2 space-y-6">
            
            <x-card title="Dados do Usuário" icon="fas fa-user">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Nome -->
                    <div class="md:col-span-2">
                        <label class="label">Nome Completo *</label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name') }}" 
                               class="input" 
                               required
                               maxlength="255"
                               placeholder="Nome completo do utilizador">
                        @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="label">Email *</label>
                        <input type="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               class="input" 
                               required
                               placeholder="utilizador@escola.ao">
                        @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Papel -->
                    <div>
                        <label class="label">Papel no Sistema *</label>
                        <select name="role_id" 
                                class="input" 
                                required
                                x-model="roleId"
                                @change="checkIfAluno">
                            <option value="">Selecione...</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" 
                                    data-role-name="{{ $role->name }}"
                                    {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('role_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                     <div class="md:col-span-2" x-data="{ autoPass: {{ old('auto_password', true) ? 'true' : 'false' }} }">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Senha -->
                            <div>
                                <label class="label">Senha *</label>
                                <input type="password"
                                       name="password"
                                       x-ref="senha"
                                       :disabled="autoPass"
                                       :required="!autoPass"
                                       minlength="8"
                                       placeholder="Mínimo 8 caracteres"
                                       class="input transition-colors"
                                       :class="autoPass
                                        ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed select-none'
                                        : 'bg-white text-gray-900 border-gray-300 cursor-text'">
                                <p class="text-xs mt-1 transition-colors"
                                   :class="autoPass ? 'text-slate-300' : 'text-slate-500'">
                                    Mínimo 8 caracteres
                                </p>
                                @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror

                                <label class="mt-2 inline-flex items-center text-sm text-gray-600">
                                    <input type="checkbox"
                                           id="auto_password"
                                           name="auto_password"
                                           value="1"
                                           class="mr-2 w-5 h-5 rounded-md border-gray-400 text-indigo-600 focus:ring-indigo-500"
                                           x-model="autoPass"
                                           x-on:change="if (autoPass) { $refs.senha.value = ''; $refs.confirmar.value = ''; }"
                                           {{ old('auto_password', true) ? 'checked' : '' }}>
                                    Gerar senha aleatória automaticamente
                                </label>
                            </div>

                            <!-- Confirmar Senha -->
                            <div>
                                <label class="label">Confirmar Senha *</label>
                                <input type="password"
                                       name="password_confirmation"
                                       x-ref="confirmar"
                                       :disabled="autoPass"
                                       :required="!autoPass"
                                       minlength="8"
                                       placeholder="Confirme a senha"
                                       class="input transition-colors"
                                       :class="autoPass
                                        ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed select-none'
                                        : 'bg-white text-gray-900 border-gray-300 cursor-text'">
                            </div>
                        </div>
                    </div>

                    <!-- Género -->
                    <div>
                        <label class="label">Género</label>
                        <select name="genero" class="input">
                            <option value="">Selecione...</option>
                            <option value="M" {{ old('genero') == 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ old('genero') == 'F' ? 'selected' : '' }}>Feminino</option>
                        </select>
                    </div>

                    <!-- Telefone -->
                    <div x-data="{ telError: false }">
                        <label class="label" for="telefone">Telefone</label>
                        <input type="tel"
                               name="telefone"
                               id="telefone"
                               inputmode="numeric"
                               pattern="[0-9\s\+\-]+"
                               maxlength="15"
                               placeholder="923000000"
                               value="{{ old('telefone') }}"
                               x-on:input="const sanitized = $event.target.value.replace(/[^0-9\s\+\-]/g, ''); telError = sanitized !== $event.target.value; $event.target.value = sanitized"
                               :class="telError ? 'border-red-400 bg-red-50' : ''"
                               class="input">
                        <p x-show="telError" x-cloak class="text-xs text-red-500 mt-1">
                            O telefone deve conter apenas números.
                        </p>
                        @error('telefone')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Endereço -->
                    <div class="md:col-span-2">
                        <label class="label">Endereço</label>
                        <input type="text" 
                               name="endereco" 
                               value="{{ old('endereco') }}" 
                               class="input"
                               placeholder="Rua, Bairro, Município"
                               maxlength="255">
                    </div>

                </div>

            </x-card>

            <!-- Dados do Aluno (condicional - só aparece se role_id for aluno) -->
            <div x-show="isAluno" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                
                <x-card title="Dados do Aluno" icon="fas fa-graduation-cap">
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded">
                        <p class="text-sm text-blue-700 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Estes campos são obrigatórios para alunos.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Número de Processo -->
                        <div>
                            <label class="label">
                                Número de Processo 
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="numero_processo" 
                                   value="{{ old('numero_processo') }}" 
                                   class="input"
                                   :required="isAluno"
                                   placeholder="2024001"
                                   maxlength="20">
                            <p class="text-xs text-gray-500 mt-1">Exemplo: 2024001, 2024002...</p>
                            @error('numero_processo')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nome Encarregado -->
                        <div>
                            <label class="label">
                                Nome do Encarregado
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="nome_encarregado" 
                                   value="{{ old('nome_encarregado') }}" 
                                   class="input"
                                   :required="isAluno"
                                   placeholder="Nome completo do encarregado"
                                   maxlength="255">
                            @error('nome_encarregado')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contacto Encarregado -->
                        <div class="md:col-span-2">
                            <label class="label">
                                Contacto do Encarregado
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="contacto_encarregado" 
                                   value="{{ old('contacto_encarregado') }}" 
                                   class="input"
                                   :required="isAluno"
                                   placeholder="923000000"
                                   maxlength="15">
                            @error('contacto_encarregado')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </x-card>

            </div>

        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            
            <!-- Foto de Perfil -->
            <x-card title="Foto de Perfil" icon="fas fa-camera">
                <div class="text-center">
                    
                    <!-- Preview da Foto -->
                    <div class="relative inline-block mb-4">
                        <img :src="photoPreview" 
                             alt="Preview" 
                             class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200 shadow-lg transition-opacity"
                             :class="{ 'opacity-50': photoLoading }">
                        
                        <!-- Loading overlay -->
                        <div x-show="photoLoading" 
                             x-cloak
                             class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i>
                        </div>

                        <!-- Botão remover (só aparece se tem foto custom) -->
                        <button type="button"
                                x-show="hasCustomPhoto"
                                x-cloak
                                @click="removePhoto"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg hover:scale-110 transform"
                                title="Remover foto">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Input file escondido -->
                    <input type="file" 
                           name="foto_perfil" 
                           id="foto_perfil" 
                           class="hidden" 
                           accept="image/jpeg,image/png,image/jpg"
                           @change="handlePhotoChange">
                    
                    <!-- Botão selecionar/alterar -->
                    <label for="foto_perfil" 
                           class="btn btn-outline cursor-pointer inline-block mb-2">
                        <i :class="hasCustomPhoto ? 'fas fa-edit' : 'fas fa-upload'" class="mr-2"></i>
                        <span x-text="hasCustomPhoto ? 'Alterar Foto' : 'Selecionar Foto'"></span>
                    </label>
                    
                    <p class="text-xs text-gray-500">
                        JPG, PNG. Máximo 2MB
                    </p>

                    <!-- Mensagem de erro customizada -->
                    <p x-show="photoError" 
                       x-cloak
                       x-text="photoError" 
                       class="text-red-600 text-sm mt-2 animate-pulse"></p>
                </div>

                @error('foto_perfil')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                @enderror
            </x-card>

            <!-- Status -->
            <x-card title="Status" icon="fas fa-toggle-on">
                <label class="flex items-center cursor-pointer group">
                    <input type="checkbox" 
                           name="ativo" 
                           value="1" 
                           checked 
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded cursor-pointer">
                    <span class="ml-2 text-sm text-gray-600 group-hover:text-gray-900 transition-colors">
                        Utilizador ativo
                    </span>
                </label>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Utilizadores inativos não conseguem fazer login no sistema.
                </p>
            </x-card>

            <!-- Ações -->
            <div class="flex flex-col space-y-3">
                <button type="submit" 
                        class="btn btn-primary w-full transition-all"
                        :disabled="photoLoading"
                        :class="{ 'opacity-50 cursor-not-allowed': photoLoading }">
                    <i class="fas fa-save mr-2"></i>
                    <span x-text="photoLoading ? 'Aguarde...' : 'Guardar Utilizador'"></span>
                </button>
                <a href="{{ route('users.index') }}" 
                   class="btn btn-outline w-full">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
            </div>

        </div>

    </div>

</form>



@endsection

@push('scripts')
<script>
function userForm() {
    return {
        // Estado do papel do utilizador
        roleId: '{{ old("role_id") }}',
        isAluno: false,

        // Estado da foto
        photoPreview: '/storage/fotos_perfil/default.png',
        photoFile: null,
        hasCustomPhoto: false,
        photoLoading: false,
        photoError: '',

        // Inicialização
        init() {
            // Verifica se o role já está selecionado (old data após erro de validação)
            if (this.roleId) {
                this.checkIfAluno();
            }
        },

        // Verifica se o papel selecionado é aluno
        checkIfAluno() {
            if (!this.roleId) {
                this.isAluno = false;
                return;
            }

            const select = document.querySelector('select[name="role_id"]');
            if (!select) return;

            const selectedOption = select.options[select.selectedIndex];
            const roleName = selectedOption.getAttribute('data-role-name');
            
            this.isAluno = roleName === 'aluno';

            // Se mudou para não-aluno, limpa os campos obrigatórios
            if (!this.isAluno) {
                const camposAluno = ['numero_processo', 'nome_encarregado', 'contacto_encarregado'];
                camposAluno.forEach(campo => {
                    const input = document.querySelector(`input[name="${campo}"]`);
                    if (input) {
                        input.removeAttribute('required');
                    }
                });
            }
        },

        // Manipula mudança de foto
        handlePhotoChange(event) {
            const file = event.target.files[0];
            
            if (!file) return;

            // Reset de erros
            this.photoError = '';

            // Validação de tipo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                this.photoError = 'Apenas ficheiros JPG e PNG são permitidos.';
                event.target.value = '';
                return;
            }

            // Validação de tamanho (2MB = 2 * 1024 * 1024 bytes)
            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                this.photoError = 'O ficheiro deve ter no máximo 2MB.';
                event.target.value = '';
                return;
            }

            // Carrega o preview
            this.photoLoading = true;
            this.photoFile = file;

            const reader = new FileReader();
            
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
                this.hasCustomPhoto = true;
                this.photoLoading = false;
            };

            reader.onerror = () => {
                this.photoError = 'Erro ao carregar a imagem. Tente novamente.';
                this.photoLoading = false;
                event.target.value = '';
            };

            reader.readAsDataURL(file);
        },

        // Remove a foto personalizada
        removePhoto() {
            this.photoPreview = '/storage/fotos_perfil/default.png';
            this.photoFile = null;
            this.hasCustomPhoto = false;
            this.photoError = '';
            
            // Limpa o input file
            const fileInput = document.getElementById('foto_perfil');
            if (fileInput) {
                fileInput.value = '';
            }
        },

        // Validação antes do submit
        handleSubmit(event) {
            // Se for aluno, garante que os campos obrigatórios estão preenchidos
            if (this.isAluno) {
                const numeroProcesso = document.querySelector('input[name="numero_processo"]');
                const nomeEncarregado = document.querySelector('input[name="nome_encarregado"]');
                const contactoEncarregado = document.querySelector('input[name="contacto_encarregado"]');

                if (!numeroProcesso?.value?.trim() || 
                    !nomeEncarregado?.value?.trim() || 
                    !contactoEncarregado?.value?.trim()) {
                    event.preventDefault();
                    this.photoError = '';
                    alert('Por favor, preencha todos os campos obrigatórios do aluno.');
                    
                    // Scroll para o card de dados do aluno
                    document.querySelector('[x-show="isAluno"]')?.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    
                    return false;
                }
            }

            // Se está a carregar foto, previne submit
            if (this.photoLoading) {
                event.preventDefault();
                alert('Aguarde o carregamento da foto.');
                return false;
            }

            return true;
        }
    };
}
</script>

<style>
[x-cloak] { 
    display: none !important; 
}

/* Animação suave para o botão de remover foto */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

[x-show="hasCustomPhoto"] {
    animation: fadeIn 0.2s ease-out;
}
</style>
@endpush
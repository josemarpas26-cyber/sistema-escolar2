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

            <!-- Dados Pessoais -->
            <x-card title="Dados Pessoais" icon="fas fa-user">
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

                    <!-- Número de Processo -->
                    <div>
                        <label class="label">
                            Número de Processo
                            <span x-show="isAluno" class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="numero_processo"
                               value="{{ old('numero_processo') }}"
                               class="input"
                               :required="isAluno"
                               placeholder="Ex: 2024001"
                               maxlength="20">
                        <p class="text-xs text-gray-500 mt-1">
                            Usado para login quando não há e-mail
                        </p>
                        @error('numero_processo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bilhete de Identidade -->
                    <div>
                        <label class="label">Bilhete de Identidade (BI)</label>
                        <input type="text"
                               name="bi"
                               value="{{ old('bi') }}"
                               class="input"
                               placeholder="Ex: 006000001LA041"
                               maxlength="14">
                        @error('bi')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Data de Nascimento -->
                    <div>
                        <label class="label">Data de Nascimento</label>
                        <input type="date"
                               name="data_nascimento"
                               value="{{ old('data_nascimento') }}"
                               class="input"
                               max="{{ date('Y-m-d') }}">
                        @error('data_nascimento')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
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
                    <div>
                        <label class="label">Telefone</label>
                        <input type="text"
                               name="telefone"
                               value="{{ old('telefone') }}"
                               class="input"
                               placeholder="923000000"
                               maxlength="15">
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

            <!-- Dados de Acesso -->
            <x-card title="Dados de Acesso" icon="fas fa-key">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Email -->
                    <div class="md:col-span-2">
                        <label class="label">
                            Email
                            <span x-show="!isAluno" class="text-red-500">*</span>
                            <span x-show="isAluno" class="text-gray-400 text-xs font-normal">(opcional para alunos)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   class="input pl-10"
                                   :required="!isAluno"
                                   placeholder="utilizador@escola.ao">
                        </div>
                        <p x-show="isAluno" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se não tiver e-mail, o aluno fará login com o número de processo.
                        </p>
                        @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Papel -->
                    <div class="md:col-span-2">
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

                    <!-- Senha -->
                     <div x-data="{ autoPass: {{ old('auto_password', true) ? 'true' : 'false' }}, mostrarSenha: false, mostrarConfirmar: false }" class="md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Senha *</label>
                                <div class="relative">
                                    <input :type="mostrarSenha ? 'text' : 'password'"
                                           name="password"
                                           x-ref="senha"
                                           :disabled="autoPass"
                                           :required="!autoPass"
                                           minlength="8"
                                           placeholder="Mínimo 8 caracteres"
                                           class="input pr-11 transition-colors"
                                           :class="autoPass
                                               ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'
                                               : 'bg-white'">
                                    <button type="button"
                                            x-on:click="mostrarSenha = !mostrarSenha"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors duration-150 focus:outline-none"
                                            :aria-label="mostrarSenha ? 'Ocultar senha' : 'Mostrar senha'"
                                            tabindex="-1">
                                        <svg x-show="mostrarSenha" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg x-show="!mostrarSenha" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                </div>
                                <label class="mt-2 inline-flex items-center text-sm text-gray-600 cursor-pointer">
                                    <input type="checkbox"
                                           name="auto_password"
                                           value="1"
                                           class="mr-2 w-4 h-4 rounded border-gray-400 text-indigo-600"
                                           x-model="autoPass"
                                           @change="if (autoPass) { $refs.senha.value = ''; $refs.confirmar.value = ''; }"
                                           {{ old('auto_password', true) ? 'checked' : '' }}>
                                    Gerar palavra-passe aleatória automaticamente
                                </label>
                                <p x-show="isAluno" x-cloak class="text-xs text-blue-600 mt-2">
                                    Para alunos, ao gerar senha automaticamente será usado o número de processo.
                                </p>
                                @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="label">Confirmar Senha *</label>
                                 <div class="relative">
                                    <input :type="mostrarConfirmar ? 'text' : 'password'"
                                           name="password_confirmation"
                                           x-ref="confirmar"
                                           :disabled="autoPass"
                                           :required="!autoPass"
                                           minlength="8"
                                           placeholder="Confirme a palavra-passe"
                                           class="input pr-11 transition-colors"
                                           :class="autoPass
                                               ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'
                                               : 'bg-white'">
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

                </div>
            </x-card>

            <!-- Dados do Encarregado (só para alunos) -->
            <div x-show="isAluno"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <x-card title="Encarregado de Educação" icon="fas fa-user-friends">

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Dois alunos podem ter o mesmo encarregado — não há restrição de email duplicado.
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="label">Nome do Encarregado *</label>
                            <input type="text"
                                   name="nome_encarregado"
                                   value="{{ old('nome_encarregado') }}"
                                   class="input"
                                   :required="isAluno"
                                   placeholder="Nome completo"
                                   maxlength="255">
                            @error('nome_encarregado')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">Contacto do Encarregado *</label>
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
                    <div class="relative inline-block mb-4">
                        <img :src="photoPreview"
                             alt="Preview"
                             class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200 shadow-lg"
                             :class="{ 'opacity-50': photoLoading }">
                        <div x-show="photoLoading" x-cloak
                             class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i>
                        </div>
                        <button type="button"
                                x-show="hasCustomPhoto" x-cloak
                                @click="removePhoto"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-8 h-8
                                       flex items-center justify-center hover:bg-red-600 shadow-lg transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <input type="file"
                           name="foto_perfil"
                           id="foto_perfil"
                           class="hidden"
                           accept="image/jpeg,image/png,image/jpg"
                           @change="handlePhotoChange">

                    <label for="foto_perfil" class="btn btn-outline cursor-pointer inline-block mb-2">
                        <i :class="hasCustomPhoto ? 'fas fa-edit' : 'fas fa-upload'" class="mr-2"></i>
                        <span x-text="hasCustomPhoto ? 'Alterar Foto' : 'Selecionar Foto'"></span>
                    </label>

                    <p class="text-xs text-gray-500">JPG, PNG. Máximo 2MB</p>
                    <p x-show="photoError" x-cloak x-text="photoError"
                       class="text-red-600 text-sm mt-2"></p>
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
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-600 group-hover:text-gray-900 transition-colors">
                        Utilizador ativo
                    </span>
                </label>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Utilizadores inativos não conseguem fazer login.
                </p>
            </x-card>

            <!-- Resumo de acesso -->
            <x-card title="Como vai fazer login?" icon="fas fa-sign-in-alt">
                <div class="space-y-2 text-sm">
                     <div x-show="!isAluno" class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-blue-800 font-medium">
                            <i class="fas fa-envelope mr-1"></i> Por e-mail + palavra-passe
                        </p>
                        <p class="text-blue-600 text-xs mt-1">
                            Ou também pelo número de processo, se definido.
                        </p>
                    </div>
                    <div x-show="isAluno" class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <p class="text-green-800 font-medium">
                            <i class="fas fa-id-card mr-1"></i> Por nº processo + palavra-passe
                        </p>
                        <p class="text-green-600 text-xs mt-1">
                            Se tiver e-mail, poderá também usar o e-mail.
                        </p>
                    </div>
                </div>
            </x-card>

            <!-- Ações -->
            <div class="flex flex-col space-y-3">
                <button type="submit"
                        class="btn btn-primary w-full"
                        :disabled="photoLoading"
                        :class="{ 'opacity-50 cursor-not-allowed': photoLoading }">
                    <i class="fas fa-save mr-2"></i>
                    <span x-text="photoLoading ? 'Aguarde...' : 'Guardar Utilizador'"></span>
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-outline w-full">
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
        roleId: '{{ old("role_id") }}',
        isAluno: false,
        photoPreview: '/storage/fotos_perfil/default.png',
        photoFile: null,
        hasCustomPhoto: false,
        photoLoading: false,
        photoError: '',

        init() {
            if (this.roleId) {
                this.checkIfAluno();
            }
        },

        checkIfAluno() {
            if (!this.roleId) {
                this.isAluno = false;
                return;
            }
            const select = document.querySelector('select[name="role_id"]');
            if (!select) return;
            const opt = select.options[select.selectedIndex];
            this.isAluno = opt.getAttribute('data-role-name') === 'aluno';
        },

        handlePhotoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.photoError = '';

            const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowed.includes(file.type)) {
                this.photoError = 'Apenas ficheiros JPG e PNG são permitidos.';
                event.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.photoError = 'O ficheiro deve ter no máximo 2MB.';
                event.target.value = '';
                return;
            }

            this.photoLoading = true;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
                this.hasCustomPhoto = true;
                this.photoLoading = false;
            };
            reader.onerror = () => {
                this.photoError = 'Erro ao carregar a imagem.';
                this.photoLoading = false;
                event.target.value = '';
            };
            reader.readAsDataURL(file);
        },

        removePhoto() {
            this.photoPreview = '/storage/fotos_perfil/default.png';
            this.photoFile = null;
            this.hasCustomPhoto = false;
            this.photoError = '';
            const input = document.getElementById('foto_perfil');
            if (input) input.value = '';
        },

        handleSubmit(event) {
            if (this.isAluno) {
                const np  = document.querySelector('input[name="numero_processo"]');
                const enc = document.querySelector('input[name="nome_encarregado"]');
                const tel = document.querySelector('input[name="contacto_encarregado"]');
                if (!np?.value?.trim() || !enc?.value?.trim() || !tel?.value?.trim()) {
                    event.preventDefault();
                    alert('Preencha o número de processo e os dados do encarregado.');
                    return false;
                }
            }
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
<style>[x-cloak] { display: none !important; }</style>
@endpush

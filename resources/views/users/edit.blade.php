@extends('layouts.app')

@section('page-title', 'Editar Utilizador')

@section('content')

<form method="POST"
      action="{{ route('users.update', $user) }}"
      enctype="multipart/form-data"
      x-data="userEditForm()"
      @submit="handleSubmit">
    @csrf
    @method('PUT')

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
                               value="{{ old('name', $user->name) }}"
                               class="input"
                               required
                               maxlength="255">
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
                               value="{{ old('numero_processo', $user->numero_processo) }}"
                               class="input"
                               :required="isAluno"
                               placeholder="Ex: 2024001"
                               maxlength="20">
                        <p class="text-xs text-gray-500 mt-1">Usado para login quando não há e-mail</p>
                        @error('numero_processo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- BI -->
                    <div>
                        <label class="label">Bilhete de Identidade (BI)</label>
                        <input type="text"
                               name="bi"
                               value="{{ old('bi', $user->bi) }}"
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
                               value="{{ old('data_nascimento', $user->data_nascimento?->format('Y-m-d')) }}"
                               class="input"
                               max="{{ date('Y-m-d') }}">
                    </div>

                    <!-- Género -->
                    <div>
                        <label class="label">Género</label>
                        <select name="genero" class="input">
                            <option value="">Selecione...</option>
                            <option value="M" {{ old('genero', $user->genero) === 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ old('genero', $user->genero) === 'F' ? 'selected' : '' }}>Feminino</option>
                        </select>
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
                                   value="{{ old('email', $user->email) }}"
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
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                    data-role-name="{{ $role->name }}"
                                    {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('role_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </x-card>

            <!-- Alterar Senha -->
            <x-card title="Alterar Senha" icon="fas fa-lock">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 rounded text-sm text-yellow-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Deixe em branco para manter a palavra-passe atual.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Nova Senha</label>
                        <input type="password"
                               name="password"
                               class="input"
                               minlength="8"
                               placeholder="Mínimo 8 caracteres">
                        @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Confirmar Nova Palavra-passe</label>
                        <input type="password"
                               name="password_confirmation"
                               class="input"
                               minlength="8"
                               placeholder="Confirme a nova palavra-passe">
                    </div>
                </div>
            </x-card>

            <!-- Encarregado (alunos) -->
            <div x-show="isAluno"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <x-card title="Encarregado de Educação" icon="fas fa-user-friends">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nome do Encarregado *</label>
                            <input type="text"
                                   name="nome_encarregado"
                                   value="{{ old('nome_encarregado', $user->nome_encarregado) }}"
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
                                   value="{{ old('contacto_encarregado', $user->contacto_encarregado) }}"
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

            <!-- Foto -->
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
                        <i class="fas fa-edit mr-2"></i> Alterar Foto
                    </label>
                    <p class="text-xs text-gray-500">JPG, PNG. Máximo 2MB</p>
                    <p x-show="photoError" x-cloak x-text="photoError" class="text-red-600 text-sm mt-2"></p>
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
                           {{ old('ativo', $user->ativo) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-600 group-hover:text-gray-900 transition-colors">
                        Utilizador ativo
                    </span>
                </label>
            </x-card>

            <!-- Info -->
            <x-card title="Informações" icon="fas fa-info-circle">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Criado em:</span>
                        <span class="font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($user->updated_at && $user->updated_at != $user->created_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Atualizado em:</span>
                        <span class="font-medium">{{ $user->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Login por:</span>
                        <span class="font-medium text-xs text-right">
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

            <!-- Ações -->
            <div class="flex flex-col space-y-3">
                <button type="submit"
                        class="btn btn-primary w-full"
                        :disabled="photoLoading"
                        :class="{ 'opacity-50 cursor-not-allowed': photoLoading }">
                    <i class="fas fa-save mr-2"></i>
                    <span x-text="photoLoading ? 'Aguarde...' : 'Guardar Alterações'"></span>
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

@push('scripts')
<script>
function userEditForm() {
    const initial = {
        roleId:          '{{ old("role_id", $user->role_id) }}',
        isAlunoInitial:  {{ $user->isAluno() ? 'true' : 'false' }},
        photoUrl:        '{{ $user->foto_perfil_url }}',
        oldRoleId:       '{{ old("role_id") }}',
    };

    return {
        roleId:          initial.roleId,
        isAluno:         initial.isAlunoInitial,
        photoPreview:    initial.photoUrl,
        originalPhoto:   initial.photoUrl,
        hasCustomPhoto:  false,
        photoLoading:    false,
        photoError:      '',

        init() {
            if (initial.oldRoleId) {
                this.roleId = initial.oldRoleId;
                this.checkIfAluno();
            }
        },

        checkIfAluno() {
            if (!this.roleId) { this.isAluno = false; return; }
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
                this.photoError = 'Apenas JPG e PNG são permitidos.';
                event.target.value = ''; return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.photoError = 'Máximo 2MB.';
                event.target.value = ''; return;
            }
            this.photoLoading = true;
            const reader = new FileReader();
            reader.onload  = (e) => { this.photoPreview = e.target.result; this.hasCustomPhoto = true; this.photoLoading = false; };
            reader.onerror = ()  => { this.photoError = 'Erro ao carregar a imagem.'; this.photoLoading = false; event.target.value = ''; };
            reader.readAsDataURL(file);
        },

        removePhoto() {
            this.photoPreview = this.originalPhoto;
            this.hasCustomPhoto = false;
            this.photoError = '';
            const input = document.getElementById('foto_perfil');
            if (input) input.value = '';
        },

        handleSubmit(event) {
            if (this.photoLoading) { event.preventDefault(); alert('Aguarde o carregamento da foto.'); return false; }
            return true;
        }
    };
}
</script>
<style>[x-cloak] { display: none !important; }</style>
@endpush
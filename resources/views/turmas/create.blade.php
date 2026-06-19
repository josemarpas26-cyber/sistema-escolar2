@extends('layouts.app')

@section('page-title', 'Nova Turma')

@section('content')

<form method="POST" action="{{ route('turmas.store') }}"
      x-data="{
        nome: '{{ old('nome') }}',
        classeNome: '',
        classeNum: '{{ preg_replace('/[^0-9]/', '', old('classe', '')) }}',
        codigoCurso: '',
        areaFormacao: '',
        turno: '{{ old('turno', '') }}',
        capacidade: '{{ old('capacidade', 30) }}',
        sala: '{{ old('sala', '') }}',
        nomeCompleto: '',
        gerarNomeCompleto() {
          const classeNumLimpo = (this.classeNum ?? '').replace(/[^0-9]/g, '').trim();
          const nomeLimpo = (this.nome ?? '').toUpperCase().trim();
          const codigoLimpo = (this.codigoCurso ?? '').toUpperCase().trim();
          const turnoLimpo = (this.turno ?? '').toUpperCase().trim();

          if (codigoLimpo && classeNumLimpo && nomeLimpo && turnoLimpo) {
            this.nomeCompleto = codigoLimpo + classeNumLimpo + nomeLimpo + turnoLimpo;
          } else {
            this.nomeCompleto = '';
          }
        },
        atualizarCurso(el) {
          const opt = el.options[el.selectedIndex];
          this.codigoCurso = opt?.dataset?.codigo ?? '';
          this.areaFormacao = opt?.dataset?.area ?? '';
          this.gerarNomeCompleto();
        },
        atualizarClasse(el) {
          const opt = el.options[el.selectedIndex];
          this.classeNome = opt?.text ?? '';
          this.classeNum = opt?.dataset?.numero ?? '';
          this.gerarNomeCompleto();
        }
      }"
      x-init="
        atualizarCurso($el.querySelector('[name=\'curso_id\']'));
        atualizarClasse($el.querySelector('[name=\'classe\']'));
        gerarNomeCompleto();
      ">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário Principal -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Dados Básicos -->
            <x-card title="Dados da Turma" icon="fas fa-chalkboard">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Nome -->
                    <div>
                      <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                            Nome da Turma *
                        </label>
                        <input
                            type="text"
                            name="nome"
                            id="nome"
                            required
                            maxlength="1"
                            pattern="[A-Za-z]"
                            placeholder="Ex: A"
                            x-model="nome"
                            x-on:input="
                                this.value = this.value
                                    .replace(/[^A-Za-z]/g, '')
                                    .slice(0, 1)
                                    .toUpperCase();
                                nome = this.value;
                                gerarNomeCompleto();
                            "
                            x-on:keydown="
                                const permitidas = /^[A-Za-z]$/;
                                const teclasSistema = [
                                    'Backspace','Delete','Tab','ArrowLeft','ArrowRight'
                                ];
                                if (!permitidas.test($event.key) &&
                                    !teclasSistema.includes($event.key)) {
                                    $event.preventDefault();
                                }
                            "
                            x-on:paste="
                                $event.preventDefault();
                                const texto = ($event.clipboardData || window.clipboardData)
                                    .getData('text');
                                const letra = texto.replace(/[^A-Za-z]/g, '').slice(0,1).toUpperCase();
                                this.value = letra;
                                nome = letra;
                                gerarNomeCompleto();
                            "
                            value="{{ old('nome', strtoupper($turma->nome ?? '')) }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm uppercase tracking-widest text-center font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nome') border-red-400 bg-red-50 @enderror"
                        />
                        <p class="text-xs text-slate-400 mt-1">
                            Apenas uma letra de A a Z (ex: A, B, C...)
                        </p>
                        @error('nome')
                           <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Classe -->
                    <div>
                        <label class="label">Classe *</label>
                         <select name="classe" class="input" required x-on:change="atualizarClasse($el)">
                            <option value="">Selecione...</option>
                            <option value="10" data-numero="10" {{ old('classe') == '10' ? 'selected' : '' }}>10ª Classe</option>
                            <option value="11" data-numero="11" {{ old('classe') == '11' ? 'selected' : '' }}>11ª Classe</option>
                            <option value="12" data-numero="12" {{ old('classe') == '12' ? 'selected' : '' }}>12ª Classe</option>
                            <option value="13" data-numero="13" {{ old('classe') == '13' ? 'selected' : '' }}>13ª Classe</option>
                        </select>
                        @error('classe')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Curso -->
                    <div>
                        <label class="label">Curso *</label>
                            <select name="curso_id" class="input" required x-on:change="atualizarCurso($el)">
                            <option value="">Selecione...</option>
                            @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" data-codigo="{{ strtoupper($curso->codigo ?? '') }}" data-area="{{ $curso->areaFormacao?->nome ?? '' }}" {{ old('curso_id') == $curso->id ? 'selected' : '' }}>
                                {{ $curso->nome }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Area herdada do curso:
                            <span class="font-semibold text-slate-700" x-text="areaFormacao || 'Selecione um curso'"></span>
                        </p>
                        @error('curso_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Coordenador -->
                    <div>
                        <label class="label">Coordenador</label>
                        <select name="coordenador_turma_id" class="input">
                            <option value="">Sem coordenador</option>
                            @foreach($professores as $prof)
                             <option value="{{ $prof->id }}" {{ old('coordenador_turma_id') == $prof->id ? 'selected' : '' }}>
                                {{ $prof->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Capacidade -->
                    <div>
                        <label class="label">Capacidade (Alunos) *</label>
                               <input type="number" name="capacidade" value="{{ old('capacidade', 30) }}"
                               min="1" max="100" class="input" required x-model="capacidade">
                        @error('capacidade')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sala -->
                    <div>
                        <label class="label">Sala</label>
                        <input
                            type="text"
                            name="sala"
                            value="{{ old('sala') }}"
                            maxlength="20"
                            placeholder="Ex: 08, B-12"
                            x-model="sala"
                            class="input uppercase @error('sala') border-red-400 bg-red-50 @enderror">
                        <p class="text-xs text-slate-400 mt-1">
                            Opcional. Informe o número ou código da sala da turma.
                        </p>
                        @error('sala')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Turno -->
                    <div>
                        <label class="label">Turno *</label>
                        <select name="turno" id="turno" required class="input @error('turno') border-red-400 @enderror"
                                x-model="turno" x-on:change="gerarNomeCompleto()">
                            <option value="">Selecione o turno...</option>
                            <option value="M" {{ old('turno') == 'M' ? 'selected' : '' }}>Manhã (M)</option>
                            <option value="T" {{ old('turno') == 'T' ? 'selected' : '' }}>Tarde (T)</option>
                        </select>
                        @error('turno')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ano Letivo -->
                    <div>
                        <label class="label">Ano Letivo *</label>

                        <select name="ano_letivo_id" class="input" required>
                            <option value="">Selecione...</option>

                            @forelse($anosLetivos as $ano)
                                <option value="{{ $ano->id }}"
                                    {{ old('ano_letivo_id', $anoAtivo?->id) == $ano->id ? 'selected' : '' }}>
                                    {{ $ano->nome }} {{ $ano->ativo ? '(Ativo)' : '' }}
                                </option>
                            @empty
                                <option disabled>Nenhum ano letivo registrado</option>
                            @endforelse
                        </select>

                        @error('ano_letivo_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror

                        @if(!$anoAtivo)
                            <p class="text-yellow-600 text-sm mt-2">
                                ⚠ Nenhum ano letivo está marcado como ativo.
                            </p>
                        @endif
                    </div>
                </div>
            </x-card>

            <x-card title="Disciplinas da Turma" icon="fas fa-book-open">
                <p class="text-sm text-gray-600 mb-4">
                    Selecione as disciplinas que serão lecionadas nesta turma
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($disciplinas as $disciplina)
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                         <input type="checkbox" name="disciplinas[]" value="{{ $disciplina->id }}"
                               {{ in_array($disciplina->id, old('disciplinas', [])) ? 'checked' : '' }}
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">{{ $disciplina->nome }}</span>
                            <p class="text-xs text-gray-500">{{ $disciplina->codigo }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                @error('disciplinas')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                @enderror
            </x-card>

        </div>

        <div class="space-y-6">
            <x-card title="Preview" icon="fas fa-eye">
                <div class="space-y-3 text-sm">
                    <div>
                        <span 
                         class="font-semibold text-xs tracking-wide"
                            :class="nomeCompleto ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">Nome Completo:</span>
                        <p
                            class="font-bold text-lg tracking-wide"
                            :class="nomeCompleto ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
                            x-text="nomeCompleto || '—'"></p>
                    </div>
                    <div>
                        <span class="text-gray-600">Curso:</span>
                        <p class="font-semibold text-gray-900" x-text="codigoCurso || '-'">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Classe:</span>
                        <p class="font-semibold text-gray-900" x-text="classeNum ? classeNum + 'ª Classe' : '-'">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Turno:</span>
                        <p class="font-semibold text-gray-900" x-text="turno === 'M' ? 'Manhã' : (turno === 'T' ? 'Tarde' : '-')">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Capacidade:</span>
                        <p class="font-semibold text-gray-900" x-text="capacidade ? capacidade + ' alunos' : '-'">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Sala:</span>
                        <p class="font-semibold text-gray-900" x-text="sala ? sala.toUpperCase() : '-'">-</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Dicas" icon="fas fa-lightbulb">
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                        <span>O nome completo será gerado automaticamente</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                        <span>Pode adicionar alunos depois de criar</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                        <span>Disciplinas podem ser editadas posteriormente</span>
                    </li>
                </ul>
            </x-card>

            <x-card title="Status" icon="fas fa-toggle-on">
                <label class="flex items-center">
                    <input type="checkbox" name="ativo" value="1" checked 
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-600">Turma ativa</span>
                </label>
            </x-card>

            <div class="flex flex-col space-y-3">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-save mr-2"></i>
                    Criar Turma
                </button>
                <a href="{{ route('turmas.index') }}" class="btn btn-outline w-full">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
            </div>

        </div>

    </div>

</form>

@endsection

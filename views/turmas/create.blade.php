@extends('layouts.app')

@section('page-title', 'Nova Turma')

@section('content')

<form method="POST" action="{{ route('turmas.store') }}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário Principal -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Dados Básicos -->
            <x-card title="Dados da Turma" icon="fas fa-chalkboard">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Nome -->
                    <div>
                        <label class="label">Nome da Turma *</label>
                        <input type="text" name="nome" value="{{ old('nome') }}" 
                               placeholder="Ex: A, B, Sala 101" class="input" required>
                        @error('nome')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Classe -->
                    <div>
                        <label class="label">Classe *</label>
                        <select name="classe" class="input" required>
                            <option value="">Selecione...</option>
                            <option value="10" {{ old('classe') == '10' ? 'selected' : '' }}>10ª Classe</option>
                            <option value="11" {{ old('classe') == '11' ? 'selected' : '' }}>11ª Classe</option>
                            <option value="12" {{ old('classe') == '12' ? 'selected' : '' }}>12ª Classe</option>
                        </select>
                        @error('classe')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Curso -->
                    <div>
                        <label class="label">Curso *</label>
                        <select name="curso_id" class="input" required>
                            <option value="">Selecione...</option>
                            @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" {{ old('curso_id') == $curso->id ? 'selected' : '' }}>
                                {{ $curso->nome }}
                            </option>
                            @endforeach
                        </select>
                        @error('curso_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Coordenador -->
                    <div>
                        <label class="label">Coordenador</label>
                        <select name="coordenador_id" class="input">
                            <option value="">Sem coordenador</option>
                            @foreach($professores as $prof)
                            <option value="{{ $prof->id }}" {{ old('coordenador_id') == $prof->id ? 'selected' : '' }}>
                                {{ $prof->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Capacidade -->
                    <div>
                        <label class="label">Capacidade (Alunos) *</label>
                        <input type="number" name="capacidade" value="{{ old('capacidade', 30) }}" 
                               min="1" max="100" class="input" required>
                        @error('capacidade')
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
                                <option disabled>Nenhum ano letivo cadastrado</option>
                            @endforelse
                        </select>

                        @error('ano_letivo_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror

                        {{-- Mensagem de aviso caso não haja ano ativo --}}
                        @if(!$anoAtivo)
                            <p class="text-yellow-600 text-sm mt-2">
                                ⚠ Nenhum ano letivo está marcado como ativo.
                            </p>
                        @endif
                    </div>


                      

                </div>
            </x-card>

            <!-- Disciplinas -->
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

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Preview -->
            <x-card title="Preview" icon="fas fa-eye">
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-600">Nome Completo:</span>
                        <p class="font-semibold text-gray-900" id="preview-nome">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Curso:</span>
                        <p class="font-semibold text-gray-900" id="preview-curso">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Classe:</span>
                        <p class="font-semibold text-gray-900" id="preview-classe">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Capacidade:</span>
                        <p class="font-semibold text-gray-900" id="preview-capacidade">-</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Ano Letivo:</span>
                        <p class="font-semibold text-gray-900" id="preview-ano">-</p>
                    </div>
                </div>
            </x-card>

            <!-- Dicas -->
            <x-card title="Dicas" icon="fas fa-lightbulb">
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                        <span>O nome completo será gerado automaticamente</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                        <span>Você pode adicionar alunos depois de criar</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                        <span>Disciplinas podem ser editadas posteriormente</span>
                    </li>
                </ul>
            </x-card>

            <!-- Status -->
            <x-card title="Status" icon="fas fa-toggle-on">
                <label class="flex items-center">
                    <input type="checkbox" name="ativo" value="1" checked 
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-600">Turma ativa</span>
                </label>
            </x-card>

            <!-- Ações -->
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nomeInput = document.querySelector('[name="nome"]');
    const classeSelect = document.querySelector('[name="classe"]');
    const cursoSelect = document.querySelector('[name="curso_id"]');
    const capacidadeInput = document.querySelector('[name="capacidade"]');
    const anoSelect = document.querySelector('[name="ano_letivo_id"]');

    function updatePreview() {
        // Nome completo
        const classe = classeSelect.value;
        const nome = nomeInput.value;
        document.getElementById('preview-nome').textContent = 
            classe && nome ? `${classe}ª ${nome}` : '-';

        // Curso
        const cursoText = cursoSelect.options[cursoSelect.selectedIndex]?.text || '-';
        document.getElementById('preview-curso').textContent = cursoText;

        // Classe
        document.getElementById('preview-classe').textContent = 
            classe ? `${classe}ª Classe` : '-';

        // Capacidade
        document.getElementById('preview-capacidade').textContent = 
            capacidadeInput.value ? `${capacidadeInput.value} alunos` : '-';

        // Ano
        const anoText = anoSelect.options[anoSelect.selectedIndex]?.text || '-';
        document.getElementById('preview-ano').textContent = anoText;
    }

    nomeInput.addEventListener('input', updatePreview);
    classeSelect.addEventListener('change', updatePreview);
    cursoSelect.addEventListener('change', updatePreview);
    capacidadeInput.addEventListener('input', updatePreview);
    anoSelect.addEventListener('change', updatePreview);

    updatePreview();
});
</script>
@endpush
@extends('layouts.app')

@section('page-title', 'Editar Turma')

@section('content')

<form method="POST" action="{{ route('turmas.update', $turma) }}">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário Principal -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Dados Básicos -->
            <x-card title="Dados da Turma" icon="fas fa-chalkboard">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div>
                        <label class="label">Nome da Turma *</label>
                        <input type="text" name="nome" value="{{ old('nome', $turma->nome) }}" class="input" required>
                        @error('nome')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label">Classe *</label>
                        <select name="classe" class="input" required>
                            <option value="10" {{ $turma->classe == 10 ? 'selected' : '' }}>10ª Classe</option>
                            <option value="11" {{ $turma->classe == 11 ? 'selected' : '' }}>11ª Classe</option>
                            <option value="12" {{ $turma->classe == 12 ? 'selected' : '' }}>12ª Classe</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Curso *</label>
                        <select name="curso_id" class="input" required>
                            @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" {{ $turma->curso_id == $curso->id ? 'selected' : '' }}>
                                {{ $curso->nome }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Coordenador</label>
                        <select name="coordenador_turma_id" class="input">
                            <option value="">Sem coordenador</option>
                            @foreach($professores as $prof)
                            <option value="{{ $prof->id }}" {{ $turma->coordenador_turma_id == $prof->id ? 'selected' : '' }}>
                                {{ $prof->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Capacidade (Alunos) *</label>
                        <input type="number" name="capacidade" value="{{ old('capacidade', $turma->capacidade) }}" 
                               min="1" max="100" class="input" required>
                    </div>

                    <div>
                        <label class="label">Ano Letivo *</label>
                        <select name="ano_letivo_id" class="input" required>
                            @foreach($anosLetivos as $ano)
                            <option value="{{ $ano->id }}" {{ $turma->ano_letivo_id == $ano->id ? 'selected' : '' }}>
                                {{ $ano->nome }} {{ $ano->ativo ? '(Ativo)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </x-card>

            <!-- Disciplinas -->
            <x-card title="Disciplinas da Turma" icon="fas fa-book-open">
                <p class="text-sm text-gray-600 mb-4">
                    Marque as disciplinas que serão lecionadas nesta turma
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($disciplinas as $disciplina)
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="disciplinas[]" value="{{ $disciplina->id }}" 
                               {{ $turma->disciplinas->contains($disciplina->id) ? 'checked' : '' }}
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">{{ $disciplina->nome }}</span>
                            <p class="text-xs text-gray-500">{{ $disciplina->codigo }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </x-card>

        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Info -->
            <x-card title="Informações" icon="fas fa-info-circle">
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-600">Nome Completo:</span>
                        <p class="font-semibold text-gray-900">{{ $turma->nome_completo }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Total de Alunos:</span>
                        <p class="font-semibold text-gray-900">{{ $turma->total_alunos }} / {{ $turma->capacidade }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Criada em:</span>
                        <p class="font-semibold text-gray-900">{{ $turma->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </x-card>

            <!-- Status -->
            <x-card title="Status" icon="fas fa-toggle-on">
                <label class="flex items-center">
                    <input type="checkbox" name="ativo" value="1" {{ $turma->ativo ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-600">Turma ativa</span>
                </label>
            </x-card>

            <!-- Ações -->
            <div class="flex flex-col space-y-3">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
                <a href="{{ route('turmas.show', $turma) }}" class="btn btn-outline w-full">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>

        </div>

    </div>

</form>

@endsection
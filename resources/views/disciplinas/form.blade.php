@extends('layouts.app')

@section('page-title', isset($disciplina) ? 'Editar Disciplina' : 'Nova Disciplina')

@section('content')

<form method="POST" action="{{ isset($disciplina) ? route('disciplinas.update', $disciplina) : route('disciplinas.store') }}">
    @csrf
    @if(isset($disciplina)) 
        @method('PUT') 
    @endif

    <div class="max-w-4xl">
        <x-card title="Dados da Disciplina" icon="fas fa-book-open">
            
            <div class="space-y-4">
                
                <!-- Código e Nome -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Código *</label>
                        <input type="text" 
                               name="codigo" 
                               value="{{ old('codigo', $disciplina->codigo ?? '') }}" 
                               class="input" 
                               required
                               maxlength="10"
                               placeholder="Ex: MAT, FIS, QUI">
                        @error('codigo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="label">Nome *</label>
                        <input type="text" 
                               name="nome" 
                               value="{{ old('nome', $disciplina->nome ?? '') }}" 
                               class="input" 
                               required
                               maxlength="100"
                               placeholder="Ex: Matemática">
                        @error('nome')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Descrição -->
                <div>
                    <label class="label">Descrição</label>
                    <textarea name="descricao" 
                              class="input" 
                              rows="2"
                              maxlength="500"
                              placeholder="Descrição opcional da disciplina">{{ old('descricao', $disciplina->descricao ?? '') }}</textarea>
                    @error('descricao')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Classes onde é lecionada -->
                <div>
                    <p class="label">Lecionada nas Classes *</p>
                    <p class="text-xs text-gray-500 mb-2">Selecione em que classes esta disciplina é lecionada</p>
                    
                    <div class="flex space-x-4">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" 
                                   name="leciona_10" 
                                   value="1"
                                   {{ old('leciona_10', isset($disciplina) ? (bool)$disciplina->leciona_10 : false) ? 'checked' : '' }}
                                   class="rounded text-primary-600 focus:ring-primary-500 border-gray-300">
                            <span class="ml-2 group-hover:text-gray-900 transition-colors">10ª Classe</span>
                        </label>

                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" 
                                   name="leciona_11" 
                                   value="1"
                                   {{ old('leciona_11', isset($disciplina) ? (bool)$disciplina->leciona_11 : false) ? 'checked' : '' }}
                                   class="rounded text-primary-600 focus:ring-primary-500 border-gray-300">
                            <span class="ml-2 group-hover:text-gray-900 transition-colors">11ª Classe</span>
                        </label>

                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" 
                                   name="leciona_12" 
                                   value="1"
                                   {{ old('leciona_12', isset($disciplina) ? (bool)$disciplina->leciona_12 : false) ? 'checked' : '' }}
                                   class="rounded text-primary-600 focus:ring-primary-500 border-gray-300">
                            <span class="ml-2 group-hover:text-gray-900 transition-colors">12ª Classe</span>
                        </label>
                    </div>
                    
                    @error('leciona_10')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status e Terminal (legado) -->
                <div class="flex space-x-6">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" 
                               name="disciplina_terminal" 
                               value="1"
                               {{ old('disciplina_terminal', isset($disciplina) ? (bool)$disciplina->disciplina_terminal : false) ? 'checked' : '' }}
                               class="rounded text-primary-600 focus:ring-primary-500 border-gray-300">
                        <span class="ml-2 group-hover:text-gray-900 transition-colors">
                            Disciplina Terminal (legado)
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" 
                               name="ativo" 
                               value="1" 
                               {{ old('ativo', isset($disciplina) ? (bool)$disciplina->ativo : true) ? 'checked' : '' }} 
                               class="rounded text-primary-600 focus:ring-primary-500 border-gray-300">
                        <span class="ml-2 group-hover:text-gray-900 transition-colors">Ativa</span>
                    </label>
                </div>

                <!-- Ano Terminal por Curso -->
                @php
                    $terminaisPorCurso = old('cursos_terminal');

                    if ($terminaisPorCurso === null && isset($disciplina)) {
                        $terminaisPorCurso = $disciplina->cursos
                            ->pluck('pivot.ano_terminal', 'id')
                            ->map(fn($valor) => $valor !== null ? (string) $valor : '');
                    }
                @endphp

                <div>
                    <p class="label">Ano Terminal por Curso</p>
                    <p class="text-xs text-gray-500 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Configuração recomendada: defina em que ano esta disciplina termina para cada curso.
                    </p>

                    <div class="overflow-x-auto border rounded-lg shadow-sm">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Curso</th>
                                    <th class="text-left px-4 py-3 w-64 font-semibold text-gray-700">Ano terminal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse(($cursos ?? collect()) as $curso)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">{{ $curso->nome }}</td>
                                        <td class="px-4 py-3">
                                            <select name="cursos_terminal[{{ $curso->id }}]" 
                                                    class="input">
                                                <option value="">Não terminal neste curso</option>
                                                <option value="10" {{ ($terminaisPorCurso[$curso->id] ?? '') === '10' ? 'selected' : '' }}>
                                                    10ª classe
                                                </option>
                                                <option value="11" {{ ($terminaisPorCurso[$curso->id] ?? '') === '11' ? 'selected' : '' }}>
                                                    11ª classe
                                                </option>
                                                <option value="12" {{ ($terminaisPorCurso[$curso->id] ?? '') === '12' ? 'selected' : '' }}>
                                                    12ª classe
                                                </option>
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-center text-gray-500">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Nenhum curso ativo encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @error('cursos_terminal')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <!-- Botões de Ação -->
            <div class="mt-6 flex space-x-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    {{ isset($disciplina) ? 'Guardar Alterações' : 'Criar Disciplina' }}
                </button>
                <a href="{{ route('disciplinas.index') }}" class="btn btn-outline">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
            </div>

        </x-card>
    </div>
</form>

@endsection
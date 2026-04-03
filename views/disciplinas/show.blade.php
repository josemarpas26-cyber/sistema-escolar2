@extends('layouts.app')

@section('page-title', $disciplina->nome)

@section('header-actions')
<a href="{{ route('disciplinas.edit', $disciplina) }}" class="btn btn-primary">
    <i class="fas fa-edit mr-2"></i>
    Editar
</a>
@endsection

@section('content')

<div class="max-w-4xl space-y-6">
    
    <!-- Informações da Disciplina -->
    <x-card title="Informações da Disciplina" icon="fas fa-info-circle">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div>
                <span class="text-gray-600">Código:</span> 
                <span class="font-bold text-lg">{{ $disciplina->codigo }}</span>
            </div>

            <div>
                <span class="text-gray-600">Nome:</span> 
                <span class="font-semibold">{{ $disciplina->nome }}</span>
            </div>

            @if($disciplina->descricao)
            <div class="md:col-span-2">
                <span class="text-gray-600">Descrição:</span>
                <p class="text-gray-900 mt-1">{{ $disciplina->descricao }}</p>
            </div>
            @endif

            <div>
                <span class="text-gray-600">Classes onde é lecionada:</span>
                <div class="flex gap-2 mt-1">
                    @if($disciplina->leciona_10)
                        <x-badge type="info">10ª Classe</x-badge>
                    @endif
                    @if($disciplina->leciona_11)
                        <x-badge type="info">11ª Classe</x-badge>
                    @endif
                    @if($disciplina->leciona_12)
                        <x-badge type="info">12ª Classe</x-badge>
                    @endif
                    @if(!$disciplina->leciona_10 && !$disciplina->leciona_11 && !$disciplina->leciona_12)
                        <x-badge type="gray">Nenhuma classe configurada</x-badge>
                    @endif
                </div>
            </div>

            <div>
                <span class="text-gray-600">Disciplina Terminal (legado):</span>
                <div class="mt-1">
                    <x-badge type="{{ $disciplina->disciplina_terminal ? 'success' : 'gray' }}">
                        {{ $disciplina->disciplina_terminal ? 'Sim' : 'Não' }}
                    </x-badge>
                </div>
            </div>

            <div>
                <span class="text-gray-600">Status:</span>
                <div class="mt-1">
                    <x-badge type="{{ $disciplina->ativo ? 'success' : 'danger' }}">
                        {{ $disciplina->ativo ? 'Ativa' : 'Inativa' }}
                    </x-badge>
                </div>
            </div>

            <div>
                <span class="text-gray-600">Data de criação:</span>
                <span class="text-gray-900">{{ $disciplina->created_at->format('d/m/Y H:i') }}</span>
            </div>

        </div>
    </x-card>
    
    <!-- Ano Terminal por Curso -->
    <x-card title="Ano Terminal por Curso" icon="fas fa-sitemap">
        <p class="text-sm text-gray-600 mb-4">
            <i class="fas fa-info-circle mr-1"></i>
            Configuração do ano em que esta disciplina termina para cada curso
        </p>

        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Curso</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Ano terminal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($disciplina->cursos as $curso)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">{{ $curso->nome }}</td>
                            <td class="px-4 py-3">
                                @if($curso->pivot->ano_terminal)
                                    <x-badge type="warning">
                                        {{ $curso->pivot->ano_terminal }}ª classe
                                    </x-badge>
                                @else
                                    <x-badge type="gray">Não terminal</x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-4 text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>
                                Sem configuração por curso.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Estatísticas (se necessário - adicione depois) -->
    {{-- 
    <x-card title="Estatísticas" icon="fas fa-chart-bar">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ $disciplina->turmas_count ?? 0 }}</p>
                <p class="text-sm text-gray-600">Turmas</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $disciplina->professores_count ?? 0 }}</p>
                <p class="text-sm text-gray-600">Professores</p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <p class="text-2xl font-bold text-purple-600">{{ $disciplina->alunos_count ?? 0 }}</p>
                <p class="text-sm text-gray-600">Alunos</p>
            </div>
        </div>
    </x-card>
    --}}

</div>

@endsection
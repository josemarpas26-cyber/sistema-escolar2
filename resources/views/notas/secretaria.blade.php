@extends('layouts.app')

@section('page-title', 'Gerenciar Notas')

@section('content')
@php
    $podeReabrirNotas = auth()->user()->role->hasPermission('notas.reabrir');
@endphp
<!-- Filtros -->
<x-card title="Filtros de Pesquisa" icon="fas fa-filter" class="mb-6">
    <form method="GET" action="{{ route('notas.secretaria-index') }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- Turma -->
            <div>
                <label class="label">Turma</label>
                <select name="turma_id" class="input" onchange="this.form.submit()">
                    <option value="">Todas as turmas</option>
                    @foreach($turmas as $turma)
                    <option value="{{ $turma->id }}" {{ request('turma_id') == $turma->id ? 'selected' : '' }}>
                        {{ $turma->nome_completo }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Disciplina -->
            <div>
                <label class="label">Disciplina</label>
                <select name="disciplina_id" class="input" {{ !request('turma_id') ? 'disabled' : '' }} onchange="this.form.submit()">
                    <option value="">Todas as disciplinas</option>
                    @if(request('turma_id') && isset($disciplinas))
                        @foreach($disciplinas as $disciplina)
                        <option value="{{ $disciplina->id }}" {{ request('disciplina_id') == $disciplina->id ? 'selected' : '' }}>
                            {{ $disciplina->nome }}
                        </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Aluno -->
            <div>
                <label class="label">Aluno</label>
                <input type="text" name="aluno" value="{{ request('aluno') }}" 
                       placeholder="Nome ou Nº Processo" class="input">
            </div>

            <!-- Botões -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search mr-2"></i>
                    Buscar
                </button>
                <a href="{{ route('notas.secretaria-index') }}" class="btn btn-outline">
                    <i class="fas fa-times"></i>
                </a>
            </div>

        </div>
    </form>
</x-card>

<!-- Resultados -->
@if(request('turma_id') && request('disciplina_id') && isset($notas))

    <!-- Info da Seleção -->
    <x-card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $turmaSelecionada->nome_completo }} - {{ $disciplinaSelecionada->nome }}
                </h3>
                <p class="text-sm text-gray-600">
                    {{ $turmaSelecionada->curso->nome }} | Ano: {{ $turmaSelecionada->anoLetivo->nome }}
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('relatorios.pauta', [$turmaSelecionada, $disciplinaSelecionada]) }}" 
                   class="btn btn-outline btn-sm" target="_blank">
                    <i class="fas fa-file-alt mr-2"></i>
                    Ver Pauta
                </a>
                <a href="{{ route('relatorios.pauta', [$turmaSelecionada, $disciplinaSelecionada, 'formato' => 'pdf']) }}" 
                   class="btn btn-primary btn-sm">
                    <i class="fas fa-file-pdf mr-2"></i>
                    Baixar PDF
                </a>
            </div>
        </div>
    </x-card>

    <!-- Tabela de Notas -->
    <x-card title="Notas dos Alunos" icon="fas fa-table">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT1</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT2</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MFT2</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT3</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">CFD</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php $contador = 1; @endphp
                    @foreach($notas as $nota)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">{{ $contador++ }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('users.show', $nota->aluno) }}" class="font-medium text-primary-600 hover:text-primary-900">
                                {{ $nota->aluno->name }}
                            </a>
                            <p class="text-xs text-gray-500">{{ $nota->aluno->numero_processo }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">
                            {{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{ $nota->mt3 ? number_format($nota->mt3, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($nota->cfd)
                            <span class="font-bold {{ $nota->cfd >= 10 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($nota->cfd, 2) }}
                            </span>
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($nota->cfd)
                            <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}">
                                {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                            </x-badge>
                            @else
                            <x-badge type="gray">Pendente</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                          @if($nota->status === 'finalizado' && !$podeReabrirNotas)
                                <span class="text-gray-400" title="Nota finalizada (somente leitura)">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @else
                                <a href="{{ route('notas.edit', $nota) }}" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($notas->isEmpty())
        <div class="text-center py-8">
            <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Nenhuma nota encontrada para esta turma/disciplina</p>
        </div>
        @endif
    </x-card>

    <!-- Estatísticas -->
    @if($notas->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Total de Alunos</div>
            <div class="text-2xl font-bold text-gray-900">{{ $notas->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Média Geral</div>
            <div class="text-2xl font-bold text-primary-600">
                {{ $notas->avg('cfd') ? number_format($notas->avg('cfd'), 2) : '-' }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Aprovados</div>
            <div class="text-2xl font-bold text-green-600">
                {{ $notas->filter(fn($n) => $n->cfd && $n->isAprovado())->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Reprovados</div>
            <div class="text-2xl font-bold text-red-600">
                {{ $notas->filter(fn($n) => $n->cfd && !$n->isAprovado())->count() }}
            </div>
        </div>
    </div>
    @endif

@else

    <!-- Empty State -->
    <x-card>
        <div class="text-center py-12">
            <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Pesquisar Notas</h3>
            <p class="text-gray-600 mb-6">
                Selecione uma turma e disciplina acima para visualizar as notas
            </p>
        </div>
    </x-card>

@endif

@endsection
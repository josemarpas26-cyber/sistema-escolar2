@extends('layouts.app')

@section('page-title', 'Minhas Notas')

@section('header-actions')
<a href="{{ route('relatorios.boletim', auth()->user()) }}" class="btn btn-primary" target="_blank">
    <i class="fas fa-file-pdf mr-2"></i>
    Baixar Boletim
</a>
@endsection

@section('content')

@if($turmaAtual)

    <!-- Info da Turma -->
    <x-card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $turmaAtual->nome_completo }}</h3>
                <p class="text-sm text-gray-600">
                    {{ $turmaAtual->curso->nome }} | Ano: {{ $turmaAtual->anoLetivo->nome }}
                </p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-600">Média Geral</div>
                <div class="text-3xl font-bold {{ $mediaGeral >= 10 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($mediaGeral, 2) }}
                </div>
            </div>
        </div>
    </x-card>
<br>
    <!-- Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card 
            title="Disciplinas" 
            :value="$notas->count()"
            icon="fas fa-book"
            color="primary"
        />
        <x-stat-card 
            title="Média Geral" 
            :value="number_format($mediaGeral, 2)"
            icon="fas fa-chart-line"
            :color="$mediaGeral >= 10 ? 'green' : 'red'"
        />
        <x-stat-card 
            title="Aprovações" 
            :value="$aprovacoes"
            icon="fas fa-check-circle"
            color="green"
        />
        <x-stat-card 
            title="Reprovações" 
            :value="$reprovacoes"
            icon="fas fa-times-circle"
            color="red"
        />
    </div>

    <!-- Tabela de Notas -->
    <x-card title="Notas por Disciplina" icon="fas fa-clipboard-list">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT1</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT2</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">MFT2</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT3</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">PG</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">CFD</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($notas as $nota)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $nota->disciplina->nome }}</div>
                            <div class="text-xs text-gray-500">{{ $nota->disciplina->codigo }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            {{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center font-semibold">
                            {{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {{ $nota->mt3 ? number_format($nota->mt3, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {{ $nota->pg ? number_format($nota->pg, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($nota->cfd)
                            <span class="text-lg font-bold {{ $nota->cfd >= 10 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($nota->cfd, 2) }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($nota->cfd)
                            <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}">
                                {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                            </x-badge>
                            @else
                            <x-badge type="gray">Pendente</x-badge>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($notas->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg mb-2">Nenhuma nota disponível</p>
            <p class="text-gray-400 text-sm">As notas serão exibidas aqui quando forem lançadas pelos professores</p>
        </div>
        @endif
    </x-card>

    <!-- Legenda -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 mb-2">Legenda:</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-blue-800">
            <div><strong>MT1, MT2, MT3:</strong> Médias dos Trimestres 1, 2 e 3</div>
            <div><strong>MFT2:</strong> Média dos Primeiro e Segundo Trimestres</div>
            <div><strong>PG:</strong> Prova Global</div>
            <div><strong>CFD:</strong> Classificação Final da Disciplina</div>
            <div class="md:col-span-2">
                <strong>Aprovação:</strong> CFD ≥ 10 valores
            </div>
        </div>
    </div>

@else

    <!-- Sem Turma -->
    <x-card>
        <div class="text-center py-12">
            <i class="fas fa-exclamation-triangle text-5xl text-yellow-400 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Você não está matriculado em nenhuma turma</h3>
            <p class="text-gray-600">
                Entre em contato com a secretaria para regularizar sua matrícula
            </p>
        </div>
    </x-card>

@endif

@endsection
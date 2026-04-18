@extends('layouts.app')

@section('page-title', 'Minhas Notas')

@section('header-actions')
<a href="{{ route('relatorios.boletim', auth()->user()) }}" class="btn btn-primary" target="_blank">
    <i class="fas fa-file-pdf mr-2"></i>
    Baixar Boletim
</a>
@endsection

@section('content')
@php
    $formatNota = function ($valor, $fallback = 'Sem dado') {
        return $valor !== null ? number_format((float) $valor, 2) : $fallback;
    };
@endphp

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
    <div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-4">
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
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Disciplina</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MAC1</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MT1</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MAC2</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MT2</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MFT2</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MAC3</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MT3</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PG</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CFD</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>

                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($notas as $nota)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $nota->disciplina->nome }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $nota->disciplina->codigo }}</div>
                        </td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">{{ $nota->mac1 !== null ? number_format($nota->mac1, 2) : '-' }}</td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">
                            {{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}
                        </td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">{{ $nota->mac2 !== null ? number_format($nota->mac2, 2) : '-' }}</td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">
                            {{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}
                        </td>

                        <td class="px-6 py-4 text-center font-semibold text-gray-900 dark:text-gray-100">
                            {{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}
                        </td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">{{ $nota->mac3 !== null ? number_format($nota->mac3, 2) : '-' }}</td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">
                            {{ $nota->mt3 ? number_format($nota->mt3, 2) : '-' }}
                        </td>

                        <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">
                            {{ $nota->pg ? number_format($nota->pg, 2) : '-' }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($nota->cfd)
                            <span class="text-lg font-bold {{ $nota->cfd >= 10 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($nota->cfd, 2) }}
                            </span>
                            @else
                            <span class="text-gray-400 dark:text-gray-500">-</span>
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

                    <tr class="bg-gray-50/60 dark:bg-gray-800/50">
                        <td class="px-6 pb-4 pt-0 text-xs text-gray-600 dark:text-gray-400" colspan="11">
                            {{-- conteúdo --}}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($notas->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-clipboard-list text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">Nenhuma nota disponível</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm">As notas serão exibidas aqui quando forem lançadas pelos professores</p>
        </div>
        @endif
    </x-card>

    <x-card title="Avaliações completas por disciplina" icon="fas fa-clipboard-list" class="mt-6">
        @if($disciplinasDetalhadas->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg mb-2">Sem disciplinas no ano letivo atual</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($disciplinasDetalhadas as $item)
            @php
                $nota = $item['nota'];
                $statusClasse = 'background:var(--warn-bg); color:var(--warn-tx); border:1px solid var(--warn-bd);';

                if ($nota?->cfd !== null) {
                    $statusClasse = $nota->isAprovado()
                        ? 'background:var(--ok-bg); color:var(--ok-tx); border:1px solid var(--ok-bd);'
                        : 'background:var(--err-bg); color:var(--err-tx); border:1px solid var(--err-bd);';
                }
            @endphp
            <details class="rounded-2xl overflow-hidden" style="border:1px solid var(--border); background:var(--surface);" @if($loop->first) open @endif>
                <summary class="p-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between" style="background:linear-gradient(180deg,var(--surface),var(--surface-sunken));">
                    <div>
                        <h3 class="text-lg font-bold" style="color:var(--tx-1);">{{ $item['disciplina']->nome }} - {{ $item['disciplina']->codigo }}</h3>
                        <p class="mt-2 text-sm" style="color:var(--tx-3);">
                            Professor: {{ $item['professor']?->name ?? 'Nao associado' }}
                            · Coordenador: {{ $item['coordenador']?->name ?? 'Nao definido' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full px-3 py-2 text-sm font-extrabold" style="background:var(--info-bg); color:var(--info-tx); border:1px solid var(--info-bd);">
                            {{ $item['indicador']['label'] }}: {{ $item['indicador']['valor'] !== null ? number_format($item['indicador']['valor'], 2) : '--' }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-2 text-sm font-extrabold" style="{{ $statusClasse }}">
                            @if($nota?->cfd !== null)
                                {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                            @elseif($nota)
                                Em andamento
                            @else
                                Sem lancamento
                            @endif
                        </span>
                    </div>
                </summary>

                <div class="p-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    @foreach([
                        ['titulo' => '1º Trimestre', 'disponivel' => $nota ? $nota->trimestreEstaDisponivel(1) : true, 'campos' => [['label' => 'MAC1', 'valor' => $nota?->mac1], ['label' => 'PP1', 'valor' => $nota?->pp1], ['label' => 'PT1', 'valor' => $nota?->pt1], ['label' => 'MT1', 'valor' => $nota?->mt1]]],
                        ['titulo' => '2º Trimestre', 'disponivel' => $nota ? $nota->trimestreEstaDisponivel(2) : true, 'campos' => [['label' => 'MAC2', 'valor' => $nota?->mac2], ['label' => 'PP2', 'valor' => $nota?->pp2], ['label' => 'PT2', 'valor' => $nota?->pt2], ['label' => 'MT2', 'valor' => $nota?->mt2], ['label' => 'MFT2', 'valor' => $nota?->mft2]]],
                        ['titulo' => '3º Trimestre', 'disponivel' => $nota ? $nota->trimestreEstaDisponivel(3) : true, 'campos' => [['label' => 'MAC3', 'valor' => $nota?->mac3], ['label' => 'PP3', 'valor' => $nota?->pp3], ['label' => 'PG', 'valor' => $nota?->pg], ['label' => 'MT3', 'valor' => $nota?->mt3]]],
                        ['titulo' => 'Fecho final', 'disponivel' => true, 'campos' => [['label' => 'CF', 'valor' => $nota?->cf], ['label' => 'CA', 'valor' => $nota?->ca], ['label' => 'CFD', 'valor' => $nota?->cfd], ['label' => 'Estado', 'valor' => $nota?->status_final]]],
                    ] as $bloco)
                    <section class="rounded-2xl overflow-hidden" style="background:var(--surface-sunken); border:1px solid var(--border);">
                        <div class="p-4 flex items-center justify-between" style="border-bottom:1px solid var(--border);">
                            <h4 class="font-bold" style="color:var(--tx-1);">{{ $bloco['titulo'] }}</h4>
                            <span class="text-xs font-bold uppercase tracking-[0.14em]" style="color:var(--tx-4);">
                                {{ !$bloco['disponivel'] ? 'Nao aplicavel' : (!$nota ? 'Sem lancamento' : 'Dados completos') }}
                            </span>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
                            @foreach($bloco['campos'] as $campo)
                            <div class="rounded-xl p-3" style="background:var(--surface); border:1px solid var(--border);">
                                <div class="text-xs font-bold uppercase tracking-[0.14em]" style="color:var(--tx-4);">{{ $campo['label'] }}</div>
                                <div class="mt-2 text-base font-extrabold" style="color:{{ $campo['valor'] === null ? 'var(--tx-4)' : 'var(--tx-1)' }};">
                                    @if(!$bloco['disponivel'] && $campo['label'] !== 'Estado')
                                        Nao aplicavel
                                    @elseif($campo['label'] === 'Estado')
                                        {{ $campo['valor'] ?? ($nota ? 'Em andamento' : 'Sem lancamento') }}
                                    @else
                                        {{ $formatNota($campo['valor']) }}
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </section>
                    @endforeach
                </div>
            </details>
            @endforeach
        </div>
        @endif
    </x-card>

    <!-- Legenda -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-5">
        <h4 class="font-semibold text-blue-900 mb-2">Legenda:</h4>
        <div class="grid grid-cols-1 gap-4 text-sm text-blue-800 md:grid-cols-2">
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
            <h3 class="text-lg font-semibold text-gray-900 mb-2">O utilizador não está matriculado em nenhuma turma</h3>
            <p class="text-gray-600">
                   Entre em contacto com a secretaria para regularizar a sua matrícula
            </p>
        </div>
    </x-card>

@endif

@endsection

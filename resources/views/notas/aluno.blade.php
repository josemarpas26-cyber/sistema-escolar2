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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MAC1</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MT1</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MAC2</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MT2</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MFT2</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MAC3</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">MT3</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">PG</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">CFD</th>
                        <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($notas as $nota)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $nota->disciplina->nome }}</div>
                            <div class="text-xs text-gray-500">{{ $nota->disciplina->codigo }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">{{ $nota->mac1 !== null ? number_format($nota->mac1, 2) : '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            {{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">{{ $nota->mac2 !== null ? number_format($nota->mac2, 2) : '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            {{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center font-semibold">
                            {{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">{{ $nota->mac3 !== null ? number_format($nota->mac3, 2) : '-' }}</td>
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
                    <tr class="bg-gray-50/60">
                        <td class="px-6 pb-4 pt-0 text-xs text-gray-600" colspan="11">
                            @php
                                $av1 = $nota->avaliacoesContinuas->where('trimestre', 1);
                                $av2 = $nota->avaliacoesContinuas->where('trimestre', 2);
                                $av3 = $nota->avaliacoesContinuas->where('trimestre', 3);
                                $blocosTrimestres = [
                                    1 => ['items' => $av1, 'media' => $nota->mac1],
                                    2 => ['items' => $av2, 'media' => $nota->mac2],
                                    3 => ['items' => $av3, 'media' => $nota->mac3],
                                ];
                            @endphp
                            <div class="font-semibold text-gray-700 mb-2">Avaliações contínuas por trimestre</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach($blocosTrimestres as $trimestre => $bloco)
                                    @php
                                        $media = $bloco['media'];
                                        $mediaClass = $media === null
                                            ? 'bg-gray-100 text-gray-500'
                                            : ($media >= 10 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
                                    @endphp
                                    <div class="rounded-lg border border-gray-200 bg-white p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-semibold text-gray-800">{{ $trimestre }}º Trimestre</span>
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold {{ $mediaClass }}">
                                                Média: {{ $media !== null ? number_format($media, 2) : '—' }}
                                            </span>
                                        </div>

                                        @if($bloco['items']->isEmpty())
                                            <p class="text-gray-400">Sem avaliações lançadas.</p>
                                        @else
                                            <ul class="space-y-1.5">
                                                @foreach($bloco['items'] as $avaliacao)
                                                    @php
                                                        $notaClass = $avaliacao->valor >= 10 ? 'text-green-600' : 'text-red-600';
                                                    @endphp
                                                    <li class="flex items-center justify-between gap-2 border-b border-gray-100 pb-1 last:border-0 last:pb-0">
                                                        <span class="truncate text-gray-700">{{ $avaliacao->descricao }}</span>
                                                        <span class="shrink-0 {{ $notaClass }} font-semibold">{{ number_format($avaliacao->valor, 2) }}</span>
                                                        <span class="shrink-0 text-gray-500">
                                                            {{ optional($avaliacao->data_avaliacao)->format('d/m/Y') ?? 's/ data' }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
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

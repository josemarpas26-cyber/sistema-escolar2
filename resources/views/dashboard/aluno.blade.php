@extends('layouts.app')

@section('page-title', 'Painel Academico')

@section('header-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('notas.index') }}" class="btn btn-outline">
        <i class="fas fa-clipboard-list mr-2"></i>
        Minhas notas
    </a>
    <a href="{{ route('relatorios.boletim', auth()->user()) }}" class="btn btn-primary" target="_blank">
        <i class="fas fa-file-pdf mr-2"></i>
        Boletim
    </a>
</div>
@endsection

@push('styles')
<style>
.aluno-dashboard details summary { list-style: none; }
.aluno-dashboard details summary::-webkit-details-marker { display: none; }
.aluno-dashboard .ad-bar { height: 10px; border-radius: 999px; background: var(--hover-bg); overflow: hidden; }
.aluno-dashboard .ad-bar > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--blue-500), var(--ok-ico)); }
</style>
@endpush

@section('content')
@php
    $formatNota = function ($valor, $fallback = 'Sem dado') {
        return $valor !== null ? number_format((float) $valor, 2) : $fallback;
    };
@endphp

<div class="aluno-dashboard space-y-6">
    <section class="rounded-3xl p-6 md:p-8" style="background:linear-gradient(135deg,var(--surface),var(--surface-sunken)); border:1px solid var(--border); box-shadow:var(--sh-sm);">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]" style="background:var(--info-bg); color:var(--info-tx); border:1px solid var(--info-bd);">
                    <i class="fas fa-user-graduate"></i>
                    Ano letivo {{ $ano_letivo->nome }}
                </div>
                <div class="space-y-2">
                    <h1 class="text-2xl md:text-4xl font-black leading-tight" style="color:var(--tx-1);">Resumo academico completo do aluno</h1>
                    <p class="max-w-3xl text-sm md:text-base" style="color:var(--tx-3);">
                        O painel mostra apenas os dados ligados a sua turma atual e as disciplinas realmente associadas a ela, incluindo professores, coordenacao, avaliacoes completas, estatisticas e historico.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold" style="background:var(--surface); color:var(--tx-2); border:1px solid var(--border);">
                        <i class="fas fa-book-open"></i>
                        {{ $total_disciplinas }} disciplina(s)
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold" style="background:var(--surface); color:var(--tx-2); border:1px solid var(--border);">
                        <i class="fas fa-check-circle"></i>
                        {{ $disciplinas_com_resultado }} com resultado final
                    </span>
                    @if($turma)
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold" style="background:var(--surface); color:var(--tx-2); border:1px solid var(--border);">
                        <i class="fas fa-school"></i>
                        {{ $turma->nome_completo }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl p-5 w-full xl:max-w-xs" style="background:var(--surface); border:1px solid var(--border); box-shadow:var(--sh-sm);">
                <div class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--tx-4);">Media atual</div>
                <div class="mt-3 text-4xl font-black" style="color:var(--tx-1);">
                    {{ $media_atual !== null ? number_format($media_atual, 2) : '--' }}
                </div>
                <p class="mt-3 text-sm" style="color:var(--tx-3);">
                    Baseada no indicador mais recente disponivel em cada disciplina.
                </p>
            </div>
        </div>
    </section>

     <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-5">
        <x-stat-card title="Media geral" :value="$disciplinas_com_resultado > 0 ? number_format($media_geral, 2) : '--'" icon="fas fa-chart-line" :color="$media_geral >= 10 ? 'green' : 'warning'" />
        <x-stat-card title="Media atual" :value="$media_atual !== null ? number_format($media_atual, 2) : '--'" icon="fas fa-wave-square" color="primary" />
        <x-stat-card title="Disciplinas" :value="$total_disciplinas" icon="fas fa-book" color="blue" />
        <x-stat-card title="Aprovacoes" :value="$aprovacoes" icon="fas fa-check-circle" color="green" />
        <x-stat-card title="Reprovacoes" :value="$reprovacoes" icon="fas fa-times-circle" color="red" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="Turma e contexto atual" icon="fas fa-school">
            @if($turma)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Turma</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->nome_completo }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">{{ $turma->classe }}a classe</div>
                </div>
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Curso</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->curso->nome ?? 'Sem curso' }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Ano {{ $turma->anoLetivo->nome ?? $ano_letivo->nome }}</div>
                </div>
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Diretor de turma</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->coordenador?->name ?? 'Nao definido' }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Responsavel pelo acompanhamento da turma</div>
                </div>
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Coordenacao de disciplinas</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $disciplinas_detalhadas->whereNotNull('coordenador')->count() }} disciplina(s)</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Com coordenador associado</div>
                </div>
            </div>
            @else
            <div class="py-10 text-center">
                <i class="fas fa-school text-3xl mb-3" style="color:var(--tx-4);"></i>
                <div class="text-lg font-bold" style="color:var(--tx-1);">Sem turma ativa neste ano letivo</div>
                <p class="mt-2 text-sm max-w-xl mx-auto" style="color:var(--tx-3);">
                    O dashboard continua a mostrar o historico abaixo, mas os dados do ano letivo atual dependem de uma matricula ativa.
                </p>
            </div>
            @endif
        </x-card>

        <x-card title="Evolucao temporal" icon="fas fa-chart-area">
             <div class="space-y-5">
                @foreach($evolucao_temporal as $ponto)
                @php $percentual = $ponto['media'] !== null ? min(100, max(0, ($ponto['media'] / 20) * 100)) : 0; @endphp
                <div class="space-y-2.5 py-2.5">
                    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-bold" style="color:var(--tx-1);">{{ $ponto['label'] }}</div>
                            <div class="text-sm" style="color:var(--tx-3);">{{ $ponto['total'] }} disciplina(s) com dados</div>
                        </div>
                        <div class="text-sm font-semibold" style="color:var(--tx-2);">
                            Media {{ $ponto['media'] !== null ? number_format($ponto['media'], 2) : '--' }}
                            @if($ponto['taxa_aprovacao'] !== null)
                                · {{ number_format($ponto['taxa_aprovacao'], 1) }}% >= 10
                            @endif
                        </div>
                    </div>
                     <div class="ad-bar mt-1.5">
                        <span style="width: {{ $percentual }}%"></span>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <x-card title="Desempenho por disciplina" icon="fas fa-chart-bar">
        @if($desempenho_disciplinas->isEmpty())
        <div class="py-10 text-center">
            <i class="fas fa-chart-bar text-3xl mb-3" style="color:var(--tx-4);"></i>
            <div class="text-lg font-bold" style="color:var(--tx-1);">Sem disciplinas para analisar</div>
            <p class="mt-2 text-sm" style="color:var(--tx-3);">Assim que a turma e as notas estiverem ligadas ao aluno, esta area sera preenchida automaticamente.</p>
        </div>
        @else
         <div class="space-y-0">
            @foreach($desempenho_disciplinas as $item)
            <article class="py-4 @if(!$loop->last) border-b @endif" style="border-color:var(--border);">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h3 class="text-lg font-bold" style="color:var(--tx-1);">{{ $item['disciplina']->nome }} - {{ $item['disciplina']->codigo }}</h3>
                        <p class="mt-1 text-sm" style="color:var(--tx-3);">
                            Professor: {{ $item['professor']?->name ?? 'Nao associado' }}
                            · Coordenador: {{ $item['coordenador']?->name ?? 'Nao definido' }}
                        </p>
                    </div>
                    <div class="text-left md:text-right">
                        <div class="inline-flex rounded-full px-3 py-2 text-sm font-extrabold" style="background:var(--info-bg); color:var(--info-tx); border:1px solid var(--info-bd);">
                            {{ $item['indicador']['valor'] !== null ? number_format($item['indicador']['valor'], 2) : '--' }}
                        </div>
                        <div class="mt-2 text-xs font-bold uppercase tracking-[0.14em]" style="color:var(--tx-4);">{{ $item['indicador']['label'] }}</div>
                    </div>
                </div>
                <div class="mt-4 ad-bar">
                    <span style="width: {{ $item['percentual'] }}%"></span>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </x-card>

    <x-card title="Avaliacoes completas por disciplina" icon="fas fa-clipboard-list">
        @if($disciplinas_detalhadas->isEmpty())
        <div class="py-10 text-center">
            <i class="fas fa-clipboard-list text-3xl mb-3" style="color:var(--tx-4);"></i>
            <div class="text-lg font-bold" style="color:var(--tx-1);">Sem disciplinas no ano letivo atual</div>
            <p class="mt-2 text-sm" style="color:var(--tx-3);">Quando a matricula e as disciplinas da turma estiverem prontas, este bloco passa a exibir todas as avaliacoes.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($disciplinas_detalhadas as $item)
            @php
                $nota = $item['nota'];
                $statusClasse = 'background:var(--warn-bg); color:var(--warn-tx); border:1px solid var(--warn-bd);';

                if ($nota?->cfd !== null) {
                    $statusClasse = $nota->isAprovado()
                        ? 'background:var(--ok-bg); color:var(--ok-tx); border:1px solid var(--ok-bd);'
                        : 'background:var(--err-bg); color:var(--err-tx); border:1px solid var(--err-bd);';
                }

                $blocos = [
                    [
                        'titulo' => '1o Trimestre',
                        'disponivel' => $nota ? $nota->trimestreEstaDisponivel(1) : true,
                        'campos' => [['label' => 'MAC1', 'valor' => $nota?->mac1], ['label' => 'PP1', 'valor' => $nota?->pp1], ['label' => 'PT1', 'valor' => $nota?->pt1], ['label' => 'MT1', 'valor' => $nota?->mt1]],
                    ],
                    [
                        'titulo' => '2o Trimestre',
                        'disponivel' => $nota ? $nota->trimestreEstaDisponivel(2) : true,
                        'campos' => [['label' => 'MAC2', 'valor' => $nota?->mac2], ['label' => 'PP2', 'valor' => $nota?->pp2], ['label' => 'PT2', 'valor' => $nota?->pt2], ['label' => 'MT2', 'valor' => $nota?->mt2], ['label' => 'MFT2', 'valor' => $nota?->mft2]],
                    ],
                    [
                        'titulo' => '3o Trimestre',
                        'disponivel' => $nota ? $nota->trimestreEstaDisponivel(3) : true,
                        'campos' => [['label' => 'MAC3', 'valor' => $nota?->mac3], ['label' => 'PP3', 'valor' => $nota?->pp3], ['label' => 'PG', 'valor' => $nota?->pg], ['label' => 'MT3', 'valor' => $nota?->mt3]],
                    ],
                    [
                        'titulo' => 'Fecho final',
                        'disponivel' => true,
                        'campos' => [['label' => 'CF', 'valor' => $nota?->cf], ['label' => 'CA', 'valor' => $nota?->ca], ['label' => 'CFD', 'valor' => $nota?->cfd], ['label' => 'Estado', 'valor' => $nota?->status_final]],
                    ],
                ];
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
                    @foreach($blocos as $bloco)
                    <section class="rounded-2xl overflow-hidden" style="background:var(--surface-sunken); border:1px solid var(--border);">
                        <div class="p-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between" style="border-bottom:1px solid var(--border);">
                            <h4 class="font-bold" style="color:var(--tx-1);">{{ $bloco['titulo'] }}</h4>
                            <span class="text-xs font-bold uppercase tracking-[0.14em]" style="color:var(--tx-4);">
                                @if(!$bloco['disponivel'])
                                    Nao aplicavel
                                @elseif(!$nota)
                                    Sem lancamento
                                @else
                                    Dados completos
                                @endif
                            </span>
                        </div>
                        <div class="p-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                            @foreach($bloco['campos'] as $campo)
                            <div class="rounded-xl p-4" style="background:var(--surface); border:1px solid var(--border);">
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

    <x-card title="Historico academico" icon="fas fa-history">
        @if($historico_por_ano->isEmpty())
        <div class="py-10 text-center">
            <i class="fas fa-history text-3xl mb-3" style="color:var(--tx-4);"></i>
            <div class="text-lg font-bold" style="color:var(--tx-1);">Sem historico anterior disponivel</div>
            <p class="mt-2 text-sm" style="color:var(--tx-3);">Quando os anos anteriores forem processados e encerrados, as classificacoes finais passam a aparecer aqui.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($historico_por_ano as $ano)
            <article class="rounded-2xl overflow-hidden" style="background:var(--surface-sunken); border:1px solid var(--border);">
                <div class="p-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between" style="border-bottom:1px solid var(--border);">
                    <div>
                        <h3 class="text-lg font-bold" style="color:var(--tx-1);">{{ $ano['ano'] }}</h3>
                        <p class="mt-1 text-sm" style="color:var(--tx-3);">{{ $ano['registos']->count() }} disciplina(s) registradas no historico</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full px-3 py-2 text-xs font-bold uppercase tracking-[0.14em]" style="background:var(--surface); color:var(--tx-2); border:1px solid var(--border);">Media {{ $ano['media'] !== null ? number_format($ano['media'], 2) : '--' }}</span>
                        <span class="inline-flex rounded-full px-3 py-2 text-xs font-bold uppercase tracking-[0.14em]" style="background:var(--surface); color:var(--tx-2); border:1px solid var(--border);">{{ $ano['aprovadas'] }} aprovadas</span>
                        <span class="inline-flex rounded-full px-3 py-2 text-xs font-bold uppercase tracking-[0.14em]" style="background:var(--surface); color:var(--tx-2); border:1px solid var(--border);">{{ $ano['reprovadas'] }} reprovadas</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y" style="border-color:var(--border);">
                        <thead style="background:var(--surface);">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.14em]" style="color:var(--tx-4);">Disciplina</th>
                                <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.14em]" style="color:var(--tx-4);">Turma</th>
                                <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.14em]" style="color:var(--tx-4);">Classe</th>
                                <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.14em]" style="color:var(--tx-4);">CFD</th>
                                <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.14em]" style="color:var(--tx-4);">Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:var(--border);">
                            @foreach($ano['registos'] as $registo)
                            <tr>
                                <td class="px-4 py-4 align-top" style="color:var(--tx-2);">
                                    <div class="font-bold" style="color:var(--tx-1);">{{ $registo->disciplina->nome ?? 'Disciplina removida' }}</div>
                                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Coordenador: {{ $registo->disciplina->coordenador?->name ?? 'Nao definido' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top" style="color:var(--tx-2);">
                                    <div class="font-semibold">{{ $registo->turma->nome_completo ?? ($registo->turma->nome ?? 'Turma removida') }}</div>
                                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Diretor: {{ $registo->turma->coordenador?->name ?? 'Nao definido' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top" style="color:var(--tx-2);">{{ $registo->classe ?? '--' }}</td>
                                <td class="px-4 py-4 align-top font-bold" style="color:var(--tx-1);">{{ $registo->classificacao_final !== null ? number_format((float) $registo->classificacao_final, 2) : '--' }}</td>
                                <td class="px-4 py-4 align-top" style="color:var(--tx-2);">{{ ucfirst((string) ($registo->resultado ?? 'Sem resultado')) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </x-card>
</div>
@endsection

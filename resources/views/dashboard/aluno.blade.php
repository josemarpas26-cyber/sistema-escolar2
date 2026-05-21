@extends('layouts.app')

@section('page-title', 'Painel Acadêmico')
@section('header-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('relatorios.boletim', auth()->user()) }}" class="btn btn-primary" target="_blank">
        <i class="fas fa-file-pdf mr-2"></i>Boletim
    </a>
</div>
@endsection

@push('styles')
<style>
.aluno-dashboard details summary { list-style: none; }
.aluno-dashboard details summary::-webkit-details-marker { display: none; }
.aluno-dashboard .ad-bar { height: 8px; border-radius: 999px; background: var(--hover-bg); overflow: hidden; }
.aluno-dashboard .ad-bar > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--blue-500), var(--ok-ico)); }
.ad-chart-wrap { position: relative; width: 100%; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const tx3   = dark ? '#888780' : '#73726c';
    const surf  = dark ? '#1e1e1c' : '#ffffff';
    const gridC = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.07)';

    // --- DADOS vindos do controller ---
    const evoLabels = @json($evolucao_temporal->pluck('label'));
    const evoMedias = @json($evolucao_temporal->pluck('media'));

    const discNomes = @json($desempenho_disciplinas->pluck('disciplina.nome'));
    const discVals  = @json($desempenho_disciplinas->map(fn($i) => $i['indicador']['valor']));

    // Gráfico de linha – evolução temporal
    const lineCtx = document.getElementById('adLineChart');
    if (lineCtx) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: evoLabels,
                datasets: [{
                    label: 'Média',
                    data: evoMedias,
                    borderColor: '#378ADD',
                    backgroundColor: 'rgba(55,138,221,0.10)',
                    pointBackgroundColor: '#378ADD',
                    pointBorderColor: surf,
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` Média: ${ctx.parsed.y !== null ? ctx.parsed.y.toFixed(2) : '--'}` } }
                },
                scales: {
                    y: { min: 0, max: 20, ticks: { color: tx3, font: { size: 11 }, stepSize: 5 }, grid: { color: gridC }, border: { display: false } },
                    x: { ticks: { color: tx3, font: { size: 11 } }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    // Gráfico de barras – desempenho por disciplina
    const barCtx = document.getElementById('adBarChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: discNomes,
                datasets: [{
                    label: 'Nota',
                    data: discVals,
                    backgroundColor: discVals.map(v => {
                        if (v === null) return 'rgba(156,154,146,0.4)';
                        if (v >= 14)   return 'rgba(55,138,221,0.85)';
                        if (v >= 10)   return 'rgba(99,153,34,0.85)';
                        if (v >= 8)    return 'rgba(186,117,23,0.85)';
                        return 'rgba(226,75,74,0.85)';
                    }),
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` Nota: ${ctx.parsed.y !== null ? ctx.parsed.y.toFixed(2) : '--'}` } }
                },
                scales: {
                    y: { min: 0, max: 20, ticks: { color: tx3, font: { size: 11 }, stepSize: 5 }, grid: { color: gridC }, border: { display: false } },
                    x: { ticks: { color: tx3, font: { size: 11 }, autoSkip: false, maxRotation: 35 }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    // Gráfico de rosca – distribuição de notas
    const pieCtx = document.getElementById('adPieChart');
    if (pieCtx) {
        const aprov   = {{ $aprovacoes }};
        const reprov  = {{ $reprovacoes }};
        const pendente = {{ $total_disciplinas }} - aprov - reprov;
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Aprovadas', 'Pendentes', 'Reprovadas'],
                datasets: [{
                    data: [aprov, pendente, reprov],
                    backgroundColor: ['rgba(99,153,34,0.85)', 'rgba(186,117,23,0.85)', 'rgba(226,75,74,0.85)'],
                    borderColor: surf,
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
                }
            }
        });
    }
});
</script>
@endpush

@section('content')
<div class="aluno-dashboard space-y-6">

    {{-- ─── HERO ─── --}}
    <section class="rounded-3xl p-6 md:p-8"
        style="background:linear-gradient(135deg,var(--surface),var(--surface-sunken));border:1px solid var(--border);box-shadow:var(--sh-sm);">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]"
                    style="background:var(--info-bg);color:var(--info-tx);border:1px solid var(--info-bd);">
                    <i class="fas fa-user-graduate"></i>
                    Ano letivo {{ $ano_letivo->nome }}
                </div>
                <div class="space-y-2">
                    <h1 class="text-2xl md:text-4xl font-black leading-tight" style="color:var(--tx-1);">
                        Olá, {{ auth()->user()->name ? explode(' ', auth()->user()->name)[0] : 'Aluno' }}
                    </h1>
                    <p class="max-w-3xl text-sm md:text-base" style="color:var(--tx-3);">
                        Bem-vindo ao seu painel acadêmico! Acompanhe o seu desempenho, resultados por disciplina e estatísticas gerais.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold"
                        style="background:var(--surface);color:var(--tx-2);border:1px solid var(--border);">
                        <i class="fas fa-book-open"></i> {{ $total_disciplinas }} disciplina(s)
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold"
                        style="background:var(--surface);color:var(--tx-2);border:1px solid var(--border);">
                        <i class="fas fa-check-circle"></i> {{ $disciplinas_com_resultado }} com resultado final
                    </span>
                    @if($turma)
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold"
                        style="background:var(--surface);color:var(--tx-2);border:1px solid var(--border);">
                        <i class="fas fa-school"></i> {{ $turma->nome_completo }}
                    </span>
                    @endif
                </div>
            </div>
            <div class="rounded-2xl p-5 w-full xl:max-w-xs"
                style="background:var(--surface);border:1px solid var(--border);box-shadow:var(--sh-sm);">
                <div class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--tx-4);">Média atual</div>
                <div class="mt-3 text-5xl font-black" style="color:var(--tx-1);">
                    {{ $media_atual !== null ? number_format($media_atual, 2) : '--' }}
                </div>
                <p class="mt-3 text-sm" style="color:var(--tx-3);">
                    Baseada no indicador mais recente disponível em cada disciplina.
                </p>
            </div>
        </div>
    </section>

    {{-- ─── STAT CARDS ─── --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        <x-stat-card title="Média geral (CF)" :value="$disciplinas_com_resultado > 0 ? number_format($media_geral, 2) : '--'" icon="fas fa-chart-line" :color="$media_geral >= 10 ? 'green' : 'warning'" />
        <x-stat-card title="Média atual"       :value="$media_atual !== null ? number_format($media_atual, 2) : '--'"   icon="fas fa-wave-square" color="primary" />
        <x-stat-card title="Disciplinas"       :value="$total_disciplinas"  icon="fas fa-book"         color="blue" />
        <x-stat-card title="Aprovações"        :value="$aprovacoes"         icon="fas fa-check-circle" color="green" />
        <x-stat-card title="Reprovações"       :value="$reprovacoes"        icon="fas fa-times-circle" color="red" />
    </div>

    {{-- ─── LINHA 1: Turma + Evolução (linha) ─── --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        <x-card title="Turma e contexto atual" icon="fas fa-school">
            @if($turma)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken);border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Turma</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->nome_completo }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">{{ $turma->classe }}ª classe</div>
                </div>
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken);border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Curso</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->curso->nome ?? 'Sem curso' }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Ano {{ $turma->anoLetivo->nome ?? $ano_letivo->nome }}</div>
                </div>
                <div class="rounded-2xl p-4 md:col-span-2" style="background:var(--surface-sunken);border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Diretor de turma</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->coordenador?->name ?? 'Não definido' }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Professor(a) responsável da turma</div>
                </div>
            </div>
            @else
            <div class="py-10 text-center">
                <i class="fas fa-school text-3xl mb-3" style="color:var(--tx-4);"></i>
                <div class="text-lg font-bold" style="color:var(--tx-1);">Sem turma ativa neste ano letivo</div>
                <p class="mt-2 text-sm max-w-xl mx-auto" style="color:var(--tx-3);">
                    O dashboard continua a mostrar o histórico abaixo, mas os dados do ano letivo atual dependem de uma matrícula ativa.
                </p>
            </div>
            @endif
        </x-card>

        <x-card title="Evolução temporal" icon="fas fa-chart-area">
            <div class="ad-chart-wrap" style="height:240px;">
                <canvas id="adLineChart"
                    role="img"
                    aria-label="Gráfico de linha mostrando a evolução da média ao longo das avaliações">
                    Gráfico de evolução da média por período letivo.
                </canvas>
            </div>
        </x-card>
    </div>

    {{-- ─── LINHA 2: Rosca + Barras por disciplina ─── --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <x-card title="Situação das disciplinas" icon="fas fa-pie-chart">
            {{-- Legenda manual --}}
            <div class="flex flex-wrap gap-3 mb-4 text-xs font-semibold" style="color:var(--tx-3);">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(99,153,34,0.85);"></span>Aprovadas
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(186,117,23,0.85);"></span>Pendentes
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(226,75,74,0.85);"></span>Reprovadas
                </span>
            </div>
            <div class="ad-chart-wrap" style="height:200px;">
                <canvas id="adPieChart"
                    role="img"
                    aria-label="Gráfico de rosca com a situação das disciplinas: aprovadas, pendentes e reprovadas">
                    Distribuição das disciplinas por situação.
                </canvas>
            </div>
        </x-card>

        <div class="xl:col-span-2">
            <x-card title="Notas por disciplina" icon="fas fa-chart-bar">
                <div class="flex flex-wrap gap-3 mb-4 text-xs font-semibold" style="color:var(--tx-3);">
                    <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(55,138,221,0.85);"></span>Muito Bom (≥14)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(99,153,34,0.85);"></span>Bom (10–13)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(186,117,23,0.85);"></span>Suficiente (8–9)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:rgba(226,75,74,0.85);"></span>Negativa (&lt;8)</span>
                </div>
                <div class="ad-chart-wrap" style="height:220px;">
                    <canvas id="adBarChart"
                        role="img"
                        aria-label="Gráfico de barras com a nota de cada disciplina">
                        Notas por disciplina.
                    </canvas>
                </div>
            </x-card>
        </div>
    </div>

    {{-- ─── DESEMPENHO DETALHADO ─── --}}
    <x-card title="Desempenho por disciplina" icon="fas fa-list-alt">
        @if($desempenho_disciplinas->isEmpty())
        <div class="py-10 text-center">
            <i class="fas fa-chart-bar text-3xl mb-3" style="color:var(--tx-4);"></i>
            <div class="text-lg font-bold" style="color:var(--tx-1);">Sem disciplinas para analisar</div>
            <p class="mt-2 text-sm" style="color:var(--tx-3);">Assim que a turma e as notas estiverem ligadas ao aluno, esta área será preenchida automaticamente.</p>
        </div>
        @else
        <div class="space-y-0">
            @foreach($desempenho_disciplinas as $item)
            <article class="py-4 @if(!$loop->last) border-b @endif" style="border-color:var(--border);">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex-1">
                        <h3 class="text-base font-bold" style="color:var(--tx-1);">
                            {{ $item['disciplina']->nome }}
                            <span class="text-xs font-semibold ml-1" style="color:var(--tx-4);">{{ $item['disciplina']->codigo }}</span>
                        </h3>
                        <p class="mt-1 text-xs" style="color:var(--tx-3);">
                            Prof. {{ $item['professor']?->name ?? 'Não associado' }}
                            · Coord. {{ $item['coordenador']?->name ?? 'Não definido' }}
                        </p>
                    </div>
                    <div class="text-left md:text-right shrink-0">
                        <div class="inline-flex rounded-full px-3 py-1.5 text-sm font-extrabold"
                            style="background:var(--info-bg);color:var(--info-tx);border:1px solid var(--info-bd);">
                            {{ $item['indicador']['valor'] !== null ? number_format($item['indicador']['valor'], 2) : '--' }}
                        </div>
                        <div class="mt-1.5 text-xs font-bold uppercase tracking-[0.14em]" style="color:var(--tx-4);">
                            {{ $item['indicador']['label'] }}
                        </div>
                    </div>
                </div>
                <div class="mt-3 ad-bar">
                    <span style="width:{{ $item['percentual'] }}%"></span>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </x-card>

</div>
@endsection
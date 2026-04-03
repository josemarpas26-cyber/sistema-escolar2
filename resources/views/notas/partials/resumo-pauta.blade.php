@php
    $estatisticasPauta = $estatisticasPauta ?? null;
@endphp

@if($estatisticasPauta)
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Resumo da pauta</h3>
            <p class="text-sm text-gray-500">Indicadores rapidos para acompanhar o lancamento por trimestre.</p>
        </div>
        <div class="text-right text-xs text-gray-500">
            <div>{{ $estatisticasPauta['total_registos'] }} registo(s)</div>
            <div>{{ $estatisticasPauta['finalizadas'] }} finalizada(s) | {{ $estatisticasPauta['bloqueadas'] }} com bloqueio</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visao geral</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800">{{ $estatisticasPauta['geral']['total_notas'] }}</p>
            <p class="mt-1 text-xs text-gray-500">notas lancadas nos tres trimestres</p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-green-100 px-2.5 py-1 text-green-700">
                    {{ $estatisticasPauta['geral']['pct_aprovacao'] }}% aprovacao
                </span>
                <span class="rounded-full bg-red-100 px-2.5 py-1 text-red-700">
                    {{ $estatisticasPauta['geral']['pct_reprovacao'] }}% reprovacao
                </span>
                @if($estatisticasPauta['geral']['media_geral'] !== null)
                <span class="rounded-full bg-teal-100 px-2.5 py-1 text-teal-700">
                    Media {{ number_format($estatisticasPauta['geral']['media_geral'], 1) }}
                </span>
                @endif
            </div>
        </div>

        @foreach($estatisticasPauta['trimestres'] as $trim)
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $trim['trimestre'] }}o Trimestre</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $trim['total'] }}</p>
            <p class="mt-1 text-xs text-gray-500">registo(s) com nota lancada</p>
            <div class="mt-3 space-y-1 text-xs text-gray-600">
                <div>Masc.: <strong>{{ $trim['masculino'] }}</strong> | Fem.: <strong>{{ $trim['feminino'] }}</strong></div>
                <div>Positivas: <strong class="text-green-700">{{ $trim['positivas'] }}</strong> | Negativas: <strong class="text-red-600">{{ $trim['negativas'] }}</strong></div>
                <div>Aprovacao: <strong>{{ $trim['pct_aprovacao'] }}%</strong></div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

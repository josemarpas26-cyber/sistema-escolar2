@extends('layouts.app')

@section('page-title', 'Estatisticas Academicas')

@section('header-actions')
<div class="flex items-center gap-2 text-sm text-gray-500">
    <i class="fas fa-calendar-alt"></i>
    {{ $anoLetivo->nome }}
</div>
@endsection

@section('content')
@php
    $trimCores = ['', 'bg-blue-50 text-blue-700', 'bg-teal-50 text-teal-700', 'bg-orange-50 text-orange-700'];
    $trimLabels = ['', '1o Trimestre', '2o Trimestre', '3o Trimestre'];
    $contextoLabels = [
        'professor' => 'Professor',
        'coord_turma' => 'Coordenacao de turma',
        'coord_curso' => 'Coordenacao de curso',
        'admin' => 'Administrativo',
    ];
@endphp

<div class="mb-6 flex flex-wrap gap-2">
    @foreach($contextos as $contexto)
    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
        {{ $contextoLabels[$contexto] ?? ucfirst($contexto) }}
    </span>
    @endforeach
</div>

<div class="space-y-8">
    @foreach($secoes as $secao)
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gradient-to-r from-slate-50 to-white px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-blue-600">
                        {{ $contextoLabels[$secao['tipo']] ?? ucfirst($secao['tipo']) }}
                    </p>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $secao['titulo'] }}</h2>
                    <p class="text-sm text-gray-500">{{ $secao['descricao'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-b border-gray-200 bg-gray-50 px-5 py-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total de notas</p>
                <p class="mt-2 text-2xl font-semibold text-blue-700">{{ number_format($secao['resumo']['total_notas']) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Aprovacao</p>
                <p class="mt-2 text-2xl font-semibold text-green-700">{{ $secao['resumo']['pct_aprovacao'] }}%</p>
                <p class="mt-1 text-xs text-gray-400">{{ $secao['resumo']['total_positivas'] }} positivas</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Reprovacao</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">{{ $secao['resumo']['pct_reprovacao'] }}%</p>
                <p class="mt-1 text-xs text-gray-400">{{ $secao['resumo']['total_negativas'] }} negativas</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Media geral</p>
                <p class="mt-2 text-2xl font-semibold text-teal-700">
                    {{ $secao['resumo']['media_geral'] !== null ? number_format($secao['resumo']['media_geral'], 1) : '-' }}
                </p>
            </div>
        </div>

        <div class="space-y-5 p-5">
            @if($secao['tipo'] === 'professor')
                @foreach($secao['itens'] as $item)
                <article class="overflow-hidden rounded-xl border border-gray-200">
                    <div class="border-b border-gray-200 bg-white px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ $item['disciplina']->nome }} - {{ $item['disciplina']->codigo }}
                                </h3>
                                <p class="text-sm text-gray-500">{{ $item['turma']->nome_completo }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700">
                                    {{ $item['resumo']['total_notas'] }} notas
                                </span>
                                <span class="rounded-full bg-green-50 px-3 py-1 text-green-700">
                                    {{ $item['resumo']['pct_aprovacao'] }}% aprovacao
                                </span>
                                <span class="rounded-full bg-red-50 px-3 py-1 text-red-700">
                                    {{ $item['resumo']['pct_reprovacao'] }}% reprovacao
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($item['trimestres']->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-gray-500">
                        Nenhuma nota lancada para esta pauta ate ao momento.
                    </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Trimestre</th>
                                    <th class="px-4 py-3 text-right font-medium">Total</th>
                                    <th class="px-4 py-3 text-right font-medium">Masc.</th>
                                    <th class="px-4 py-3 text-right font-medium">Fem.</th>
                                    <th class="px-4 py-3 text-right font-medium">Positivas</th>
                                    <th class="px-4 py-3 text-right font-medium">Negativas</th>
                                    <th class="px-4 py-3 font-medium" style="min-width: 150px">% Aprovacao</th>
                                    <th class="px-4 py-3 text-right font-medium">% Reprov.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($item['trimestres'] as $trim)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $trimCores[$trim['trimestre']] }}">
                                            {{ $trimLabels[$trim['trimestre']] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $trim['total'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $trim['masculino'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $trim['feminino'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-green-700">{{ $trim['positivas'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-red-600">{{ $trim['negativas'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 min-w-[80px] flex-1 overflow-hidden rounded-full bg-gray-100">
                                                <div
                                                    class="h-2 rounded-full"
                                                    style="width: {{ $trim['pct_aprovacao'] }}%; background: {{ $trim['pct_aprovacao'] >= 70 ? '#639922' : ($trim['pct_aprovacao'] >= 50 ? '#BA7517' : '#D85A30') }}">
                                                </div>
                                            </div>
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700">
                                                {{ $trim['pct_aprovacao'] }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $trim['pct_reprovacao'] > 30 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $trim['pct_reprovacao'] }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </article>
                @endforeach
            @else
                @foreach($secao['itens'] as $item)
                @php
                    $tituloItem = $item['turma']->nome_completo ?? $item['curso']->nome ?? 'Contexto';
                    $estatisticas = $item['estatisticas'];
                    $resumoItem = $item['resumo'];
                @endphp
                <article class="overflow-hidden rounded-xl border border-gray-200">
                    <div class="border-b border-gray-200 bg-white px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">{{ $tituloItem }}</h3>
                                @if(isset($item['turma']))
                                <p class="text-sm text-gray-500">
                                    {{ $estatisticas->count() }} disciplina(s) com estatisticas consolidadas
                                </p>
                                @elseif(isset($item['curso']))
                                <p class="text-sm text-gray-500">
                                    {{ $item['turmas']->count() }} turma(s) considerada(s) neste ano letivo
                                </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700">
                                    {{ $resumoItem['total_notas'] }} notas
                                </span>
                                <span class="rounded-full bg-green-50 px-3 py-1 text-green-700">
                                    {{ $resumoItem['pct_aprovacao'] }}% aprovacao
                                </span>
                                <span class="rounded-full bg-red-50 px-3 py-1 text-red-700">
                                    {{ $resumoItem['pct_reprovacao'] }}% reprovacao
                                </span>
                                @if($resumoItem['media_geral'] !== null)
                                <span class="rounded-full bg-teal-50 px-3 py-1 text-teal-700">
                                    Media {{ number_format($resumoItem['media_geral'], 1) }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($estatisticas->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-gray-500">
                        Nenhuma nota lancada para este contexto ate ao momento.
                    </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm" style="min-width: 760px">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Disciplina</th>
                                    <th class="px-4 py-3 text-center font-medium">Trimestre</th>
                                    <th class="px-4 py-3 text-right font-medium">Total</th>
                                    <th class="px-4 py-3 text-right font-medium">Masc.</th>
                                    <th class="px-4 py-3 text-right font-medium">Fem.</th>
                                    <th class="px-4 py-3 text-right font-medium">Positivas</th>
                                    <th class="px-4 py-3 text-right font-medium">Negativas</th>
                                    <th class="px-4 py-3 font-medium" style="min-width: 150px">% Aprovacao</th>
                                    <th class="px-4 py-3 text-right font-medium">% Reprov.</th>
                                    <th class="px-4 py-3 text-right font-medium">Media</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($estatisticas as $disc)
                                @foreach($disc['trimestres'] as $trim)
                                <tr class="hover:bg-gray-50">
                                    @if($loop->first)
                                    <td class="px-4 py-3" rowspan="{{ count($disc['trimestres']) }}" style="vertical-align: top; padding-top: 14px;">
                                        <span class="font-semibold text-gray-900">{{ $disc['disciplina']->nome }}</span>
                                        <span class="mt-0.5 block text-xs text-gray-400">{{ $disc['disciplina']->codigo }}</span>
                                    </td>
                                    @endif

                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $trimCores[$trim['trimestre']] }}">
                                            {{ $trimLabels[$trim['trimestre']] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $trim['total'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $trim['masculino'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $trim['feminino'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-green-700">{{ $trim['positivas'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-red-600">{{ $trim['negativas'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 min-w-[80px] flex-1 overflow-hidden rounded-full bg-gray-100">
                                                <div
                                                    class="h-2 rounded-full"
                                                    style="width: {{ $trim['pct_aprovacao'] }}%; background: {{ $trim['pct_aprovacao'] >= 70 ? '#639922' : ($trim['pct_aprovacao'] >= 50 ? '#BA7517' : '#D85A30') }}">
                                                </div>
                                            </div>
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700">
                                                {{ $trim['pct_aprovacao'] }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $trim['pct_reprovacao'] > 30 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $trim['pct_reprovacao'] }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        {{ $trim['media'] !== null ? number_format($trim['media'], 1) : '-' }}
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </article>
                @endforeach
            @endif
        </div>
    </section>
    @endforeach
</div>
@endsection

@extends('layouts.app')

@section('page-title', 'Ranking de Alunos')

@section('content')
@php
    $slides = collect([[
        'titulo' => 'Ranking Geral da Escola',
        'ranking' => $ranking_geral_escola->map(fn($item) => [
            'aluno_nome' => $item->aluno_nome,
            'media_geral' => (float) $item->media_geral,
        ])->values()->all(),
    ]])->merge(
        collect($rankings_carrossel)->map(fn($item) => [
            'titulo' => $item['titulo'],
            'ranking' => collect($item['ranking'])->map(fn($r) => [
                'aluno_nome' => $r->aluno_nome,
                'media_geral' => (float) $r->media_geral,
            ])->values()->all(),
        ])
    )->values();
@endphp

<div
    class="space-y-6"
    x-data="{
        slides: @js($slides),
        currentIndex: 0,
        secondsSinceGeneral: 0,
        init() {
            setInterval(() => {
                if (this.slides.length <= 1) return;
                this.secondsSinceGeneral += 7;

                if (this.secondsSinceGeneral >= 340) {
                    this.currentIndex = 0;
                    this.secondsSinceGeneral = 0;
                    return;
                }

                if (this.currentIndex === 0) {
                    this.currentIndex = 1;
                    return;
                }

                this.currentIndex = this.currentIndex + 1 >= this.slides.length ? 1 : this.currentIndex + 1;
            }, 7000);
        }
    }"
>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-lg font-semibold text-gray-900">Classificação Académica</h2>
        <p class="text-sm text-gray-500 mt-1">
            @if($ano_letivo)
                Ano letivo ativo: <span class="font-medium text-gray-700">{{ $ano_letivo->nome }}</span>
            @else
                Sem ano letivo ativo no momento.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr,340px] gap-6 items-start">
        <section class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Ranking móvel (carrossel)</h3>
                <span class="text-xs text-gray-400">Atualiza a cada 7s</span>
            </div>

            <template x-if="slides.length">
                <div>
                    <h4 class="text-lg font-bold text-primary-700 mb-3" x-text="slides[currentIndex].titulo"></h4>
                    <div class="space-y-2">
                        <template x-for="(aluno, i) in slides[currentIndex].ranking" :key="i">
                            <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center" x-text="'#' + (i + 1)"></span>
                                    <span class="truncate font-medium text-gray-700" x-text="aluno.aluno_nome"></span>
                                </div>
                                <span class="font-semibold text-primary-700" x-text="Number(aluno.media_geral).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </section>

        <aside class="hidden lg:block bg-white rounded-xl border border-gray-200 p-4 lg:sticky lg:top-24">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Ranking fixo</h3>
            <h4 class="text-base font-bold text-primary-700 mb-3">{{ $titulo_ranking_turma }}</h4>

            @if($ranking_turma_fixa->count())
                <div class="space-y-2">
                    @foreach($ranking_turma_fixa as $i => $item)
                        <div class="flex items-center justify-between text-sm border border-gray-100 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">#{{ $i + 1 }}</span>
                                <span class="truncate font-medium text-gray-700">{{ $item->aluno_nome }}</span>
                            </div>
                            <span class="font-semibold text-primary-700">{{ number_format($item->media_geral, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Sem dados suficientes para o ranking da turma.</p>
            @endif
        </aside>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">
            Regras ativas: o carrossel começa no ranking geral da escola, troca de tema a cada 7 segundos e volta ao ranking geral a cada 340 segundos.
        </p>
    </div>

    @include('ranking._filtros')
</div>
@endsection

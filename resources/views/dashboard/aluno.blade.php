@extends('layouts.app')

<<<<<<< HEAD
@section('page-title', 'Painel Académico')
=======
@section('page-title', 'Painel Acadêmico')
>>>>>>> 96a6942f08997eab38befefe27b0eba3ac662097

@section('header-actions')
<div class="flex flex-wrap gap-2">
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
<div class="aluno-dashboard space-y-6">
    <section class="rounded-3xl p-6 md:p-8" style="background:linear-gradient(135deg,var(--surface),var(--surface-sunken)); border:1px solid var(--border); box-shadow:var(--sh-sm);">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]" style="background:var(--info-bg); color:var(--info-tx); border:1px solid var(--info-bd);">
                    <i class="fas fa-user-graduate"></i>
                    Ano letivo {{ $ano_letivo->nome }}
                </div>
                <div class="space-y-2">
<<<<<<< HEAD
                    <h1 class="text-2xl md:text-4xl font-black leading-tight" style="color:var(--tx-1);">Resumo académico completo do aluno</h1>
                    <p class="max-w-3xl text-sm md:text-base" style="color:var(--tx-3);">
                         O painel mostra apenas os dados ligados à turma atual e as disciplinas realmente associadas a ela, incluindo professores, coordenação, avaliações completas, estatísticas e histórico.
=======
                    <h1 class="text-2xl md:text-4xl font-black leading-tight" style="color:var(--tx-1);">Olá, {{ auth()->user()->name ? explode(' ', auth()->user()->name)[0] : 'Usuário' }} </h1>
                    <p class="max-w-3xl text-sm md:text-base" style="color:var(--tx-3);">
                        Bem-vindo ao seu painel acadêmico! Aqui você acompanha o seu desempenho e evolução ao longo do tempo, resultados por disciplina e estatísticas geraisz  .
>>>>>>> 96a6942f08997eab38befefe27b0eba3ac662097
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
        <x-stat-card title="Média geral (CF)" :value="$disciplinas_com_resultado > 0 ? number_format($media_geral, 2) : '--'" icon="fas fa-chart-line" :color="$media_geral >= 10 ? 'green' : 'warning'" />
        <x-stat-card title="Média atual" :value="$media_atual !== null ? number_format($media_atual, 2) : '--'" icon="fas fa-wave-square" color="primary" />
        <x-stat-card title="Disciplinas" :value="$total_disciplinas" icon="fas fa-book" color="blue" />
        <x-stat-card title="Aprovacões" :value="$aprovacoes" icon="fas fa-check-circle" color="green" />
        <x-stat-card title="Reprovacões" :value="$reprovacoes" icon="fas fa-times-circle" color="red" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="Turma e contexto atual" icon="fas fa-school">
            @if($turma)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Turma</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->nome_completo }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">{{ $turma->classe }}ª classe</div>
                </div>
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Curso</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->curso->nome ?? 'Sem curso' }}</div>
                    <div class="mt-1 text-sm" style="color:var(--tx-3);">Ano {{ $turma->anoLetivo->nome ?? $ano_letivo->nome }}</div>
                </div>
                <div class="rounded-2xl p-4" style="background:var(--surface-sunken); border:1px solid var(--border);">
                    <div class="text-xs font-bold uppercase tracking-[0.16em]" style="color:var(--tx-4);">Diretor de turma</div>
                    <div class="mt-2 text-lg font-bold" style="color:var(--tx-1);">{{ $turma->coordenador?->name ?? 'Nao definido' }}</div>
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

        <x-card title="Evolucão temporal" icon="fas fa-chart-area">
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
                            · Coordenador: {{ $item['coordenador']?->name ?? 'Não definido' }}
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

</div>
@endsection

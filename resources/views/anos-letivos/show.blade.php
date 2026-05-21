@extends('layouts.app')

@section('page-title', 'Ano Letivo: ' . $anoLetivo->nome)

@section('header-actions')
@php($podeGerirEstadoAnoLetivo = auth()->user()?->isProgramador())
<div class="flex items-center gap-2">

    {{-- Voltar inteligente --}}
    <a href="{{ url()->previous() }}" 
       class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>
        Voltar
    </a>

    {{-- Encerrar Ano Letivo --}}
        @if($podeGerirEstadoAnoLetivo && $anoLetivo->ativo && !$anoLetivo->encerrado)
        <form action="{{ route('anos-letivos.encerrar', $anoLetivo) }}" 
              method="POST"
              onsubmit="return confirm('Tem a certeza de que deseja encerrar este ano letivo? Esta ação não poderá ser desfeita.')">
            @csrf


            <button type="submit" class="btn btn-danger">
                <i class="fas fa-lock mr-2"></i>
                Encerrar
            </button>
        </form>
    @endif

    {{-- Editar --}}
    @if(!$anoLetivo->encerrado)
        <a href="{{ route('anos-letivos.edit', $anoLetivo) }}" 
           class="btn btn-primary">
            <i class="fas fa-edit mr-2"></i>
            Editar
        </a>
    @endif

</div>
@endsection


@section('content')

<div class="space-y-6">

    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card 
            title="Turmas" 
            :value="$anoLetivo->turmas->count()" 
            icon="chalkboard" 
            color="blue" 
        />

        <x-stat-card 
            title="Alunos Matriculados" 
            :value="$anoLetivo->turmas->sum('total_alunos')" 
            icon="users" 
            color="green" 
        />

        <x-stat-card 
            title="Período" 
            :value="$anoLetivo->data_inicio->format('d/m/Y') . ' - ' . $anoLetivo->data_fim->format('d/m/Y')" 
            icon="calendar" 
            color="purple" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Informações --}}
        <div class="lg:col-span-1">
            <x-card title="Informações" icon="fas fa-info-circle">
                <div class="space-y-4">
                    <div>
                        <span class="text-gray-600 text-sm">Nome:</span>
                        <p class="font-semibold text-gray-900">
                            {{ $anoLetivo->nome }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-600 text-sm">Status:</span>
                        <div class="mt-1">
                            @if($anoLetivo->ativo)
                                <x-badge type="success">Ativo</x-badge>
                            @elseif($anoLetivo->encerrado)
                                <x-badge type="danger">Encerrado</x-badge>
                            @else
                                <x-badge type="gray">Inativo</x-badge>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-600 text-sm">Data Início:</span>
                        <p class="font-semibold">
                            {{ $anoLetivo->data_inicio->format('d/m/Y') }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-600 text-sm">Data Fim:</span>
                        <p class="font-semibold">
                            {{ $anoLetivo->data_fim->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Turmas --}}
        <div class="lg:col-span-2">
            <x-card title="Turmas do Ano Letivo" icon="fas fa-chalkboard">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Turma</th>
                                <th class="px-4 py-2 text-left">Curso</th>
                                <th class="px-4 py-2 text-center">Alunos</th>
                                <th class="px-4 py-2 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($anoLetivo->turmas as $turma)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        {{ $turma->nome_completo ?? $turma->nome }}
                                    </td>

                                    <td class="px-4 py-2">
                                        {{ $turma->curso->nome ?? '-' }}
                                    </td>

                                    <td class="px-4 py-2 text-center">
                                        {{ $turma->total_alunos ?? $turma->alunos->count() }}
                                    </td>

                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('turmas.show', $turma) }}" 
                                           class="text-primary-600 hover:text-primary-800 font-medium">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        Nenhuma turma associada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

    </div>


    @if($anoLetivo->configuracaoAvaliacao)
    <x-card title="Configuração de Avaliação" icon="fas fa-sliders-h">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-sm">
            <div><span class="text-gray-600">Peso da PG:</span> <strong>{{ number_format($anoLetivo->configuracaoAvaliacao->peso_pg, 2, ',', '.') }}%</strong></div>
            <div><span class="text-gray-600">Nota mínima:</span> <strong>{{ number_format($anoLetivo->configuracaoAvaliacao->nota_minima_aprovacao, 2, ',', '.') }}</strong></div>
        </div>
        @foreach([1,2,3] as $periodo)
            @php
                $provas = $anoLetivo->configuracaoAvaliacao->provas->where('periodo', $periodo)->sortBy('ordem');
                $somaAtivas = (float) $provas->where('ativo', true)->sum('peso');
            @endphp
            <div class="mb-4">
                <h4 class="font-semibold mb-2">{{ $periodo }}º Trimestre</h4>
                <table class="min-w-full text-sm border">
                    <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Prova</th><th class="px-3 py-2 text-left">Código</th><th class="px-3 py-2 text-right">Peso</th><th class="px-3 py-2 text-right">Peso Normalizado</th><th class="px-3 py-2 text-center">Status</th></tr></thead>
                    <tbody>
                        @foreach($provas as $prova)
                            @php
                                $pesoNormalizado = $prova->ativo && $somaAtivas > 0 ? ((float) $prova->peso / $somaAtivas) * 100 : 0;
                            @endphp
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ $prova->nome }}</td>
                                <td class="px-3 py-2"><code>{{ $prova->codigo }}</code></td>
                                <td class="px-3 py-2 text-right">{{ number_format($prova->peso, 4, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($pesoNormalizado, 2, ',', '.') }}%</td>
                                <td class="px-3 py-2 text-center">{!! $prova->ativo ? '<span class="text-green-600 font-semibold">Ativa</span>' : '<span class="text-gray-500">Inativa</span>' !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </x-card>
    @endif

    @if(!$anoLetivo->ativo)
    <x-card title="Histórico detalhado do ano letivo" icon="fas fa-history">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div><span class="text-gray-600 text-sm">Turmas</span><p class="font-semibold text-lg">{{ $stats['total_turmas'] }}</p></div>
            <div><span class="text-gray-600 text-sm">Alunos matriculados</span><p class="font-semibold text-lg">{{ $stats['total_alunos'] }}</p></div>
            <div><span class="text-gray-600 text-sm">Notas lançadas</span><p class="font-semibold text-lg">{{ $stats['total_notas'] }}</p></div>
            <div><span class="text-gray-600 text-sm">Última atualização</span><p class="font-semibold text-lg">{{ $anoLetivo->updated_at?->format('d/m/Y H:i') }}</p></div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Turma</th>
                        <th class="px-4 py-2 text-left">Curso</th>
                        <th class="px-4 py-2 text-center">Alunos</th>
                        <th class="px-4 py-2 text-center">Disciplinas</th>
                        <th class="px-4 py-2 text-center">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($turmasDetalhadas as $turma)
                        <tr>
                            <td class="px-4 py-2">{{ $turma->nome_completo ?? $turma->nome }}</td>
                            <td class="px-4 py-2">{{ $turma->curso->nome ?? '-' }}</td>
                            <td class="px-4 py-2 text-center">{{ $turma->alunos_matriculados_count }}</td>
                            <td class="px-4 py-2 text-center">{{ $turma->disciplinas_count }}</td>
                            <td class="px-4 py-2 text-center">{{ $turma->notas_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Sem turmas neste ano letivo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
    @endif


    @if($anosAnterioresDetalhados->isNotEmpty())
    <x-card title="Anos letivos anteriores (histórico completo)" icon="fas fa-archive">
        <p class="text-sm text-gray-600 mb-4">Resumo completo para consulta rápida, sem necessidade de reativar anos letivos antigos.</p>
        <div class="space-y-6">
            @foreach($anosAnterioresDetalhados as $item)
                @php($anoAnterior = $item['ano'])
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4 text-sm">
                        <div><span class="text-gray-500">Ano</span><p class="font-semibold">{{ $anoAnterior->nome }}</p></div>
                        <div><span class="text-gray-500">Período</span><p class="font-semibold">{{ $anoAnterior->data_inicio->format('d/m/Y') }} - {{ $anoAnterior->data_fim->format('d/m/Y') }}</p></div>
                        <div><span class="text-gray-500">Turmas</span><p class="font-semibold">{{ $item['total_turmas'] }}</p></div>
                        <div><span class="text-gray-500">Alunos</span><p class="font-semibold">{{ $item['total_alunos'] }}</p></div>
                        <div><span class="text-gray-500">Notas</span><p class="font-semibold">{{ $item['total_notas'] }}</p></div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Turma</th>
                                    <th class="px-3 py-2 text-left">Curso</th>
                                    <th class="px-3 py-2 text-center">Alunos</th>
                                    <th class="px-3 py-2 text-center">Disciplinas</th>
                                    <th class="px-3 py-2 text-center">Notas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($anoAnterior->turmas as $turmaAnterior)
                                    <tr>
                                        <td class="px-3 py-2">{{ $turmaAnterior->nome_completo ?? $turmaAnterior->nome }}</td>
                                        <td class="px-3 py-2">{{ $turmaAnterior->curso->nome ?? '-' }}</td>
                                        <td class="px-3 py-2 text-center">{{ $turmaAnterior->alunos_matriculados_count }}</td>
                                        <td class="px-3 py-2 text-center">{{ $turmaAnterior->disciplinas_count }}</td>
                                        <td class="px-3 py-2 text-center">{{ $turmaAnterior->notas_count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Sem turmas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
    @endif

</div>

@endsection

@extends('layouts.app')

@section('page-title', 'Avaliações Contínuas')

@section('content')
<x-card class="mb-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-sm text-gray-600">Ano letivo: {{ $anoLetivo->nome }}</p>
            <h3 class="text-lg font-semibold text-gray-900">Avaliações contínuas por disciplina</h3>
            <h6 class="text-sm text-gray-600">Aqui são apresentadas as avaliações realizadas ao longo do período letivo, permitindo acompanhar o desempenho progressivo do aluno.</h6>
        </div>
        <a href="{{ route('notas.index') }}" class="btn btn-outline">
            <i class="fas fa-clipboard-list mr-2"></i>
            Ver notas completas
        </a>
    </div>
</x-card>

@if($notas->isEmpty())
    <x-card>
        <div class="text-center py-12">
            <i class="fas fa-list-check text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg mb-2">Sem avaliações contínuas no momento</p>
            <p class="text-gray-400 text-sm">As avaliações aparecerão aqui assim que forem lançadas.</p>
        </div>
    </x-card>
@else
    <div class="space-y-4">
        @foreach($notas as $nota)
            <x-card>
                <div class="flex items-start justify-between gap-4 flex-wrap mb-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900">{{ $nota->disciplina->nome }}</h4>
                        <p class="text-sm text-gray-500">{{ $nota->disciplina->codigo }}</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        @foreach([1,2,3] as $trimestre)
                            @php
                                $mac = $nota->{"mac{$trimestre}"};
                                $macClass = $mac === null
                                    ? 'bg-gray-100 text-gray-500'
                                    : ($mac >= 10 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
                            @endphp
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $macClass }}">
                                MAC T{{ $trimestre }}: {{ $mac !== null ? number_format($mac, 2) : '—' }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach([1,2,3] as $trimestre)
                        @php $itens = $nota->avaliacoesContinuas->where('trimestre', $trimestre); @endphp
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <div class="font-semibold text-gray-800 mb-2">{{ $trimestre }}º Trimestre</div>

                            @if($itens->isEmpty())
                                <p class="text-gray-400 text-sm">Sem avaliações lançadas.</p>
                            @else
                                <ul class="space-y-1.5">
                                    @foreach($itens as $avaliacao)
                                        @php $notaClass = $avaliacao->valor >= 10 ? 'text-green-600' : 'text-red-600'; @endphp
                                        <li class="flex items-center justify-between gap-2 border-b border-gray-100 pb-1 last:border-0 last:pb-0 text-sm">
                                            <span class="truncate text-gray-700">{{ $avaliacao->descricao ?: 'Avaliação contínua' }}</span>
                                            <span class="shrink-0 {{ $notaClass }} font-semibold">{{ number_format($avaliacao->valor, 2) }}</span>
                                            <span class="shrink-0 text-gray-500">{{ optional($avaliacao->data_avaliacao)->format('d/m/Y') ?? 's/ data' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endforeach
    </div>
@endif
@endsection

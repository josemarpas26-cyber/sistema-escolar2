@extends('layouts.app')

@section('page-title', 'Ranking de Alunos')

@section('content')
<div class="space-y-6">
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

    @include('dashboard.partials.ranking-alunos')
</div>
@endsection

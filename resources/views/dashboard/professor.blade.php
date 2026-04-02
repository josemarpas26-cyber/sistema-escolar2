@extends('layouts.app')

@section('page-title', 'Dashboard do Professor')

@section('content')

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <x-stat-card 
        title="Minhas Turmas" 
        :value="$total_turmas" 
        icon="fas fa-chalkboard"
        color="primary" 
    />

    <x-stat-card 
        title="Total de Alunos" 
        :value="$total_alunos" 
        icon="fas fa-user-graduate"
        color="green" 
    />

    <x-stat-card 
        title="Notas Pendentes" 
        :value="$notas_pendentes" 
        icon="fas fa-clipboard-list"
        color="warning" 
    />

</div>

<!-- Ano Letivo -->
@if($ano_letivo)
<div class="bg-primary-50 border border-primary-200 rounded-lg p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-calendar-alt text-primary-600 text-xl mr-3"></i>
        <div>
            <p class="text-sm text-primary-700 font-medium">Ano Letivo Ativo</p>
            <p class="text-lg font-bold text-primary-900">{{ $ano_letivo->nome }}</p>
        </div>
    </div>
</div>
@endif

<!-- Minhas Turmas -->
<x-card title="Minhas Turmas" icon="fas fa-chalkboard-teacher">
    @if($turmas->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($turmas as $turmaId => $atribuicoes)
            @php
                $turma = $atribuicoes->first()->turma;
                $disciplinas = $atribuicoes->pluck('disciplina');
            @endphp
            
            <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-500 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="font-semibold text-gray-900">{{ $turma->nome_completo }}</h4>
                        <p class="text-sm text-gray-500">{{ $turma->curso->nome }}</p>
                    </div>
                    <x-badge type="primary">{{ $turma->classe }}ª</x-badge>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-users w-5 mr-2"></i>
                        <span>{{ $turma->total_alunos }} alunos</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-book w-5 mr-2"></i>
                        <span class="font-medium">Disciplinas:</span>
                        <div class="ml-7 mt-1">
                            @foreach($disciplinas as $disciplina)
                            <x-badge type="info" class="mr-1 mb-1">{{ $disciplina->codigo }}</x-badge>
                            @endforeach
                        </div>
                    </div>
                </div>

                <a href="{{ route('turmas.show', $turma) }}" class="block w-full text-center py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                    Ver Detalhes
                </a>
            </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-8">
        <i class="fas fa-chalkboard text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">Você não está atribuído a nenhuma turma ainda</p>
    </div>
    @endif
</x-card>

<!-- Quick Actions -->
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <a href="{{ route('notas.index') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-primary-500 hover:bg-primary-50 transition-all">
            <i class="fas fa-clipboard-list text-2xl text-primary-600 mr-3"></i>
            <span class="font-medium text-gray-700">Lançar Notas</span>
        </a>

        <a href="{{ route('relatorios.index') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-green-500 hover:bg-green-50 transition-all">
            <i class="fas fa-file-alt text-2xl text-green-600 mr-3"></i>
            <span class="font-medium text-gray-700">Relatórios</span>
        </a>

        <a href="{{ route('turmas.index') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 transition-all">
            <i class="fas fa-list text-2xl text-blue-600 mr-3"></i>
            <span class="font-medium text-gray-700">Minhas Turmas</span>
        </a>

        <a href="{{ route('users.show', auth()->id()) }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-purple-500 hover:bg-purple-50 transition-all">
            <i class="fas fa-user text-2xl text-purple-600 mr-3"></i>
            <span class="font-medium text-gray-700">Meu Perfil</span>
        </a>

    </div>
</div>

@endsection

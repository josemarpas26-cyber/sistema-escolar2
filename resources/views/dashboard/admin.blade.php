@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <x-stat-card 
        title="Total de Usuários" 
        :value="$total_usuarios" 
        icon="fas fa-users"
        color="primary" 
    />

    <x-stat-card 
        title="Alunos" 
        :value="$total_alunos" 
        icon="fas fa-user-graduate"
        color="green" 
    />

    <x-stat-card 
        title="Professores" 
        :value="$total_professores" 
        icon="fas fa-chalkboard-teacher"
        color="blue" 
    />

    <x-stat-card 
        title="Turmas" 
        :value="$total_turmas" 
        icon="fas fa-school"
        color="purple" 
    />

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Ano Letivo Ativo -->
    <x-card title="Ano Letivo Ativo" icon="fas fa-calendar-alt">
        @if($ano_letivo_ativo)
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Nome:</span>
                <span class="font-semibold">{{ $ano_letivo_ativo->nome }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Início:</span>
                <span class="font-semibold">{{ $ano_letivo_ativo->data_inicio->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Fim:</span>
                <span class="font-semibold">{{ $ano_letivo_ativo->data_fim->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Status:</span>
                <x-badge type="{{ $ano_letivo_ativo->encerrado ? 'danger' : 'success' }}">
                    {{ $ano_letivo_ativo->encerrado ? 'Encerrado' : 'Ativo' }}
                </x-badge>
            </div>
        </div>
        @else
        <p class="text-gray-500 text-center py-4">Nenhum ano letivo ativo</p>
        @endif
    </x-card>

    <!-- Logs Recentes -->
    <x-card title="Alterações Recentes" icon="fas fa-history">
        @if($logs_recentes->count() > 0)
        <div class="space-y-3">
            @foreach($logs_recentes as $log)
            <div class="flex items-start space-x-3 pb-3 border-b border-gray-100 last:border-0">
                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-edit text-primary-600 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">
                        <span class="font-semibold">{{ $log->usuario->name }}</span> 
                        {{ $log->descricao_acao }} 
                        <span class="text-primary-600">{{ $log->descricao_campo }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $log->aluno->name }} - {{ $log->disciplina->nome }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $log->data_alteracao->diffForHumans() }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4 text-center">
            <a href="{{ route('logs.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                Ver todos os logs →
            </a>
        </div>
        @else
        <p class="text-gray-500 text-center py-4">Nenhum log recente</p>
        @endif
    </x-card>

</div>

<!-- Quick Actions -->
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        
        <a href="{{ route('users.create') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-primary-500 hover:bg-primary-50 transition-all">
            <i class="fas fa-user-plus text-2xl text-primary-600 mr-3"></i>
            <span class="font-medium text-gray-700">Novo Usuário</span>
        </a>

        <a href="{{ route('turmas.create') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-green-500 hover:bg-green-50 transition-all">
            <i class="fas fa-chalkboard text-2xl text-green-600 mr-3"></i>
            <span class="font-medium text-gray-700">Nova Turma</span>
        </a>

        <a href="{{ route('anos-letivos.create') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 transition-all">
            <i class="fas fa-calendar-plus text-2xl text-blue-600 mr-3"></i>
            <span class="font-medium text-gray-700">Novo Ano Letivo</span>
        </a>

        <a href="{{ route('logs.dashboard') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-purple-500 hover:bg-purple-50 transition-all">
            <i class="fas fa-chart-line text-2xl text-purple-600 mr-3"></i>
            <span class="font-medium text-gray-700">Dashboard Logs</span>
        </a>

    </div>
</div>

@endsection

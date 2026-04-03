@extends('layouts.app')

@section('page-title', 'Dashboard da Secretaria')

@section('content')

<!-- ================= STATS CARDS ================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <x-stat-card 
        title="Alunos Ativos" 
        :value="$total_alunos" 
        icon="fas fa-user-graduate"
        color="green"
        :href="route('users.alunos')"
    />

    <x-stat-card 
        title="Professores Ativos" 
        :value="$total_professores ?? 0" 
        icon="fas fa-chalkboard-teacher"
        color="blue"
        :href="route('users.professores')"
    />

    <x-stat-card 
        title="Turmas" 
        :value="$total_turmas" 
        icon="fas fa-school"
        color="purple"
        :href="route('turmas.index')"
    />

    <x-stat-card 
        title="Logs Hoje" 
        :value="$logs_hoje" 
        icon="fas fa-history"
        color="primary"
        :href="route('logs.index')"
    />

</div>


<!-- ================= GRID PRINCIPAL ================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Ano Letivo Ativo -->
    <x-card title="Ano Letivo Ativo" icon="fas fa-calendar-alt">
        @if($ano_letivo)

            <div class="space-y-3">

                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Nome:</span>
                    <span class="font-semibold">{{ $ano_letivo->nome }}</span>
                </div>

                @if($ano_letivo->data_inicio)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Início:</span>
                    <span class="font-semibold">
                        {{ $ano_letivo->data_inicio->format('d/m/Y') }}
                    </span>
                </div>
                @endif

                @if($ano_letivo->data_fim)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Fim:</span>
                    <span class="font-semibold">
                        {{ $ano_letivo->data_fim->format('d/m/Y') }}
                    </span>
                </div>
                @endif

                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Status:</span>

                    <x-badge type="{{ $ano_letivo->encerrado ? 'danger' : 'success' }}">
                        {{ $ano_letivo->encerrado ? 'Encerrado' : 'Ativo' }}
                    </x-badge>
                </div>

            </div>

        @else
            <p class="text-gray-500 text-center py-4">
                Nenhum ano letivo ativo
            </p>
        @endif
    </x-card>


    <!-- Logs Recentes -->
    <x-card title="Atividades Recentes" icon="fas fa-history">

        @if(isset($atividades_recentes) && $atividades_recentes->count())

            <div class="space-y-3">
                @foreach($atividades_recentes->take(5) as $log)
                    <div class="flex items-start space-x-3 pb-3 border-b border-gray-100 last:border-0">

                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-edit text-primary-600 text-xs"></i>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">
                                {{ $log->descricao }}
                            </p>

                            <p class="text-xs text-gray-400 mt-1">
                                {{ $log->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('logs.index') }}"
                   class="text-sm text-primary-900 hover:text-primary-500 font-medium">
                    Ver todos →
                </a>
            </div>

        @else
            <p class="text-gray-500 text-center py-4">
                Nenhuma atividade recente
            </p>
        @endif

    </x-card>

</div>


<!-- ================= QUICK ACTIONS ================= -->

@php
$actions = [
    [
        'label' => 'Novo Aluno',
        'route' => route('users.create'),
        'icon' => 'fas fa-user-plus',
        'color' => 'blue',
        'description' => 'Cadastrar novo aluno'
    ],
    [
        'label' => 'Nova Turma',
        'route' => route('turmas.create'),
        'icon' => 'fas fa-chalkboard',
        'color' => 'green',
        'description' => 'Criar nova turma'
    ],
    [
        'label' => 'Relatórios',
        'route' => route('relatorios.index'),
        'icon' => 'fas fa-chart-line',
        'color' => 'purple',
        'description' => 'Visualizar relatórios do sistema'
    ],
];

$colorClasses = [
    'blue' => [
        'bg' => 'bg-blue-100',
        'text' => 'text-blue-600',
        'gradient' => 'from-blue-100',
        'bar' => 'bg-blue-500',
    ],
    'green' => [
        'bg' => 'bg-green-100',
        'text' => 'text-green-600',
        'gradient' => 'from-green-100',
        'bar' => 'bg-green-500',
    ],
    'purple' => [
        'bg' => 'bg-purple-100',
        'text' => 'text-purple-600',
        'gradient' => 'from-purple-100',
        'bar' => 'bg-purple-500',
    ],
];
@endphp


<div class="mt-10">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">Ações Rápidas</h2>
        <span class="text-sm text-gray-400">{{ now()->format('d/m/Y') }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6">

        @foreach($actions as $action)

            @php
                $classes = $colorClasses[$action['color']] ?? $colorClasses['blue'];
            @endphp

            <a href="{{ $action['route'] }}"
               class="group relative p-6 bg-white rounded-xl border border-gray-200 shadow-sm 
                      hover:shadow-xl transition-all duration-300 
                      hover:-translate-y-2 overflow-hidden">

                <div class="absolute inset-0 bg-gradient-to-r {{ $classes['gradient'] }} 
                            to-transparent opacity-0 group-hover:opacity-100 
                            transition-opacity duration-300"></div>

                <div class="relative flex items-start space-x-4">

                    <div class="w-14 h-14 flex items-center justify-center 
                                rounded-xl {{ $classes['bg'] }} {{ $classes['text'] }}
                                text-2xl transition-transform duration-300 
                                group-hover:scale-110">
                        <i class="{{ $action['icon'] }}"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-800 text-lg">
                            {{ $action['label'] }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            {{ $action['description'] }}
                        </p>
                    </div>

                </div>

                <div class="absolute bottom-0 left-0 h-1 w-0 
                            {{ $classes['bar'] }}
                            group-hover:w-full transition-all duration-300"></div>

            </a>

        @endforeach

    </div>
</div>

@endsection
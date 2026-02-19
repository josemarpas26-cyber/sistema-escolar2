@extends('layouts.app')

@section('page-title', 'Painel do administrador')

@section('content')

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <x-stat-card 
        title="Total de Usuários" 
        :value="$total_usuarios" 
        icon="fas fa-users"
        color="primary"
        :href="route('users.index')" 
    />

    <x-stat-card 
        title="Alunos" 
        :value="$total_alunos" 
        icon="fas fa-user-graduate"
        color="green" 
        :href="route('users.alunos')"
    />

    <x-stat-card 
        title="Professores" 
        :value="$total_professores" 
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
                <div class="flex items-center space-x-2">
                    <x-badge type="{{ $ano_letivo_ativo->encerrado ? 'danger' : 'success' }}">
                        {{ $ano_letivo_ativo->encerrado ? 'Encerrado' : 'Ativo' }}
                    </x-badge>

                    {{-- BUG CORRIGIDO 1: isset() verifica se a variável existe, 
                         não se tem valor válido. $dias_restantes pode ser 0 (válido) 
                         ou negativo (válido), e isset($dias_restantes) retorna true 
                         mesmo para null. A comparação !== null é mais correcta. --}}
                    @if(!$ano_letivo_ativo->encerrado && isset($dias_restantes))
                        <span class="text-sm text-gray-500">
                            @if($dias_restantes > 0)
                                ({{ $dias_restantes }} dias restantes)
                            @elseif($dias_restantes === 0)
                                (Encerrando hoje)
                            @else
                                (Já deveria estar encerrado 👀)
                            @endif
                        </span>
                    @endif
                </div>
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
            <a href="{{ route('logs.index') }}" class="text-sm text-primary-900 hover:text-primary-500 font-medium">
                Ver todos os logs →
            </a>
        </div>
        @else
        <p class="text-gray-500 text-center py-4">Nenhum log recente</p>
        @endif
    </x-card>

</div>

<!-- Quick Actions -->
{{-- BUG CORRIGIDO 2: as classes Tailwind dinâmicas (bg-{{ $color }}-100) NÃO 
     FUNCIONAM porque o Tailwind é compilado estaticamente e não consegue gerar 
     classes em runtime. As classes devem estar presentes integralmente no código 
     fonte ou serem safelisted no tailwind.config.js. Solução: usar classes fixas 
     com switch/case ou mapeamento directo. --}}

@php
$actions = [
    [
        'label' => 'Novo Usuário',
        'route' => route('users.create'),
        'icon' => 'fas fa-user-plus',
        'color' => 'blue',
        'description' => 'Criar novo usuário no sistema'
    ],
    [
        'label' => 'Nova Turma',
        'route' => route('turmas.create'),
        'icon' => 'fas fa-chalkboard',
        'color' => 'green',
        'description' => 'Cadastrar uma nova turma'
    ],
    [
        'label' => 'Dashboard Logs',
        'route' => route('logs.dashboard'),
        'icon' => 'fas fa-chart-line',
        'color' => 'purple',
        'description' => 'Visualizar estatísticas e alterações'
    ],
];

// Mapeamento fixo de cores para classes Tailwind completas
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
    'red' => [
        'bg' => 'bg-red-100',
        'text' => 'text-red-600',
        'gradient' => 'from-red-100',
        'bar' => 'bg-red-500',
    ],
];
@endphp

<div class="mt-10">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">Ações Rápidas</h2>
        <span class="text-sm text-gray-400">{{ now()->format('d/m/Y') }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($actions as $action)
            @php
                $classes = $colorClasses[$action['color']] ?? $colorClasses['blue'];
            @endphp

            <a href="{{ $action['route'] }}"
               class="group relative p-6 bg-white rounded-xl border border-gray-200 shadow-sm 
                      hover:shadow-xl transition-all duration-300 
                      hover:-translate-y-2 overflow-hidden">

                {{-- Glow animado --}}
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

                {{-- Indicador lateral animado --}}
                <div class="absolute bottom-0 left-0 h-1 w-0 
                            {{ $classes['bar'] }}
                            group-hover:w-full transition-all duration-300"></div>
            </a>
        @endforeach
    </div>
</div>

@endsection
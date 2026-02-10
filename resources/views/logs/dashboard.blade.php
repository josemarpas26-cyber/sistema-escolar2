@extends('layouts.app')

@section('page-title', 'Dashboard de Logs')

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('logs.index') }}" class="btn btn-outline">
        <i class="fas fa-list mr-2"></i>
        Ver Lista
    </a>
    <a href="{{ route('logs.exportar') }}" class="btn btn-primary">
        <i class="fas fa-download mr-2"></i>
        Exportar CSV
    </a>
</div>
@endsection

@section('content')

<!-- Estatísticas Gerais -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    
    <x-stat-card 
        title="Total de Logs" 
        :value="$totalLogs"
        icon="fas fa-clipboard-list"
        color="primary"
    />
    
    <x-stat-card 
        title="Hoje" 
        :value="$logsHoje"
        icon="fas fa-calendar-day"
        color="green"
    />
    
    <x-stat-card 
        title="Esta Semana" 
        :value="$logsSemana"
        icon="fas fa-calendar-week"
        color="blue"
    />
    
    <x-stat-card 
        title="Este Mês" 
        :value="$logsMes"
        icon="fas fa-calendar-alt"
        color="purple"
    />

</div>

<!-- Gráficos e Análises -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- Logs por Ação -->
    <x-card title="Logs por Tipo de Ação" icon="fas fa-chart-pie">
        <div class="space-y-3">
            @foreach($logsPorAcao as $acao => $quantidade)
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full mr-3 {{ $acao == 'created' ? 'bg-green-500' : ($acao == 'updated' ? 'bg-blue-500' : 'bg-red-500') }}"></div>
                    <span class="font-medium text-gray-700">
                        {{ ucfirst($acao) }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $acao == 'created' ? 'bg-green-500' : ($acao == 'updated' ? 'bg-blue-500' : 'bg-red-500') }}" 
                             style="width: {{ ($quantidade / $totalLogs) * 100 }}%"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-900 w-12 text-right">{{ $quantidade }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </x-card>

    <!-- Top Usuários -->
    <x-card title="Usuários Mais Ativos" icon="fas fa-users">
        <div class="space-y-3">
            @foreach($topUsuarios as $usuarioData)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-primary-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $usuarioData->usuario->name }}</p>
                        <p class="text-xs text-gray-500">{{ $usuarioData->usuario->role->display_name }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-primary-600">{{ $usuarioData->total }}</div>
                    <div class="text-xs text-gray-500">alterações</div>
                </div>
            </div>
            @endforeach

            @if($topUsuarios->isEmpty())
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-user-slash text-3xl mb-2"></i>
                <p>Nenhum dado disponível</p>
            </div>
            @endif
        </div>
    </x-card>

</div>

<!-- Logs Recentes e Disciplinas Mais Editadas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- Logs Recentes -->
    <x-card title="Alterações Recentes" icon="fas fa-clock">
        <div class="space-y-2">
            @foreach($logsRecentes as $log)
            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                {{ $log->acao == 'created' ? 'bg-green-100 text-green-600' : 
                                   ($log->acao == 'updated' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600') }}">
                        <i class="fas fa-{{ $log->acao == 'created' ? 'plus' : ($log->acao == 'updated' ? 'edit' : 'trash') }} text-xs"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        {{ $log->usuario->name }}
                    </p>
                    <p class="text-xs text-gray-600">
                        {{ ucfirst($log->acao) }} nota de 
                        <span class="font-medium">{{ $log->aluno->name }}</span>
                        em {{ $log->disciplina->nome }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-clock mr-1"></i>
                        {{ $log->data_alteracao->diffForHumans() }}
                    </p>
                </div>
            </div>
            @endforeach

            @if($logsRecentes->isEmpty())
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2"></i>
                <p>Nenhum log recente</p>
            </div>
            @endif
        </div>
    </x-card>

    <!-- Disciplinas Mais Editadas -->
    <x-card title="Disciplinas Mais Editadas" icon="fas fa-book">
        <div class="space-y-3">
            @foreach($topDisciplinas as $discData)
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ $discData->disciplina->nome }}</p>
                    <p class="text-xs text-gray-500">{{ $discData->disciplina->codigo }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-24 bg-gray-200 rounded-full h-2">
                        <div class="bg-primary-500 h-2 rounded-full" 
                             style="width: {{ ($discData->total / $topDisciplinas->first()->total) * 100 }}%"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-900 w-10 text-right">{{ $discData->total }}</span>
                </div>
            </div>
            @endforeach

            @if($topDisciplinas->isEmpty())
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-book-open text-3xl mb-2"></i>
                <p>Nenhum dado disponível</p>
            </div>
            @endif
        </div>
    </x-card>

</div>

<!-- Atividade por Período -->
<x-card title="Atividade dos Últimos 7 Dias" icon="fas fa-chart-line">
    <div class="h-64 flex items-end justify-between space-x-2">
        @foreach($atividadeSemanal as $dia => $total)
        @php
            $altura = $atividadeSemanal->max() > 0 ? ($total / $atividadeSemanal->max()) * 100 : 0;
        @endphp
        <div class="flex-1 flex flex-col items-center">
            <div class="w-full bg-primary-500 rounded-t hover:bg-primary-600 transition-colors cursor-pointer relative group" 
                 style="height: {{ $altura }}%">
                <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    {{ $total }} alterações
                </div>
            </div>
            <div class="text-xs text-gray-600 mt-2 text-center">
                {{ \Carbon\Carbon::parse($dia)->format('d/m') }}
            </div>
        </div>
        @endforeach
    </div>
</x-card>

<!-- Filtros Rápidos -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="{{ route('logs.index', ['acao' => 'created']) }}" 
       class="bg-green-50 border border-green-200 rounded-lg p-4 hover:bg-green-100 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-green-800 font-semibold">Notas Criadas</div>
                <div class="text-sm text-green-600">Ver todas as criações</div>
            </div>
            <i class="fas fa-plus-circle text-3xl text-green-500"></i>
        </div>
    </a>

    <a href="{{ route('logs.index', ['acao' => 'updated']) }}" 
       class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-blue-800 font-semibold">Notas Editadas</div>
                <div class="text-sm text-blue-600">Ver todas as edições</div>
            </div>
            <i class="fas fa-edit text-3xl text-blue-500"></i>
        </div>
    </a>

    <a href="{{ route('logs.index', ['acao' => 'deleted']) }}" 
       class="bg-red-50 border border-red-200 rounded-lg p-4 hover:bg-red-100 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-red-800 font-semibold">Notas Deletadas</div>
                <div class="text-sm text-red-600">Ver todas as exclusões</div>
            </div>
            <i class="fas fa-trash-alt text-3xl text-red-500"></i>
        </div>
    </a>
</div>

@endsection
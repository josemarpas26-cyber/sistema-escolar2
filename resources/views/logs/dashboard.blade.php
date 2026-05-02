@extends('layouts.app')

@section('page-title', 'Dashboard de Logs')

@php
    $metaAcao = [
        'criacao' => ['label' => 'Criacoes', 'dot' => 'bg-green-500', 'bar' => 'bg-green-500', 'icon' => 'plus', 'chip' => 'bg-green-100 text-green-600'],
        'edicao' => ['label' => 'Edicoes', 'dot' => 'bg-blue-500', 'bar' => 'bg-blue-500', 'icon' => 'edit', 'chip' => 'bg-blue-100 text-blue-600'],
        'exclusao' => ['label' => 'Exclusoes', 'dot' => 'bg-red-500', 'bar' => 'bg-red-500', 'icon' => 'trash', 'chip' => 'bg-red-100 text-red-600'],
    ];
@endphp

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('logs.index') }}" class="btn btn-outline">
        <i class="fas fa-list mr-2"></i>
        Ver Lista
    </a>
    <a href="{{ route('logs.exportar', ['contexto' => 'dashboard']) }}" class="btn btn-primary">
        <i class="fas fa-download mr-2"></i>
        Exportar XLSX
    </a>
</div>
@endsection

@section('content')
<div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
    <x-stat-card title="Total de Logs" :value="$totalLogs" icon="fas fa-clipboard-list" color="primary" />
    <x-stat-card title="Hoje" :value="$logsHoje" icon="fas fa-calendar-day" color="green" />
    <x-stat-card title="Esta Semana" :value="$logsSemana" icon="fas fa-calendar-week" color="blue" />
    <x-stat-card title="Este Mes" :value="$logsMes" icon="fas fa-calendar-alt" color="purple" />
</div>

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <x-card title="Logs por Acao" icon="fas fa-chart-pie">
        <div class="space-y-3">
            @foreach($logsPorAcao as $acao => $quantidade)
                @php
                    $item = $metaAcao[$acao] ?? ['label' => ucfirst($acao), 'dot' => 'bg-gray-400', 'bar' => 'bg-gray-400', 'icon' => 'circle', 'chip' => 'bg-gray-100 text-gray-600'];
                @endphp
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="mr-3 h-3 w-3 rounded-full {{ $item['dot'] }}"></div>
                        <span class="font-medium text-gray-700">{{ $item['label'] }}</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="h-2 w-32 rounded-full bg-gray-200">
                            <div class="h-2 rounded-full {{ $item['bar'] }}" style="width: {{ $totalLogs > 0 ? ($quantidade / $totalLogs) * 100 : 0 }}%"></div>
                        </div>
                        <span class="w-12 text-right text-sm font-bold text-gray-900">{{ $quantidade }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card title="Utilizadores Mais Ativos" icon="fas fa-users">
        <div class="space-y-3">
            @forelse($topUsuarios as $usuarioData)
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100">
                            <i class="fas fa-user text-primary-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ optional($usuarioData->usuario)->name ?? 'Sistema' }}</p>
                            <p class="text-xs text-gray-500">{{ optional(optional($usuarioData->usuario)->role)->display_name ?? 'Sem função' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-primary-600">{{ $usuarioData->total }}</div>
                        <div class="text-xs text-gray-500">alteracoes</div>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-gray-500">
                    <i class="fas fa-user-slash mb-2 text-3xl"></i>
                    <p>Nenhum dado disponivel</p>
                </div>
            @endforelse
        </div>
    </x-card>
</div>

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <x-card :no-pad="true">
        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                <i class="fas fa-clock text-sm"></i>
            </span>
            <h3 class="text-sm font-bold uppercase tracking-[0.08em] text-slate-700">Alteracoes Recentes</h3>
        </div>
        <div>
            @forelse($logsRecentes as $log)
                @php
                    $item = $metaAcao[$log->acao] ?? ['icon' => 'circle', 'chip' => 'bg-gray-100 text-gray-600'];
                @endphp
                <div class="flex items-start gap-3 border-b border-slate-100 px-6 py-4 transition-colors hover:bg-gray-50">
                    <div class="flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $item['chip'] }}">
                            <i class="fas fa-{{ $item['icon'] }} text-xs"></i>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ optional($log->usuario)->name ?? 'Sistema' }}</p>
                        <p class="text-xs text-gray-600">
                            {{ $log->descricao_acao }} {{ $log->descricao_campo }}
                            de <span class="font-medium">{{ $log->alvo_exibicao }}</span>
                            em {{ optional($log->disciplina)->nome ?? '-' }}
                        </p>
                        @if($log->motivo)
                            <p class="mt-1 text-xs text-amber-700">Motivo: {{ $log->motivo }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-clock mr-1"></i>
                            {{ $log->data_alteracao->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox mb-2 text-3xl"></i>
                    <p>Nenhum log recente</p>
                </div>
            @endforelse
        </div>
    </x-card>

    <x-card title="Disciplinas Mais Editadas" icon="fas fa-book">
        <div class="space-y-0">
            @forelse($topDisciplinas as $discData)
                @php
                    $max = max($topDisciplinas->first()->total ?? 0, 1);
                    $percent = min(100, ($discData->total / $max) * 100);
                @endphp
                <div class="flex items-center justify-between py-3 @if(!$loop->last) border-b border-slate-100 @endif">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">{{ $discData->disciplina->nome }}</p>
                        <p class="text-xs text-gray-500">{{ $discData->disciplina->codigo }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="h-2 w-24 rounded-full bg-gray-200">
                            <div class="h-2 rounded-full bg-primary-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <span class="w-10 text-right text-sm font-bold text-gray-900">{{ $discData->total }}</span>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-gray-500">
                    <i class="fas fa-book-open mb-2 text-3xl"></i>
                    <p>Nenhum dado disponivel</p>
                </div>
            @endforelse
        </div>
    </x-card>
</div>

<x-card title="Atividade dos Ultimos 7 Dias" icon="fas fa-chart-line">
    <canvas id="atividadeChart" width="400" height="200"></canvas>
</x-card>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('atividadeChart').getContext('2d');
    const atividadeData = @json($atividadeSemanal);
    const labels = Object.keys(atividadeData).map(date => {
        const d = new Date(date);
        return d.getDate() + '/' + (d.getMonth() + 1);
    });
    const data = Object.values(atividadeData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Alterações',
                data: data,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
    <a href="{{ route('logs.index', ['acao' => 'criacao']) }}" class="rounded-lg border border-green-200 bg-green-50 p-5 transition-colors hover:bg-green-100">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-semibold text-green-800">Notas Criadas</div>
                <div class="text-sm text-green-600">Ver todas as criacoes</div>
            </div>
            <i class="fas fa-plus-circle text-3xl text-green-500"></i>
        </div>
    </a>

    <a href="{{ route('logs.index', ['acao' => 'edicao']) }}" class="rounded-lg border border-blue-200 bg-blue-50 p-5 transition-colors hover:bg-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-semibold text-blue-800">Notas Editadas</div>
                <div class="text-sm text-blue-600">Ver todas as edicoes</div>
            </div>
            <i class="fas fa-edit text-3xl text-blue-500"></i>
        </div>
    </a>

     <a href="{{ route('logs.index', ['acao' => 'exclusao']) }}" class="rounded-lg border border-red-200 bg-red-50 p-5 transition-colors hover:bg-red-100">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-semibold text-red-800">Notas Removidas</div>
                <div class="text-sm text-red-600">Ver todas as exclusoes</div>
            </div>
            <i class="fas fa-trash-alt text-3xl text-red-500"></i>
        </div>
    </a>
</div>
@endsection

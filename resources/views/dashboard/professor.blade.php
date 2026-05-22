@extends('layouts.app')

@section('page-title', 'Dashboard do Professor')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card title="Minhas Turmas" :value="$total_turmas" icon="fas fa-chalkboard" color="primary" />
        <x-stat-card title="Total de Alunos" :value="$total_alunos" icon="fas fa-user-graduate" color="green" />
        <x-stat-card title="Notas Pendentes" :value="$notas_pendentes" icon="fas fa-clipboard-list" color="warning" />
    </div>

    @if($ano_letivo)
    <div class="rounded-xl border border-primary-200 bg-gradient-to-r from-primary-50 to-white p-4">
        <div class="flex items-center gap-3">
            <i class="fas fa-calendar-alt text-primary-600 text-xl"></i>
            <div>
                <p class="text-xs uppercase tracking-wide text-primary-700 font-semibold">Ano Letivo Ativo</p>
                <p class="text-lg font-bold text-primary-900">{{ $ano_letivo->nome }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <x-card title="Matrículas por Turma" icon="fas fa-chart-bar" class="xl:col-span-2">
            <div class="h-72"><canvas id="profBarChart"></canvas></div>
        </x-card>
        <x-card title="Distribuição por Curso" icon="fas fa-chart-pie">
            <div class="h-72"><canvas id="profPieChart"></canvas></div>
        </x-card>
    </div>

    <x-card title="Minhas Turmas" icon="fas fa-chalkboard-teacher">
        @if($turmas->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($turmas as $turmaId => $atribuicoes)
                @php
                    $turma = $atribuicoes->first()->turma;
                    $disciplinas = $atribuicoes->pluck('disciplina');
                @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $turma->nome_completo }}</h4>
                            <p class="text-sm text-gray-500">{{ $turma->curso->nome }}</p>
                        </div>
                        <x-badge type="primary">{{ $turma->classe }}ª</x-badge>
                    </div>
                    <div class="space-y-2 mb-4 text-sm text-gray-600">
                        <div class="flex items-center"><i class="fas fa-users w-5 mr-2"></i><span>{{ $turma->total_alunos }} alunos</span></div>
                        <div>
                            <i class="fas fa-book w-5 mr-2"></i><span class="font-medium">Disciplinas:</span>
                            <div class="ml-7 mt-1">
                                @foreach($disciplinas as $disciplina)
                                    <x-badge type="info" class="mr-1 mb-1">{{ $disciplina->codigo }}</x-badge>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('turmas.show', $turma) }}" class="block w-full text-center py-2 rounded-lg bg-gray-100 hover:bg-primary-50 hover:text-primary-700 text-sm font-medium text-gray-700 transition-colors">Ver Detalhes</a>
                </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <i class="fas fa-chalkboard text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">O utilizador não está atribuído a nenhuma turma ainda</p>
        </div>
        @endif
    </x-card>
</div>
@endsection

@push('scripts')
<script>
const profBarData = @json($matriculas_por_turma);
const profPieData = @json($distribuicao_por_curso);

if (document.getElementById('profBarChart')) {
  new Chart(document.getElementById('profBarChart'), {
    type: 'bar',
    data: {
      labels: profBarData.map(item => item.label),
      datasets: [{ label: 'Alunos', data: profBarData.map(item => item.value), backgroundColor: '#3b82f6', borderRadius: 8 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
}

if (document.getElementById('profPieChart')) {
  new Chart(document.getElementById('profPieChart'), {
    type: 'pie',
    data: {
      labels: profPieData.map(item => item.label),
      datasets: [{ data: profPieData.map(item => item.value), backgroundColor: ['#2563eb', '#16a34a', '#7c3aed', '#f59e0b', '#db2777', '#0d9488'] }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
}
</script>
@endpush

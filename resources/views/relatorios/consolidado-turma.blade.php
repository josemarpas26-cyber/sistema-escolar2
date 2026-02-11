@extends('layouts.app')

@section('page-title', 'Consolidado da Turma')

@section('content')

<x-card title="Consolidado - {{ $turma->nome_completo }}" icon="fas fa-chart-bar">
    
    <!-- Info da Turma -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <span class="text-gray-600 text-sm">Curso:</span>
                <p class="font-semibold">{{ $turma->curso->nome }}</p>
            </div>
            <div>
                <span class="text-gray-600 text-sm">Classe:</span>
                <p class="font-semibold">{{ $turma->classe }}ª</p>
            </div>
            <div>
                <span class="text-gray-600 text-sm">Total Alunos:</span>
                <p class="font-semibold">{{ $turma->total_alunos }}</p>
            </div>
            <div>
                <span class="text-gray-600 text-sm">Ano Letivo:</span>
                <p class="font-semibold">{{ $turma->anoLetivo->nome }}</p>
            </div>
        </div>
    </div>

    <!-- Estatísticas por Disciplina -->
    <h3 class="text-lg font-semibold mb-4">Desempenho por Disciplina</h3>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Média Turma</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aprovados</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Reprovados</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Taxa</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($turma->disciplinas as $disciplina)
                @php
                    $notas = $turma->notas->where('disciplina_id', $disciplina->id)->where('cfd', '!=', null);
                    $media = $notas->avg('cfd');
                    $aprovados = $notas->filter(fn($n) => $n->isAprovado())->count();
                    $reprovados = $notas->count() - $aprovados;
                    $taxa = $notas->count() > 0 ? ($aprovados / $notas->count()) * 100 : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $disciplina->nome }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold {{ $media >= 10 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $media ? number_format($media, 2) : '-' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-green-600 font-semibold">{{ $aprovados }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-red-600 font-semibold">{{ $reprovados }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold {{ $taxa >= 70 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($taxa, 1) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Estatísticas Gerais -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        @php
            $todasNotas = $turma->notas->where('cfd', '!=', null);
            $mediaGeral = $todasNotas->avg('cfd');
            $totalAprovados = $todasNotas->filter(fn($n) => $n->isAprovado())->count();
            $totalReprovados = $todasNotas->count() - $totalAprovados;
        @endphp
        
        <div class="bg-primary-50 rounded-lg p-4 text-center">
            <div class="text-sm text-gray-600 mb-1">Média Geral da Turma</div>
            <div class="text-3xl font-bold text-primary-600">
                {{ $mediaGeral ? number_format($mediaGeral, 2) : '-' }}
            </div>
        </div>
        
        <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-sm text-gray-600 mb-1">Total de Aprovações</div>
            <div class="text-3xl font-bold text-green-600">{{ $totalAprovados }}</div>
        </div>
        
        <div class="bg-red-50 rounded-lg p-4 text-center">
            <div class="text-sm text-gray-600 mb-1">Total de Reprovações</div>
            <div class="text-3xl font-bold text-red-600">{{ $totalReprovados }}</div>
        </div>
    </div>

    <!-- Ações -->
    <div class="mt-6 flex space-x-3">
        <a href="{{ route('relatorios.consolidado', [$turma, 'formato' => 'pdf']) }}" 
           class="btn btn-primary" target="_blank">
            <i class="fas fa-file-pdf mr-2"></i>
            Baixar PDF
        </a>
        <a href="{{ route('turmas.show', $turma) }}" class="btn btn-outline">
            <i class="fas fa-arrow-left mr-2"></i>
            Voltar à Turma
        </a>
    </div>

</x-card>

@endsection
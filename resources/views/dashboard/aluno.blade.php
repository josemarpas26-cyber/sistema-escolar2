@extends('layouts.app')

@section('page-title', 'Meu Dashboard')

@section('content')

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    
    <x-stat-card 
        title="Média Geral" 
        :value="number_format($media_geral, 2)" 
        icon="fas fa-chart-line"
        color="primary" 
    />

    <x-stat-card 
        title="Disciplinas" 
        :value="$total_disciplinas" 
        icon="fas fa-book"
        color="blue" 
    />

    <x-stat-card 
        title="Aprovações" 
        :value="$aprovacoes" 
        icon="fas fa-check-circle"
        color="success" 
    />

    <x-stat-card 
        title="Reprovações" 
        :value="$reprovacoes" 
        icon="fas fa-times-circle"
        color="danger" 
    />

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Minha Turma -->
    <x-card title="Minha Turma" icon="fas fa-school" class="lg:col-span-1">
        @if($turma)
        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">Turma</p>
                <p class="text-lg font-bold text-gray-900">{{ $turma->nome_completo }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Curso</p>
                <p class="font-semibold text-gray-900">{{ $turma->curso->nome }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Classe</p>
                <x-badge type="primary" class="text-base">{{ $turma->classe }}ª Classe</x-badge>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Ano Letivo</p>
                <p class="font-semibold text-gray-900">{{ $turma->anoLetivo->nome }}</p>
            </div>
            @if($turma->coordenador)
            <div>
                <p class="text-sm text-gray-600 mb-1">Coordenador</p>
                <p class="font-semibold text-gray-900">{{ $turma->coordenador->name }}</p>
            </div>
            @endif
        </div>
        @else
        <p class="text-gray-500 text-center py-4">Você não está matriculado em nenhuma turma</p>
        @endif
    </x-card>

    <!-- Minhas Notas -->
    <x-card title="Minhas Notas" icon="fas fa-clipboard-list" class="lg:col-span-2">
        @if($notas->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT1</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT2</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT3</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">CFD</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($notas as $nota)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $nota->disciplina->nome }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            {{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            {{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            {{ $nota->mt3 ? number_format($nota->mt3, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center font-bold">
                            {{ $nota->cfd ? number_format($nota->cfd, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($nota->cfd)
                                <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}">
                                    {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                                </x-badge>
                            @else
                                <x-badge type="gray">Pendente</x-badge>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-center">
            <a href="{{ route('relatorios.boletim') }}" class="btn btn-primary">
                <i class="fas fa-download mr-2"></i>
                Baixar Boletim
            </a>
        </div>
        @else
        <div class="text-center py-8">
            <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Nenhuma nota lançada ainda</p>
        </div>
        @endif
    </x-card>

</div>

<!-- Quick Links -->
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Acesso Rápido</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <a href="{{ route('relatorios.boletim') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-primary-500 hover:bg-primary-50 transition-all">
            <i class="fas fa-file-pdf text-2xl text-primary-600 mr-3"></i>
            <span class="font-medium text-gray-700">Meu Boletim</span>
        </a>

        <a href="{{ route('relatorios.historico') }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-green-500 hover:bg-green-50 transition-all">
            <i class="fas fa-history text-2xl text-green-600 mr-3"></i>
            <span class="font-medium text-gray-700">Histórico Académico</span>
        </a>

        <a href="{{ route('users.show', auth()->id()) }}" class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 transition-all">
            <i class="fas fa-user text-2xl text-blue-600 mr-3"></i>
            <span class="font-medium text-gray-700">Meu Perfil</span>
        </a>

    </div>
</div>

@endsection

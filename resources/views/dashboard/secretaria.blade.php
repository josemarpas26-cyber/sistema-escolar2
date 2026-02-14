@extends('layouts.app')

@section('page-title', 'Dashboard da Secretaria')

@section('content')
<div class="space-y-6">
    {{-- Estatísticas principais --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card 
            title="Alunos ativos" 
            :value="$total_alunos" 
            icon="users" 
            color="blue"
            :trend="$trend_alunos ?? null"
        />
        <x-stat-card 
            title="Turmas no ano ativo" 
            :value="$total_turmas" 
            icon="chalkboard" 
            color="green"
            :subtitle="$ano_letivo?->nome"
        />
        <x-stat-card 
            title="Professores ativos" 
            :value="$total_professores ?? 0" 
            icon="chalkboard-teacher" 
            color="indigo"
        />
        <x-stat-card 
            title="Logs hoje" 
            :value="$logs_hoje" 
            icon="history" 
            color="purple"
            subtitle="Atividades registradas"
        />
    </div>

    {{-- Grid de duas colunas --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Turmas recentes (2/3 da largura) --}}
        <x-card class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Turmas recentes</h3>
                @if($turmas_recentes->count())
                    <a href="{{ route('turmas.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Ver todas →
                    </a>
                @endif
            </div>

            @if($turmas_recentes->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Turma
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Curso
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alunos
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($turmas_recentes as $turma)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $turma->nome_completo ?? $turma->nome }}
                                        </div>
                                        @if($turma->periodo)
                                            <div class="text-xs text-gray-500">
                                                {{ ucfirst($turma->periodo) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $turma->curso->nome ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $turma->alunos_count ?? $turma->alunos->count() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                        <a href="{{ route('turmas.show', $turma) }}" 
                                           class="text-blue-600 hover:text-blue-900 font-medium"
                                           title="Ver detalhes">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Nenhuma turma encontrada para o ano letivo ativo.</p>
                    @if(auth()->user()->can('criar-turmas'))
                        <a href="{{ route('turmas.create') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Criar primeira turma
                        </a>
                    @endif
                </div>
            @endif
        </x-card>

        {{-- Sidebar com informações adicionais (1/3 da largura) --}}
        <div class="space-y-6">
            {{-- Ano letivo ativo --}}
            <x-card>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Ano Letivo Ativo</h3>
                @if($ano_letivo)
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-blue-600">{{ $ano_letivo->nome }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Ativo
                            </span>
                        </div>
                        @if($ano_letivo->data_inicio && $ano_letivo->data_fim)
                            <p class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($ano_letivo->data_inicio)->format('d/m/Y') }} - 
                                {{ \Carbon\Carbon::parse($ano_letivo->data_fim)->format('d/m/Y') }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500 mb-3">Nenhum ano letivo ativo</p>
                        @if(auth()->user()->can('gerenciar-anos-letivos'))
                            <a href="{{ route('anos-letivos.create') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Criar ano letivo →
                            </a>
                        @endif
                    </div>
                @endif
            </x-card>

            {{-- Atividades recentes --}}
            @if(isset($atividades_recentes) && $atividades_recentes->count())
                <x-card>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Atividades Recentes</h3>
                    <div class="space-y-3">
                        @foreach($atividades_recentes->take(5) as $log)
                            <div class="flex items-start space-x-3 text-sm">
                                <div class="flex-shrink-0 mt-0.5">
                                    <div class="h-2 w-2 rounded-full bg-blue-400"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-gray-900 truncate">{{ $log->descricao }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- Ações rápidas --}}
            <x-card>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Ações Rápidas</h3>
                <div class="space-y-2">
                    @can('criar-alunos')
                        <a href="{{ route('alunos.create') }}" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                            + Novo Aluno
                        </a>
                    @endcan
                    @can('criar-turmas')
                        <a href="{{ route('turmas.create') }}" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                            + Nova Turma
                        </a>
                    @endcan
                    @can('visualizar-relatorios')
                        <a href="{{ route('relatorios.index') }}" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                            📊 Relatórios
                        </a>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
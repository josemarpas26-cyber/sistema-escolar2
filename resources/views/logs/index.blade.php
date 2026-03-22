@extends('layouts.app')
@section('page-title', 'Logs de Alterações')
@section('header-actions')
<a href="{{ route('logs.dashboard') }}" class="btn btn-outline">
    <i class="fas fa-chart-bar mr-2"></i>
    Dashboard
</a>
<a href="{{ route('logs.exportar', request()->query()) }}" class="btn btn-primary">
    <i class="fas fa-download mr-2"></i>
    Exportar CSV
</a>
@endsection

@section('content')

{{-- Filtros --}}
<x-card title="Filtros" icon="fas fa-filter" class="mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <input type="text" name="aluno"      value="{{ request('aluno') }}"      placeholder="Pesquisar aluno"      class="input">
        <input type="text" name="turma"      value="{{ request('turma') }}"      placeholder="Pesquisar turma"      class="input">
        <input type="text" name="curso"      value="{{ request('curso') }}"      placeholder="Pesquisar curso"      class="input">
        <input type="text" name="disciplina" value="{{ request('disciplina') }}" placeholder="Pesquisar disciplina" class="input">

        <select name="acao" class="input">
            <option value="">Todas as ações</option>
            <option value="criacao"  @selected(request('acao') === 'criacao') >Criação</option>
            <option value="edicao"   @selected(request('acao') === 'edicao')  >Edição</option>
            <option value="exclusao" @selected(request('acao') === 'exclusao')>Exclusão</option>
        </select>

        <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="input" placeholder="Data inicial">
        <input type="date" name="data_fim"    value="{{ request('data_fim') }}"    class="input" placeholder="Data final">

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary flex-1">Filtrar</button>
            <a href="{{ route('logs.index') }}" class="btn btn-outline">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</x-card>

{{-- Tabela --}}
<x-card>
    @if($logs->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Data/Hora</th>
                    <th class="px-4 py-2 text-left">Utilizador</th>
                    <th class="px-4 py-2 text-left">Ação</th>
                    <th class="px-4 py-2 text-left">Aluno</th>
                    <th class="px-4 py-2 text-left">Disciplina</th>
                    <th class="px-4 py-2 text-left">Campo</th>
                    <th class="px-4 py-2 text-left">Alteração</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-50">

                    {{-- Data --}}
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                        {{ $log->data_alteracao?->format('d/m/Y H:i') ?? '—' }}
                    </td>

                    {{-- Utilizador que fez a alteração --}}
                    <td class="px-4 py-3">
                        @if($log->usuario)
                            <span class="font-medium">{{ $log->usuario->name }}</span>
                            @if($log->usuario->trashed())
                                <x-badge type="danger" class="ml-1 text-xs">Deletado</x-badge>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    {{-- Tipo de ação --}}
                    <td class="px-4 py-3">
                        <x-badge type="{{ $log->tipo_badge_acao }}">
                            {{ $log->descricao_acao }}
                        </x-badge>
                    </td>

                    {{-- Aluno afetado --}}
                    <td class="px-4 py-3">
                        @if($log->aluno)
                            <span class="{{ $log->aluno->trashed() ? 'text-gray-400 line-through' : '' }}">
                                {{ $log->aluno->name }}
                            </span>
                            @if($log->aluno->trashed())
                                <x-badge type="danger" class="ml-1 text-xs">Deletado</x-badge>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    {{-- Disciplina --}}
                    <td class="px-4 py-3">
                        {{ optional($log->disciplina)->codigo ?? '—' }}
                    </td>

                    {{-- Campo alterado --}}
                    <td class="px-4 py-3">
                        {{ $log->descricao_campo }}
                    </td>

                    {{-- Resumo da alteração --}}
                    <td class="px-4 py-3 text-xs text-gray-600">
                        {{ $log->resumo_alteracao }}
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->withQueryString()->links() }}
    </div>

    @else
    <div class="text-center py-12">
        <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhum log encontrado para os filtros selecionados</p>
    </div>
    @endif
</x-card>

@endsection
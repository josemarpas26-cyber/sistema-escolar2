@extends('layouts.app')
@section('page-title', 'Logs de Alterações')
@section('header-actions')
<a href="{{ route('logs.dashboard') }}" class="btn btn-outline"><i class="fas fa-chart-bar mr-2"></i>Dashboard</a>
<a href="{{ route('logs.exportar') }}" class="btn btn-primary"><i class="fas fa-download mr-2"></i>Exportar CSV</a>
@endsection
@section('content')
<x-card title="Filtros" icon="fas fa-filter" class="mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <input type="text" name="aluno" value="{{ request('aluno') }}" placeholder="Pesquisar aluno" class="input">
        <input type="text" name="turma" value="{{ request('turma') }}" placeholder="Pesquisar turma" class="input">
        <input type="text" name="curso" value="{{ request('curso') }}" placeholder="Pesquisar curso" class="input">
        <input type="text" name="disciplina" value="{{ request('disciplina') }}" placeholder="Pesquisar disciplina" class="input">
        
        <select name="acao" class="input">
            <option value="">Todas as ações</option>
            <option value="criacao" @selected(request('acao') === 'criacao')>Criação</option>
            <option value="edicao" @selected(request('acao') === 'edicao')>Edição</option>
            <option value="exclusao" @selected(request('acao') === 'exclusao')>Exclusão</option>
        </select>
        <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="input" placeholder="Data inicial">
        <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="input" placeholder="Data final">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
</x-card>
<x-card>
    @if($logs->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Data/Hora</th>
                    <th class="px-4 py-2 text-left">Usuário</th>
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
                    <td class="px-4 py-3">{{ $log->data_alteracao->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $log->usuario->name }}</td>
                    <td class="px-4 py-3"><x-badge type="{{ $log->tipo_badge_acao }}">{{ $log->descricao_acao }}</x-badge></td>    
                    <td class="px-4 py-3">{{ $log->aluno->name }}</td>
                    <td class="px-4 py-3">{{ $log->disciplina->codigo }}</td>
                    <td class="px-4 py-3">{{ $log->descricao_campo }}</td>
                    <td class="px-4 py-3 text-xs">{{ $log->resumo_alteracao }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
    @else
    <p class="text-center text-gray-500 py-8">Nenhum log encontrado</p>
    @endif
</x-card>
@endsection

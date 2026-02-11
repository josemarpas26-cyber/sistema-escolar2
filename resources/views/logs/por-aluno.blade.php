@extends('layouts.app')
@section('page-title', 'Logs - ' . $aluno->name)
@section('content')
<x-card title="Logs do Aluno: {{ $aluno->name }}" icon="fas fa-user">
    @if($logs->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Data/Hora</th>
                    <th class="px-4 py-2 text-left">Usuário</th>
                    <th class="px-4 py-2 text-left">Ação</th>
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
                    <td class="px-4 py-3"><x-badge type="{{ $log->acao == 'created' ? 'success' : 'info' }}">{{ $log->acao }}</x-badge></td>
                    <td class="px-4 py-3">{{ $log->disciplina->nome }}</td>
                    <td class="px-4 py-3">{{ $log->campo_alterado }}</td>
                    <td class="px-4 py-3 text-xs">{{ $log->valor_anterior }} → {{ $log->valor_novo }}</td>
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
@extends('layouts.app')
@section('page-title', $curso->nome)
@section('header-actions')
<a href="{{ route('cursos.edit', $curso) }}" class="btn btn-primary"><i class="fas fa-edit mr-2"></i>Editar</a>
@endsection
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-card title="Informações do Curso" icon="fas fa-info-circle">
            <div class="space-y-3">
                <div><span class="text-gray-600">Código:</span> <span class="font-bold text-lg">{{ $curso->codigo }}</span></div>
                <div><span class="text-gray-600">Nome:</span> <span class="font-semibold">{{ $curso->nome }}</span></div>
                @if($curso->descricao)<div><span class="text-gray-600">Descrição:</span> <p class="mt-1">{{ $curso->descricao }}</p></div>@endif
                @if($curso->coordenador)<div><span class="text-gray-600">Coordenador:</span> <a href="{{ route('users.show', $curso->coordenador) }}" class="text-primary-600">{{ $curso->coordenador->name }}</a></div>@endif
                <div><span class="text-gray-600">Status:</span> <x-badge type="{{ $curso->ativo ? 'success' : 'danger' }}">{{ $curso->ativo ? 'Ativo' : 'Inativo' }}</x-badge></div>
            </div>
        </x-card>
        @if($curso->turmas->count() > 0)
        <x-card title="Turmas do Curso" icon="fas fa-chalkboard" class="mt-6">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Turma</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Classe</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Alunos</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ano</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($curso->turmas as $turma)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><a href="{{ route('turmas.show', $turma) }}" class="text-primary-600">{{ $turma->nome }}</a></td>
                            <td class="px-4 py-3">{{ $turma->classe }}ª</td>
                            <td class="px-4 py-3">{{ $turma->total_alunos }}</td>
                            <td class="px-4 py-3">{{ $turma->anoLetivo->nome }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif
    </div>
    <div>
        <x-card title="Estatísticas" icon="fas fa-chart-bar">
            <div class="space-y-4">
                <div class="text-center p-4 bg-primary-50 rounded-lg">
                    <div class="text-3xl font-bold text-primary-600">{{ $curso->turmas_count }}</div>
                    <div class="text-sm text-gray-600">Turmas</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">{{ $curso->turmas->sum('total_alunos') }}</div>
                    <div class="text-sm text-gray-600">Total de Alunos</div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection

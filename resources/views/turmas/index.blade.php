@extends('layouts.app')
@section('page-title', 'Turmas')
@section('header-actions')
<a href="{{ route('turmas.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Nova Turma</a>
@endsection
@section('content')
<x-card>
    @if($turmas->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">Turma</th>
                    <th class="px-6 py-3 text-left">Curso</th>
                    <th class="px-6 py-3 text-center">Classe</th>
                    <th class="px-6 py-3 text-center">Alunos</th>
                    <th class="px-6 py-3 text-left">Ano Letivo</th>
                    <th class="px-6 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($turmas as $turma)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-semibold">{{ $turma->nome }}</td>
                    <td class="px-6 py-4">{{ $turma->curso->nome }}</td>
                    <td class="px-6 py-4 text-center"><x-badge type="primary">{{ $turma->classe }}ª</x-badge></td>
                    <td class="px-6 py-4 text-center">{{ $turma->total_alunos }} / {{ $turma->capacidade }}</td>
                    <td class="px-6 py-4">{{ $turma->anoLetivo->nome }}</td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('turmas.show', $turma) }}" class="text-primary-600"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('turmas.edit', $turma) }}" class="text-blue-600"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $turmas->links() }}
    @else
    <div class="text-center py-12">
        <i class="fas fa-chalkboard text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhuma turma cadastrada</p>
        <a href="{{ route('turmas.create') }}" class="btn btn-primary mt-4"><i class="fas fa-plus mr-2"></i>Criar Primeira Turma</a>
    </div>
    @endif
</x-card>
@endsection

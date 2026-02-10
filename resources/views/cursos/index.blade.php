{{-- INDEX --}}
@extends('layouts.app')
@section('page-title', 'Cursos')
@section('header-actions')
<a href="{{ route('cursos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Novo Curso</a>
@endsection
@section('content')
<x-card>
    @if($cursos->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coordenador</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turmas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($cursos as $curso)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4"><span class="font-mono font-bold">{{ $curso->codigo }}</span></td>
                    <td class="px-6 py-4 font-semibold">{{ $curso->nome }}</td>
                    <td class="px-6 py-4">{{ $curso->coordenador?->name ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $curso->turmas_count }}</td>
                    <td class="px-6 py-4">
                        <x-badge type="{{ $curso->ativo ? 'success' : 'danger' }}">
                            {{ $curso->ativo ? 'Ativo' : 'Inativo' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('cursos.show', $curso) }}" class="text-primary-600"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('cursos.edit', $curso) }}" class="text-blue-600"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $cursos->links() }}
    @else
    <div class="text-center py-12">
        <i class="fas fa-book text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhum curso cadastrado</p>
        <a href="{{ route('cursos.create') }}" class="btn btn-primary mt-4"><i class="fas fa-plus mr-2"></i>Criar Primeiro Curso</a>
    </div>
    @endif
</x-card>
@endsection

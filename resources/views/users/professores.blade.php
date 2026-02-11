@extends('layouts.app')

@section('page-title', 'Professores')

@section('header-actions')
<a href="{{ route('users.create') }}" class="btn btn-primary">
    <i class="fas fa-user-plus mr-2"></i>
    Novo Professor
</a>
@endsection

@section('content')

<x-card>
    @if($professores->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Professor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplinas</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Turmas</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($professores as $professor)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $professor->foto_perfil_url }}" class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $professor->name }}</div>
                                <div class="text-sm text-gray-500">{{ $professor->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @forelse($professor->atribuicoes->unique('disciplina_id')->take(3) as $atrib)
                            <x-badge type="info">{{ $atrib->disciplina->codigo }}</x-badge>
                            @empty
                            <span class="text-gray-400 text-sm">Sem atribuições</span>
                            @endforelse
                            @if($professor->atribuicoes->unique('disciplina_id')->count() > 3)
                            <x-badge type="gray">+{{ $professor->atribuicoes->unique('disciplina_id')->count() - 3 }}</x-badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold text-gray-900">{{ $professor->atribuicoes->unique('turma_id')->count() }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge type="{{ $professor->ativo ? 'success' : 'danger' }}">
                            {{ $professor->ativo ? 'Ativo' : 'Inativo' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('users.show', $professor) }}" class="text-primary-600"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('users.edit', $professor) }}" class="text-blue-600"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $professores->links() }}
    @else
    <div class="text-center py-12">
        <i class="fas fa-chalkboard-teacher text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhum professor encontrado</p>
    </div>
    @endif
</x-card>

@endsection
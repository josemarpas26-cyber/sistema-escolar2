@extends('layouts.app')

@section('page-title', 'Disciplinas')

@section('header-actions')
<a href="{{ route('disciplinas.create') }}" class="btn btn-primary">
    <i class="fas fa-plus mr-2"></i>
    Nova Disciplina
</a>
@endsection

@section('content')

<x-card>
    @if($disciplinas->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Classes</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terminal</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($disciplinas as $disciplina)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono font-bold text-gray-900">{{ $disciplina->codigo }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-semibold text-gray-900">{{ $disciplina->nome }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center space-x-1">
                            @if($disciplina->leciona_10)
                            <x-badge type="info">10ª</x-badge>
                            @endif
                            @if($disciplina->leciona_11)
                            <x-badge type="info">11ª</x-badge>
                            @endif
                            @if($disciplina->leciona_12)
                            <x-badge type="info">12ª</x-badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($disciplina->disciplina_terminal)
                        <i class="fas fa-check-circle text-green-600 text-lg"></i>
                        @else
                        <i class="fas fa-times-circle text-gray-300 text-lg"></i>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge type="{{ $disciplina->ativo ? 'success' : 'danger' }}">
                            {{ $disciplina->ativo ? 'Ativa' : 'Inativa' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('disciplinas.show', $disciplina) }}" 
                               class="text-primary-600 hover:text-primary-900" 
                               title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('disciplinas.edit', $disciplina) }}" 
                               class="text-blue-600 hover:text-blue-900" 
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('disciplinas.destroy', $disciplina) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-900" 
                                        title="Deletar"
                                        onclick="return confirm('Deseja deletar esta disciplina?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="mt-4">
        {{ $disciplinas->links() }}
    </div>

    @else

    <!-- Empty State -->
    <div class="text-center py-12">
        <i class="fas fa-book-open text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma disciplina cadastrada</h3>
        <p class="text-gray-600 mb-6">
            Comece criando a primeira disciplina do sistema
        </p>
        <a href="{{ route('disciplinas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>
            Criar Primeira Disciplina
        </a>
    </div>

    @endif
</x-card>

@endsection
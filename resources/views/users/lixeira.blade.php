@extends('layouts.app')

@section('page-title', 'Lixeira — Utilizadores Deletados')

@section('header-actions')
<a href="{{ route('users.index') }}" class="btn btn-outline">
    <i class="fas fa-arrow-left mr-2"></i>
    Voltar à Lista
</a>
@endsection

@section('content')

{{-- Filtro --}}
<x-card class="mb-6">
    <form method="GET" class="flex gap-4">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Pesquisar por nome ou email..."
               class="input flex-1">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search mr-2"></i>
            Filtrar
        </button>
        @if(request('search'))
        <a href="{{ route('users.lixeira') }}" class="btn btn-outline">
            <i class="fas fa-times"></i>
        </a>
        @endif
    </form>
</x-card>
<br>
<x-card>

    {{-- Aviso informativo --}}
    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
        <i class="fas fa-info-circle text-amber-500 mt-0.5 flex-shrink-0"></i>
        <div class="text-sm text-amber-800">
            <p class="font-semibold mb-1">Sobre a restauração</p>
            <p>Ao restaurar um utilizador, as suas matrículas e dados académicos são preservados automaticamente
               porque o sistema usa deleção suave (soft delete). O utilizador volta ao estado anterior à deleção.</p>
        </div>
    </div>

    @if($users->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilizador</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Papel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Processo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deletado em</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($users as $user)
                <tr class="hover:bg-red-50 bg-red-50/50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            {{-- Avatar com ícone de deletado --}}
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-times text-white text-xs" style="font-size:8px"></i>
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <x-badge type="gray">{{ $user->role->display_name ?? '—' }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $user->numero_processo ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <div>{{ $user->deleted_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-400">{{ $user->deleted_at->format('H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <form method="POST"
                              action="{{ route('users.restore', $user->id) }}"
                              onsubmit="return confirm('Restaurar {{ addslashes($user->name) }}?\n\nAs matrículas e dados académicos serão mantidos.')">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-undo mr-2"></i>
                                Restaurar
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    @else
    <div class="text-center py-16">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash-alt text-3xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Lixeira vazia</h3>
        <p class="text-gray-500 text-sm">Nenhum utilizador foi deletado.</p>
    </div>
    @endif

</x-card>

@endsection
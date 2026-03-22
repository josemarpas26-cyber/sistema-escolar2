@extends('layouts.app')

@section('page-title', 'Usuários')

@section('header-actions')
<a href="{{ route('users.create') }}" class="btn btn-primary">
    <i class="fas fa-plus mr-2"></i>
    Novo Usuário
</a>
@endsection

@section('content')

<!-- Filtros -->
<x-card class="mb-6">
    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf

        <!-- Busca -->
        <div>
            <label class="label">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Nome, email, BI..." 
                   class="input">
        </div>

        <!-- Papel -->
        <div>
            <label class="label">Papel</label>
            <select name="role_id" class="input">
                <option value="">Todos</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                    {{ $role->display_name }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Status -->
        <div>
            <label class="label">Status</label>
            <select name="ativo" class="input">
                <option value="">Todos</option>
                <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
                <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
            </select>
        </div>

        <!-- Botões -->
        <div class="flex items-end space-x-2">
            <button type="submit" class="btn btn-primary flex-1">
                <i class="fas fa-search mr-2"></i>
                Filtrar
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-outline">
                <i class="fas fa-times"></i>
            </a>
        </div>

    </form>
</x-card>

<!-- Tabela -->
<x-card>
    @if($users->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Usuário
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Papel
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <img src="{{ $user->foto_perfil_url }}" alt="{{ $user->name }}" 
                                 class="w-10 h-10 rounded-full object-cover">
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                @if($user->numero_processo)
                                <div class="text-sm text-gray-500">{{ $user->numero_processo }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $user->email }}</div>
                        @if($user->telefone)
                        <div class="text-sm text-gray-500">{{ $user->telefone }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <x-badge type="primary">{{ $user->role->display_name }}</x-badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <x-badge type="{{ $user->ativo ? 'success' : 'danger' }}">
                            {{ $user->ativo ? 'Ativo' : 'Inativo' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <a href="{{ route('users.show', $user) }}" 
                           class="text-primary-600 hover:text-primary-900"
                           title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('users.edit', $user) }}" 
                           class="text-blue-600 hover:text-blue-900"
                           title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}" 
                              class="inline">
                            @csrf
                            <button type="submit" 
                                    class="text-{{ $user->ativo ? 'yellow' : 'green' }}-600 hover:text-{{ $user->ativo ? 'yellow' : 'green' }}-900"
                                    title="{{ $user->ativo ? 'Desativar' : 'Ativar' }}">
                                <i class="fas fa-{{ $user->ativo ? 'ban' : 'check' }}"></i>
                            </button>
                        </form>
                        @if($user->id !== auth()->id())
                        <button onclick="confirmDelete('delete-form-{{ $user->id }}', 'Deseja deletar {{ $user->name }}?')" 
                                class="text-red-600 hover:text-red-900"
                                title="Deletar">
                            <i class="fas fa-trash"></i>
                        </button>
                        <form id="delete-form-{{ $user->id }}" 
                              method="POST" 
                              action="{{ route('users.destroy', $user) }}" 
                              class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="mt-4">
        {{ $users->links() }}
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Nenhum usuário encontrado</p>
        <a href="{{ route('users.create') }}" class="btn btn-primary mt-4">
            <i class="fas fa-plus mr-2"></i>
            Criar Primeiro Usuário
        </a>
    </div>
    @endif
</x-card>

@endsection

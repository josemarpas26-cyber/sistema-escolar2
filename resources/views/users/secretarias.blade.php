@extends('layouts.app')

@section('page-title', 'Secretárias')

@section('header-actions')
@if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
<a href="{{ route('users.create') }}" class="btn btn-primary">
    <i class="fas fa-user-plus mr-2"></i>
    Nova Secretária
</a>
@endif
@endsection

@section('content')
<x-card class="mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar por nome, email ou BI..." class="input">
        <select name="status" class="input">
            <option value="">Todos os status</option>
            <option value="ativo" {{ request('status') == 'ativo' ? 'selected' : '' }}>Ativo</option>
            <option value="inativo" {{ request('status') == 'inativo' ? 'selected' : '' }}>Inativo</option>
        </select>
        <select name="genero" class="input">
            <option value="">Todos os sexos</option>
            <option value="M" {{ request('genero') == 'M' ? 'selected' : '' }}>Masculino</option>
            <option value="F" {{ request('genero') == 'F' ? 'selected' : '' }}>Feminino</option>
        </select>
        <select name="ordem" class="input">
            <option value="alfabetica" {{ request('ordem', 'alfabetica') == 'alfabetica' ? 'selected' : '' }}>Ordem alfabética</option>
            <option value="recentes" {{ request('ordem') == 'recentes' ? 'selected' : '' }}>Data de adição (mais recentes)</option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-2"></i>Filtrar</button>
    </form>
</x-card>

<x-card>
    @if($secretarias->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">Secretária</th>
                    <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                    <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3.5 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($secretarias as $secretaria)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $secretaria->foto_perfil_url }}" class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $secretaria->name }}</div>
                                <div class="text-sm text-gray-500">{{ $secretaria->email ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $secretaria->telefone ?? '—' }}</td>
                    <td class="px-6 py-4 text-center">
                        <x-badge type="{{ $secretaria->ativo ? 'success' : 'danger' }}">{{ $secretaria->ativo ? 'Ativo' : 'Inativo' }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('users.show', $secretaria) }}" class="text-primary-600"><i class="fas fa-eye"></i></a>
                        @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                        <a href="{{ route('users.edit', $secretaria) }}" class="text-blue-600"><i class="fas fa-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $secretarias->links('vendor.pagination.tailwind') }}
    @else
    <div class="text-center py-12 text-gray-500">Nenhuma secretária encontrada.</div>
    @endif
</x-card>
@endsection

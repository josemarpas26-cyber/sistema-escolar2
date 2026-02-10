@extends('layouts.app')
@section('page-title', 'Anos Letivos')
@section('header-actions')
<a href="{{ route('anos-letivos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Novo Ano Letivo</a>
@endsection
@section('content')
<x-card>
    @if($anosLetivos->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">Nome</th>
                    <th class="px-6 py-3 text-left">Data Início</th>
                    <th class="px-6 py-3 text-left">Data Fim</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Turmas</th>
                    <th class="px-6 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($anosLetivos as $ano)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $ano->ativo ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                        <td class="px-6 py-4 font-semibold">
                            {{ $ano->nome }}
                        </td>

                        <td class="px-6 py-4">
                            {{ optional($ano->data_inicio)->format('d/m/Y') }}
                        </td>

                        <td class="px-6 py-4">
                            {{ optional($ano->data_fim)->format('d/m/Y') }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($ano->ativo)
                                <x-badge type="success">Ativo</x-badge>
                            @elseif($ano->encerrado)
                                <x-badge type="danger">Encerrado</x-badge>
                            @else
                                <x-badge type="gray">Inativo</x-badge>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center font-medium">
                            {{ $ano->turmas_count }}
                        </td>

                        <td class="px-6 py-4 text-right space-x-3">
                            <a href="{{ route('anos-letivos.edit', $ano) }}"
                            class="text-blue-600 hover:text-blue-800 transition">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            Nenhum ano letivo cadastrado.
                        </td>
                    </tr>
                    @endforelse

            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fas fa-calendar-alt text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhum ano letivo cadastrado</p>
        <a href="{{ route('anos-letivos.create') }}" class="btn btn-primary mt-4"><i class="fas fa-plus mr-2"></i>Criar Primeiro Ano</a>
    </div>
    @endif
</x-card>
@endsection

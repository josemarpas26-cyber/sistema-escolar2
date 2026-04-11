@extends('layouts.app')

@section('page-title', 'Fórmulas de Avaliação')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold">Fórmulas por ano letivo</h2>
        <a href="{{ route('formulas.create') }}" class="btn btn-primary">Nova fórmula</a>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="p-2">Nome</th>
                        <th class="p-2">Ano</th>
                        <th class="p-2">Versões</th>
                        <th class="p-2">Provas</th>
                        <th class="p-2">Estado</th>
                        <th class="p-2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($formulas as $formula)
                    <tr class="border-b">
                        <td class="p-2">{{ $formula->nome }}</td>
                        <td class="p-2">{{ $formula->anoLetivo?->nome }}</td>
                        <td class="p-2">{{ $formula->versoes_count }}</td>
                        <td class="p-2">{{ $formula->avaliacoes_count }}</td>
                        <td class="p-2">
                            @if($formula->anoLetivo?->encerrado)
                                <span class="px-2 py-1 rounded bg-gray-100 text-gray-700">Somente leitura</span>
                            @else
                                <span class="px-2 py-1 rounded bg-green-100 text-green-700">Editável</span>
                            @endif
                        </td>
                        <td class="p-2">
                            <a class="text-blue-600" href="{{ route('formulas.show', $formula) }}">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-center text-gray-500">Nenhuma fórmula cadastrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection

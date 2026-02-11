@extends('layouts.app')

@section('page-title', $anoLetivo->nome)

@section('header-actions')
<a href="{{ route('anos-letivos.edit', $anoLetivo) }}" class="btn btn-primary">
    <i class="fas fa-edit mr-2"></i>
    Editar
</a>
@endsection

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-card title="Informações" icon="fas fa-info-circle" class="mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-600">Nome:</span>
                    <p class="font-semibold text-gray-900">{{ $anoLetivo->nome }}</p>
                </div>
                <div>
                    <span class="text-gray-600">Status:</span>
                    @if($anoLetivo->ativo)
                    <x-badge type="success">Ativo</x-badge>
                    @elseif($anoLetivo->encerrado)
                    <x-badge type="danger">Encerrado</x-badge>
                    @else
                    <x-badge type="gray">Inativo</x-badge>
                    @endif
                </div>
                <div>
                    <span class="text-gray-600">Data Início:</span>
                    <p class="font-semibold">{{ $anoLetivo->data_inicio->format('d/m/Y') }}</p>
                </div>
                <div>
                    <span class="text-gray-600">Data Fim:</span>
                    <p class="font-semibold">{{ $anoLetivo->data_fim->format('d/m/Y') }}</p>
                </div>
            </div>
        </x-card>

        @if($anoLetivo->turmas->count() > 0)
        <x-card title="Turmas" icon="fas fa-chalkboard">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Turma</th>
                            <th class="px-4 py-2 text-left">Curso</th>
                            <th class="px-4 py-2 text-center">Alunos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($anoLetivo->turmas as $turma)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('turmas.show', $turma) }}" class="text-primary-600">
                                    {{ $turma->nome_completo }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $turma->curso->nome }}</td>
                            <td class="px-4 py-3 text-center">{{ $turma->total_alunos }}</td>
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
                    <div class="text-3xl font-bold text-primary-600">{{ $anoLetivo->turmas->count() }}</div>
                    <div class="text-sm text-gray-600">Turmas</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">{{ $anoLetivo->turmas->sum('total_alunos') }}</div>
                    <div class="text-sm text-gray-600">Total de Alunos</div>
                </div>
            </div>
        </x-card>
    </div>
</div>

@endsection
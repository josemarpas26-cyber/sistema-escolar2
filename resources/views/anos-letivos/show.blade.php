@extends('layouts.app')

@section('page-title', 'Ano Letivo: ' . $anoLetivo->nome)

@section('header-actions')
<div class="flex items-center gap-2">

    {{-- Voltar inteligente --}}
    <a href="{{ url()->previous() }}" 
       class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>
        Voltar
    </a>

    {{-- Encerrar Ano Letivo --}}
    @if(!$anoLetivo->encerrado)
        <form action="{{ route('anos-letivos.encerrar', $anoLetivo) }}" 
              method="POST"
              onsubmit="return confirm('Tem certeza que deseja encerrar este ano letivo? Esta ação não poderá ser desfeita.')">
            @csrf


            <button type="submit" class="btn btn-danger">
                <i class="fas fa-lock mr-2"></i>
                Encerrar
            </button>
        </form>
    @endif

    {{-- Editar --}}
    <a href="{{ route('anos-letivos.edit', $anoLetivo) }}" 
       class="btn btn-primary">
        <i class="fas fa-edit mr-2"></i>
        Editar
    </a>

</div>
@endsection


@section('content')

<div class="space-y-6">

    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card 
            title="Turmas" 
            :value="$anoLetivo->turmas->count()" 
            icon="chalkboard" 
            color="blue" 
        />

        <x-stat-card 
            title="Alunos Matriculados" 
            :value="$anoLetivo->turmas->sum('total_alunos')" 
            icon="users" 
            color="green" 
        />

        <x-stat-card 
            title="Período" 
            :value="$anoLetivo->data_inicio->format('d/m/Y') . ' - ' . $anoLetivo->data_fim->format('d/m/Y')" 
            icon="calendar" 
            color="purple" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Informações --}}
        <div class="lg:col-span-1">
            <x-card title="Informações" icon="fas fa-info-circle">
                <div class="space-y-4">
                    <div>
                        <span class="text-gray-600 text-sm">Nome:</span>
                        <p class="font-semibold text-gray-900">
                            {{ $anoLetivo->nome }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-600 text-sm">Status:</span>
                        <div class="mt-1">
                            @if($anoLetivo->ativo)
                                <x-badge type="success">Ativo</x-badge>
                            @elseif($anoLetivo->encerrado)
                                <x-badge type="danger">Encerrado</x-badge>
                            @else
                                <x-badge type="gray">Inativo</x-badge>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-600 text-sm">Data Início:</span>
                        <p class="font-semibold">
                            {{ $anoLetivo->data_inicio->format('d/m/Y') }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-600 text-sm">Data Fim:</span>
                        <p class="font-semibold">
                            {{ $anoLetivo->data_fim->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Turmas --}}
        <div class="lg:col-span-2">
            <x-card title="Turmas do Ano Letivo" icon="fas fa-chalkboard">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Turma</th>
                                <th class="px-4 py-2 text-left">Curso</th>
                                <th class="px-4 py-2 text-center">Alunos</th>
                                <th class="px-4 py-2 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($anoLetivo->turmas as $turma)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        {{ $turma->nome_completo ?? $turma->nome }}
                                    </td>

                                    <td class="px-4 py-2">
                                        {{ $turma->curso->nome ?? '-' }}
                                    </td>

                                    <td class="px-4 py-2 text-center">
                                        {{ $turma->total_alunos ?? $turma->alunos->count() }}
                                    </td>

                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('turmas.show', $turma) }}" 
                                           class="text-primary-600 hover:text-primary-800 font-medium">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        Nenhuma turma associada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

    </div>

</div>

@endsection

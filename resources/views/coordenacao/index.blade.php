@extends('layouts.app')

@section('title', 'Coordenação')
@section('page-title', 'Coordenação')

@section('content')
<div class="space-y-6">
    @if(!$curso && !$turma)
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
            Nenhuma coordenação atribuída ao seu utilizador neste momento.
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Ano letivo ativo</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ $anoLetivoAtivo?->nome ?? 'Sem ano letivo ativo' }}</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Alunos da turma coordenada</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ $totalAlunosTurma }}</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Média geral da turma</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ is_null($mediaTurma) ? '-' : number_format($mediaTurma, 2) }}</p>
        </div>
    </div>

    @if($curso)
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Curso coordenado</h2>
            <p class="text-gray-600 mt-1">{{ $curso->nome }}</p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-500 border-b">
                        <tr>
                            <th class="py-2 pr-4">Turma</th>
                            <th class="py-2 pr-4">Classe</th>
                            <th class="py-2">Alunos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($curso->turmas as $cursoTurma)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pr-4">{{ $cursoTurma->nome }}</td>
                                <td class="py-2 pr-4">{{ $cursoTurma->classe }}</td>
                                <td class="py-2">{{ $cursoTurma->alunos_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-3 text-gray-500">Sem turmas associadas ao curso.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($turma)
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Turma coordenada</h2>
            <p class="text-gray-600 mt-1">{{ $turma->nome }} · {{ $turma->curso?->nome ?? 'Sem curso' }}</p>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($turma->disciplinas as $disciplina)
                    <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">
                        {{ $disciplina->nome }}
                    </span>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

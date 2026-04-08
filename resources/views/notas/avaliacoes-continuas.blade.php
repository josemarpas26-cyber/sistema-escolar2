@extends('layouts.app')

@section('page-title', 'Avaliações Contínuas')

@section('content')
@php
    $isProfessor = auth()->user()->isProfessor();
    $isAdminArea = auth()->user()->isAdmin() || auth()->user()->isSecretaria();
@endphp

<x-card class="mb-6" title="Filtro de pauta" icon="fas fa-filter">
    <form method="GET" action="{{ route('notas.avaliacoes-continuas.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <select name="turma_id" class="form-input" required onchange="this.form.submit()">
            <option value="">Turma</option>
            @foreach($turmas as $turma)
                <option value="{{ $turma->id }}" @selected((int)$turmaId === (int)$turma->id)>
                    {{ $turma->nome_completo }}
                </option>
            @endforeach
        </select>

        <select name="disciplina_id" class="form-input" @disabled(!$turmaId) onchange="this.form.submit()">
            <option value="">Disciplina</option>
            @foreach($disciplinas as $disciplina)
                <option value="{{ $disciplina->id }}" @selected((int)$disciplinaId === (int)$disciplina->id)>
                    {{ $disciplina->nome }}
                </option>
            @endforeach
        </select>

        <div class="md:col-span-2 text-sm text-gray-500 flex items-center">
            Ano letivo ativo: <strong class="ml-1">{{ $anoLetivo->nome }}</strong>
        </div>
    </form>
</x-card>

@if($notas->isNotEmpty())
<x-card title="Tabela de avaliações contínuas" icon="fas fa-list-ol">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">Aluno</th>
                    <th class="px-3 py-2 text-left">1º Trimestre</th>
                    <th class="px-3 py-2 text-left">2º Trimestre</th>
                    <th class="px-3 py-2 text-left">3º Trimestre</th>
                    <th class="px-3 py-2 text-center">MAC</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            @foreach($notas as $nota)
                <tr>
                    <td class="px-3 py-3 align-top">
                        <div class="font-semibold text-gray-800">{{ $nota->aluno->name }}</div>
                        <div class="text-xs text-gray-500">{{ $nota->aluno->numero_processo ?? '—' }}</div>
                    </td>

                    @for($trimestre = 1; $trimestre <= 3; $trimestre++)
                        @php
                            $items = $nota->avaliacoesContinuas->where('trimestre', $trimestre);
                        @endphp
                        <td class="px-3 py-3 align-top">
                            <div class="space-y-2">
                                @forelse($items as $item)
                                    <div class="border rounded p-2 text-xs">
                                        @if($isAdminArea)
                                        <form method="POST" action="{{ route('notas.avaliacoes-continuas.update', $item) }}" class="grid grid-cols-12 gap-1">
                                            @csrf
                                            @method('PUT')
                                            <input name="descricao" value="{{ $item->descricao }}" maxlength="120" class="col-span-6 form-input h-8 text-xs" required>
                                            <input name="valor" type="number" step="0.01" min="0" max="20" value="{{ $item->valor }}" class="col-span-3 form-input h-8 text-xs" required>
                                            <button class="col-span-2 btn btn-outline h-8 text-xs" type="submit">Salvar</button>
                                        </form>
                                        <form method="POST" action="{{ route('notas.avaliacoes-continuas.destroy', $item) }}" class="mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 text-xs" type="submit">Remover</button>
                                        </form>
                                        @else
                                            <div class="font-medium">{{ $item->descricao }}</div>
                                            <div>{{ number_format($item->valor, 2) }} valores</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-xs text-gray-400">Sem avaliações</span>
                                @endforelse

                                @if($isProfessor && $nota->trimestreEstaDisponivel($trimestre) && ! $nota->{"bloqueado_t{$trimestre}"})
                                    <form method="POST" action="{{ route('notas.avaliacoes-continuas.store') }}" class="grid grid-cols-12 gap-1">
                                        @csrf
                                        <input type="hidden" name="nota_id" value="{{ $nota->id }}">
                                        <input type="hidden" name="trimestre" value="{{ $trimestre }}">
                                        <input type="text" name="descricao" maxlength="120" required class="col-span-7 form-input h-8 text-xs" placeholder="Descrição">
                                        <input type="number" step="0.01" min="0" max="20" name="valor" required class="col-span-3 form-input h-8 text-xs" placeholder="Nota">
                                        <button type="submit" class="col-span-2 btn btn-primary h-8 text-xs">+</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endfor

                    <td class="px-3 py-3 align-top text-xs text-center">
                        <div>T1: <strong>{{ $nota->mac1 !== null ? number_format($nota->mac1, 2) : '—' }}</strong></div>
                        <div>T2: <strong>{{ $nota->mac2 !== null ? number_format($nota->mac2, 2) : '—' }}</strong></div>
                        <div>T3: <strong>{{ $nota->mac3 !== null ? number_format($nota->mac3, 2) : '—' }}</strong></div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@else
<x-card>
    <p class="text-gray-500">Selecione turma e disciplina para visualizar a tabela de avaliações contínuas.</p>
</x-card>
@endif
@endsection

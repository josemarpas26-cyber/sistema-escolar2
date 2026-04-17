@extends('layouts.app')

@section('page-title', 'Avaliações Contínuas')

@section('content')
@php
    $isProfessor = auth()->user()->isProfessor();
    $isAdminArea = auth()->user()->isAdmin() || auth()->user()->isSecretaria();
    $viewMode = request('view_mode', 'cards');
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
<x-card class="mb-6" title="Lançamento em lote" icon="fas fa-layer-group">
    <p class="text-sm text-gray-600 mb-4">
        Preencha apenas as notas que deseja lançar e clique em <strong>Guardar todas</strong>.
    </p>
    <form method="POST" action="{{ route('notas.avaliacoes-continuas.store-lote') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <select name="trimestre" class="form-input" required>
                <option value="">Trimestre</option>
                <option value="{{ $trimestreCorrente }}" selected>{{ $trimestreCorrente }}º Trimestre (corrente)</option>
            </select>
            <input type="text" name="descricao" class="form-input" maxlength="120" placeholder="Descrição (opcional)">
            <input type="date" name="data_avaliacao" class="form-input" value="{{ now()->toDateString() }}">
            <button class="btn btn-primary" type="submit">Guardar todas</button>
        </div>
        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left">Aluno</th>
                    <th class="px-3 py-2 text-left">Nº Processo</th>
                    <th class="px-3 py-2 text-left">Nota</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @foreach($notas as $nota)
                    <tr>
                        <td class="px-3 py-2 font-semibold text-slate-800">{{ $nota->aluno->name }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $nota->aluno->numero_processo ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <input type="hidden" name="avaliacoes[{{ $nota->id }}][nota_id]" value="{{ $nota->id }}">
                            <input type="number" step="0.01" min="0" max="20" name="avaliacoes[{{ $nota->id }}][valor]" class="form-input max-w-[120px]" placeholder="0-20">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </form>
</x-card>

<x-card title="Avaliações contínuas" icon="fas fa-list-ol">
    <div class="flex justify-end mb-3">
        <div class="inline-flex rounded-md border border-slate-300 overflow-hidden text-xs">
            <a href="{{ route('notas.avaliacoes-continuas.index', ['turma_id' => $turmaId, 'disciplina_id' => $disciplinaId, 'view_mode' => 'cards']) }}"
               class="px-3 py-2 {{ $viewMode === 'cards' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700' }}">
                Modo cartões
            </a>
            <a href="{{ route('notas.avaliacoes-continuas.index', ['turma_id' => $turmaId, 'disciplina_id' => $disciplinaId, 'view_mode' => 'tabela']) }}"
               class="px-3 py-2 {{ $viewMode === 'tabela' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700' }}">
                Modo tabela
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
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
                            $macCampo = "mac{$trimestre}";
                            $macValor = $nota->{$macCampo};
                            $macClass = $macValor === null
                                ? 'bg-slate-100 text-slate-500'
                                : ($macValor >= 10 ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-rose-100 text-rose-700 border border-rose-200');
                        @endphp
                        <td class="px-3 py-3 align-top">
                            @if($viewMode === 'tabela')
                                <div class="border rounded-lg overflow-hidden">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-100">
                                        <tr>
                                            <th class="text-left px-2 py-1">Descrição</th>
                                            <th class="text-left px-2 py-1">Nota</th>
                                            <th class="text-left px-2 py-1">Data</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                        @forelse($items as $item)
                                            @php
                                                $valorClass = $item->valor >= 10 ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-semibold';
                                            @endphp
                                            <tr>
                                                <td class="px-2 py-1">{{ $item->descricao }}</td>
                                                <td class="px-2 py-1 {{ $valorClass }}">{{ number_format($item->valor, 2) }}</td>
                                                <td class="px-2 py-1 text-slate-500">{{ optional($item->data_avaliacao)->format('d/m/Y') ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-2 py-2 text-slate-400">Sem avaliações</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @else
                            <div class="space-y-2">
                                @forelse($items as $item)
                                    @php
                                        $valorClass = $item->valor >= 10 ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-semibold';
                                    @endphp
                                    <div class="border rounded p-2 text-xs">
                                        @if($isAdminArea)
                                        <form method="POST" action="{{ route('notas.avaliacoes-continuas.update', $item) }}" class="grid grid-cols-12 gap-1">
                                            @csrf
                                            @method('PUT')
                                            <input name="descricao" value="{{ $item->descricao }}" maxlength="120" class="col-span-5 form-input h-8 text-xs" placeholder="Descrição (opcional)">
                                            <input name="valor" type="number" step="0.01" min="0" max="20" value="{{ $item->valor }}" class="col-span-2 form-input h-8 text-xs {{ $valorClass }}" required>
                                            <input name="data_avaliacao" type="date" value="{{ optional($item->data_avaliacao)->format('Y-m-d') }}" class="col-span-3 form-input h-8 text-xs">
                                            <button class="col-span-2 btn btn-outline h-8 text-xs" type="submit">Salvar</button>
                                        </form>
                                        <form method="POST" action="{{ route('notas.avaliacoes-continuas.destroy', $item) }}" class="mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 text-xs" type="submit">Remover</button>
                                        </form>
                                        @else
                                            <div class="font-medium">{{ $item->descricao }}</div>
                                            <div class="{{ $valorClass }}">{{ number_format($item->valor, 2) }} valores</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-xs text-gray-400">Sem avaliações</span>
                                @endforelse


                            </div>
                            @endif
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold {{ $macClass }}">
                                    MAC T{{ $trimestre }}:
                                    <span class="ml-1">{{ $macValor !== null ? number_format($macValor, 2) : '—' }}</span>
                                </span>
                            </div>
                        </td>
                    @endfor

                    <td class="px-3 py-3 align-top text-xs text-center">
                        @foreach([1,2,3] as $tr)
                            @php
                                $mac = $nota->{"mac{$tr}"};
                                $macResumoClass = $mac === null
                                    ? 'bg-slate-100 text-slate-500'
                                    : ($mac >= 10 ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-rose-100 text-rose-700 border border-rose-200');
                            @endphp
                            <div class="mb-1">
                                <span class="inline-flex items-center px-2 py-1 rounded font-semibold {{ $macResumoClass }}">
                                    T{{ $tr }}: {{ $mac !== null ? number_format($mac, 2) : '—' }}
                                </span>
                            </div>
                        @endforeach
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

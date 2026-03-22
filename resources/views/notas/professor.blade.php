@extends('layouts.app')

@section('page-title', 'Lançamento de Notas')

@section('content')

<!-- Seleção de Turma/Disciplina -->
<x-card class="mb-6" title="Selecione Turma e Disciplina" icon="fas fa-filter">
    <form method="GET" action="{{ route('notas.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @csrf
        <div>
            <label class="label">Turma</label>
            <select name="turma_id" class="input" required onchange="this.form.submit()">
                <option value="">Selecione...</option>
                @foreach($atribuicoes->groupBy('turma_id') as $turmaId => $items)
                    @php $t = $items->first()->turma; @endphp
                    <option value="{{ $turmaId }}" {{ request('turma_id') == $turmaId ? 'selected' : '' }}>
                        {{ $t->nome_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label">Disciplina</label>
            <select name="disciplina_id" class="input" required {{ !request('turma_id') ? 'disabled' : '' }} onchange="this.form.submit()">
                <option value="">Selecione...</option>
                @if(request('turma_id'))
                    @foreach($atribuicoes->where('turma_id', request('turma_id')) as $atrib)
                        <option value="{{ $atrib->disciplina_id }}" {{ request('disciplina_id') == $atrib->disciplina_id ? 'selected' : '' }}>
                            {{ $atrib->disciplina->nome }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="btn btn-primary w-full">
                <i class="fas fa-search mr-2"></i>
                Buscar
            </button>
        </div>

    </form>
</x-card>

@if($notas && $turma && $disciplina)
@php
    $podeReabrirNotas = auth()->user()->can('notas.reabrir');
    $podeFinalizarNotas = auth()->user()->can('notas.editar');
    $opcoesAlunosOperacao = $notas->pluck('aluno')->filter()->unique('id')->sortBy('name')->values();
    $haNotasBloqueadasOuFinalizadas = $notas->contains(fn($nota) =>
        $nota->status === 'finalizado'
        || $nota->bloqueado_t1
        || $nota->bloqueado_t2
        || $nota->bloqueado_t3
    );
@endphp
<!-- Info da Turma/Disciplina -->
<div class="bg-primary-50 border border-primary-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-primary-900 text-lg">{{ $turma->nome_completo }}</h3>
            <p class="text-primary-700">{{ $disciplina->nome }} - {{ $disciplina->codigo }}</p>
        </div>
                <div class="text-right">
            <x-badge type="primary" class="text-lg">{{ $turma->classe }}ª Classe</x-badge>
            @if($notas->contains(fn($nota) => $nota->status === 'finalizado'))
                <p class="text-xs text-amber-700 mt-2">Notas finalizadas ficam somente leitura.</p>
            @endif
        </div>
    </div>
</div>
    <div class="mb-6 flex items-center justify-between gap-3">
        <p class="text-sm text-gray-600">
            Se não existirem notas para esta pauta, clique em <strong>Inicializar Pauta</strong> para criar os registos.
        </p>
                <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('notas.inicializar-pauta') }}">
                @csrf
                <input type="hidden" name="turma_id" value="{{ $turma->id }}">
                <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
                <button type="submit" class="btn btn-outline">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Inicializar Pauta
                </button>
            </form>

            @if($podeFinalizarNotas)
                <form method="POST" action="{{ route('notas.finalizar') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="turma_id" value="{{ $turma->id }}">
                    <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
                                
                                        <label class="text-xs text-gray-500">Escopo:</label><label class="text-xs text-gray-500">Escopo:</label><select name="trimestre" class="input" style="width:auto;display:inline-block">
                                        <option value="">Finalização Geral</option>
                                        <option value="1">Bloquear 1º Trimestre</option>
                                        <option value="2">Bloquear 2º Trimestre</option>
                                        <option value="3">Bloquear 3º Trimestre</option>
                                    </select>
                                    <label class="text-xs text-gray-500">Aluno:</label><select name="aluno_id" class="input" style="width:auto;display:inline-block">
                                        
                                    <option value="">Todos os alunos</option>
                               @foreach($opcoesAlunosOperacao as $al)
                                    <option value="{{ $al->id }}">{{ $al->name }}</option>
                                @endforeach
                                </select>

                    <button type="submit" class="btn btn-primary"
                            {{ $notas->isEmpty() || !$haNotasBloqueadasOuFinalizadas ? 'disabled' : '' }}>
                        <i class="fas fa-lock mr-2"></i>
                            Finalizar/Bloquear
                    </button>
                </form>
            @endif

            @if($podeReabrirNotas)
            <form method="POST" action="{{ route('notas.reabrir') }}" class="flex items-center gap-2"
            onsubmit="return confirm('Deseja reabrir esta pauta para edição?')">
                    @csrf
                    <input type="hidden" name="turma_id" value="{{ $turma->id }}">
                    <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
                            <select name="trimestre" class="input" style="width:auto;display:inline-block">
                                    <option value="">Reabertura Geral</option>
                                    <option value="1">Desbloquear 1º Trimestre</option>
                                    <option value="2">Desbloquear 2º Trimestre</option>
                                    <option value="3">Desbloquear 3º Trimestre</option>
                            </select>
                    <label class="text-xs text-gray-500">Aluno:</label><select name="aluno_id" class="input" style="width:auto;display:inline-block">
                            <option value="">Todos os alunos</option>
                            
                            @foreach($opcoesAlunosOperacao as $al)
                                <option value="{{ $al->id }}">{{ $al->name }}</option>
                            @endforeach

                            </select>
                    <button type="submit" class="btn btn-outline"
                            {{ $notas->isEmpty() || $notas->every(fn($nota) => $nota->status !== 'finalizado') ? 'disabled' : '' }}>
                        <i class="fas fa-lock-open mr-2"></i>
                        Reabrir/Desbloquear                    </button>
                </form>
            @endif
        </div>
    </div>
<!-- Tabs de Trimestres -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ tab: '1' }">
    <div class="border-b border-gray-200">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button @click="tab = '1'" 
                    :class="tab === '1' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                1º Trimestre
            </button>
            <button @click="tab = '2'" 
                    :class="tab === '2' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                2º Trimestre
            </button>
            <button @click="tab = '3'" 
                    :class="tab === '3' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                3º Trimestre
            </button>
        </nav>
    </div>

    <div class="p-6">
                @if($notas->isEmpty())
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">Nenhuma nota encontrada para esta pauta. Inicialize a pauta para começar os lançamentos.</p>
                    </div>
                 @endif
        <!-- 1º Trimestre -->
        <div x-show="tab === '1'">
            <form method="POST" action="{{ route('notas.trimestre-1') }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">MAC1</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">PP1</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">PT1</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">MT1</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($notas as $index => $nota)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $nota->aluno->name }}
                                                                        @if($nota->status === 'finalizado')
                                        <x-badge type="warning" class="ml-2">Somente leitura</x-badge>
                                    @endif
                                    <input type="hidden" name="notas[{{ $index }}][id]" value="{{ $nota->id }}">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][mac1]" 
                                           value="{{ $nota->mac1 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                                                                      onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][pp1]" 
                                           value="{{ $nota->pp1 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                                                                      onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][pt1]" 
                                           value="{{ $nota->pt1 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                                                                      onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-primary-600">
                                        {{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                                        <button type="submit" class="btn btn-success"
                            {{ $notas->every(fn($nota) => $nota->status === 'finalizado') && !$podeReabrirNotas ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-2"></i>
                        Salvar 1º Trimestre
                    </button>
                </div>
            </form>
        </div>

        <!-- 2º Trimestre -->
        <div x-show="tab === '2'" x-cloak>
            <form method="POST" action="{{ route('notas.trimestre-2') }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">MT1</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">MAC2</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">PP2</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">PT2</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">MT2</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">MFT2</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($notas as $index => $nota)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $nota->aluno->name }}
                                    @if($nota->status === 'finalizado')
                                        <x-badge type="warning" class="ml-2">Somente leitura</x-badge>
                                    @endif
                                    <input type="hidden" name="notas[{{ $index }}][id]" value="{{ $nota->id }}">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs text-gray-500">{{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][mac2]" 
                                           value="{{ $nota->mac2 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                                                                      onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][pp2]" 
                                           value="{{ $nota->pp2 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                                                                      onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][pt2]" 
                                           value="{{ $nota->pt2 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                                                                      onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-primary-600">
                                        {{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-green-600">
                                        {{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                             <button type="submit" class="btn btn-success"
                            {{ $notas->every(fn($nota) => $nota->status === 'finalizado') && !$podeReabrirNotas ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-2"></i>
                        Salvar 2º Trimestre
                    </button>
                </div>
            </form>
        </div>

        <!-- 3º Trimestre -->
        <div x-show="tab === '3'" x-cloak>
            <form method="POST" action="{{ route('notas.trimestre-3') }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">MFT2</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">MAC3</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">PP3</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">PG</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">CFD</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($notas as $index => $nota)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $nota->aluno->name }}
                                     @if($nota->status === 'finalizado')
                                        <x-badge type="warning" class="ml-2">Somente leitura</x-badge>
                                    @endif
                                    <input type="hidden" name="notas[{{ $index }}][id]" value="{{ $nota->id }}">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs text-gray-500">{{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][mac3]" 
                                           value="{{ $nota->mac3 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                            onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][pp3]" 
                                           value="{{ $nota->pp3 }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                            onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" step="0.01" min="0" max="20" 
                                           name="notas[{{ $index }}][pg]" 
                                           value="{{ $nota->pg }}"
                                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary-500"
                                            onblur="formatNota(this)"
                                           {{ $nota->status === 'finalizado' && !$podeReabrirNotas ? 'disabled' : '' }}>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold {{ $nota->cfd && $nota->cfd >= 10 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $nota->cfd ? number_format($nota->cfd, 2) : '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($nota->cfd)
                                        <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}">
                                            {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                                        </x-badge>
                                    @else
                                        <x-badge type="gray">-</x-badge>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    @if($turma->classe != '10')
                    <form method="POST" action="{{ route('notas.importar-cas') }}" class="inline">
                        @csrf
                        <input type="hidden" name="turma_id" value="{{ $turma->id }}">
                        <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
                        <button type="submit" class="btn btn-outline"
                            {{ $notas->every(fn($nota) => $nota->status === 'finalizado') && !$podeReabrirNotas ? 'disabled' : '' }}>
                            <i class="fas fa-download mr-2"></i>
                            Importar CAs
                        </button>
                    </form>
                    @endif
                    <button type="submit" class="btn btn-success"
                            {{ $notas->every(fn($nota) => $nota->status === 'finalizado') && !$podeReabrirNotas ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-2"></i>
                        Salvar 3º Trimestre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@else
<div class="text-center py-12">
    <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
    <p class="text-gray-500 text-lg">Selecione uma turma e disciplina para lançar notas</p>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

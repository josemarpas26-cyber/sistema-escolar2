@extends('layouts.app')

@section('page-title', 'Detalhes da Fórmula')

@section('content')
<div class="space-y-4">
    <x-card title="{{ $formula->nome }}">
        <p><strong>Ano letivo:</strong> {{ $formula->anoLetivo->nome }}</p>
        <p><strong>Soma dos pesos:</strong> {{ number_format($formula->somaPesosComponentes(), 2) }}%</p>
        @if(!$formula->anoLetivo->encerrado)
            <div class="mt-2"><a href="{{ route('formulas.edit', $formula) }}" class="btn btn-primary">Editar fórmula</a></div>
        @endif

        <div class="mt-4">
            <h4 class="font-semibold">Componentes</h4>
            <ul class="list-disc pl-5">
                @foreach($formula->componentes as $componente)
                    <li>{{ $componente['nome'] }} - {{ $componente['peso'] }}%</li>
                @endforeach
            </ul>
        </div>
    </x-card>

    <x-card title="Versionamento">
        <div class="space-y-2">
            @foreach($formula->versoes as $versao)
                <div class="border rounded p-3 flex justify-between items-center">
                    <div>
                        <div class="font-semibold">Versão #{{ $versao->versao }}</div>
                        <div class="text-xs text-gray-500">{{ $versao->created_at?->format('d/m/Y H:i') }} · {{ $versao->autor?->name ?? 'Sistema' }} · {{ $versao->motivo }}</div>
                    </div>
                    @if(!$formula->anoLetivo->encerrado)
                    <form method="POST" action="{{ route('formulas.restaurar-versao', [$formula, $versao]) }}">
                        @csrf
                        <button class="btn btn-outline" onclick="return confirm('Restaurar esta versão?')">Reverter</button>
                    </form>
                    @endif
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card title="Provas dinâmicas">
        @if(!$formula->anoLetivo->encerrado)
        <form method="POST" action="{{ route('formulas.avaliacoes.store', $formula) }}" class="grid md:grid-cols-6 gap-2 mb-4">
            @csrf
            <select name="disciplina_id" class="input md:col-span-2" required>
                <option value="">Disciplina</option>
                @foreach($disciplinas as $disciplina)
                    <option value="{{ $disciplina->id }}">{{ $disciplina->nome }}</option>
                @endforeach
            </select>
            <input name="nome" class="input" placeholder="Nome da prova" required>
            <select name="tipo" class="input" required>
                <option value="prova_escrita">Prova escrita</option>
                <option value="trabalho">Trabalho</option>
                <option value="avaliacao_continua">Avaliação contínua</option>
                <option value="exame_final">Exame final</option>
            </select>
            <input name="peso" type="number" step="0.01" min="0" max="100" class="input" placeholder="Peso" required>
            <input name="excecoes" class="input" placeholder="Exceções (opcional)">
            <button class="btn btn-primary md:col-span-6">Adicionar avaliação</button>
        </form>
        @endif

        <div class="space-y-2">
            @forelse($formula->avaliacoes as $avaliacao)
                <div class="border rounded p-3 flex justify-between items-center">
                    <div>
                        <div class="font-semibold">{{ $avaliacao->nome }} ({{ $avaliacao->tipo }})</div>
                        <div class="text-xs text-gray-500">{{ $avaliacao->disciplina->nome }} · Peso {{ $avaliacao->peso }}%</div>
                    </div>
                    @if(!$formula->anoLetivo->encerrado)
                    <form method="POST" action="{{ route('formulas.avaliacoes.destroy', [$formula, $avaliacao]) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline" onclick="return confirm('Remover avaliação?')">Remover</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-gray-500">Nenhuma avaliação dinâmica cadastrada.</p>
            @endforelse
        </div>
    </x-card>
</div>
@endsection

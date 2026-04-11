<form method="POST" action="{{ $action }}" class="max-w-4xl mx-auto space-y-4">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <x-card title="Configuração da fórmula">
        <div class="space-y-3">
            @if($formula)
                <input type="hidden" name="ano_letivo_id" value="{{ $formula->ano_letivo_id }}">
                <p><strong>Ano letivo:</strong> {{ $formula->anoLetivo->nome }}</p>
            @else
                <div>
                    <label class="label">Ano letivo</label>
                    <select name="ano_letivo_id" class="input" required>
                        @foreach($anosLetivos as $ano)
                            <option value="{{ $ano->id }}" @selected(old('ano_letivo_id') == $ano->id)>{{ $ano->nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="label">Nome</label>
                <input class="input" name="nome" value="{{ old('nome', $formula->nome ?? '') }}" required>
            </div>

            <div>
                <label class="label">Regras</label>
                <div class="grid md:grid-cols-2 gap-3">
                    <input class="input" type="number" step="0.01" min="0" max="20" name="regras[nota_minima]" value="{{ old('regras.nota_minima', data_get($formula, 'regras.nota_minima')) }}" placeholder="Nota mínima">
                    <select class="input" name="regras[arredondamento]">
                        <option value="">Arredondamento</option>
                        @foreach(['baixo' => 'Para baixo', 'normal' => 'Normal', 'cima' => 'Para cima'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('regras.arredondamento', data_get($formula, 'regras.arredondamento')) == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="label">Componentes e pesos (%)</label>
                    <button type="button" class="btn btn-outline" onclick="addComponente()">Adicionar componente</button>
                </div>
                <div id="componentes" class="space-y-2"></div>
                <p class="text-xs text-gray-500">A soma deve totalizar 100%.</p>
            </div>

            @if($formula)
                <div>
                    <label class="label">Motivo da nova versão</label>
                    <input class="input" name="motivo" placeholder="Ex.: atualização institucional 2026">
                </div>
            @endif
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('formulas.index') }}" class="btn btn-outline">Cancelar</a>
            <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
    </x-card>
</form>

@php
    $componentesIniciais = old('componentes', $formula->componentes ?? [['nome' => '', 'peso' => '']]);
@endphp

@push('scripts')
<script>
const compContainer = document.getElementById('componentes');
let compIdx = 0;
const iniciais = @json($componentesIniciais);

function addComponente(item = { nome: '', peso: '' }) {
    const idx = compIdx++;
    const row = document.createElement('div');
    row.className = 'grid grid-cols-12 gap-2';
    row.innerHTML = `
        <input class="input col-span-7" name="componentes[${idx}][nome]" value="${item.nome ?? ''}" placeholder="Ex: prova, trabalho" required>
        <input class="input col-span-4" type="number" min="0" max="100" step="0.01" name="componentes[${idx}][peso]" value="${item.peso ?? ''}" placeholder="Peso" required>
        <button type="button" class="btn btn-outline col-span-1" onclick="this.parentElement.remove()">x</button>
    `;
    compContainer.appendChild(row);
}

iniciais.forEach(addComponente);
</script>
@endpush

<div class="mt-6" x-data="configAvaliacao(@js($configuracaoPadrao), @js(old('provas', $configuracaoPadrao['provas'] ?? [])))">
    <x-card title="⚙️ Configuração do Sistema de Avaliação" icon="fas fa-sliders-h">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="label">Peso da Prova Global (%)</label>
                <input type="number" step="0.01" min="0.01" max="99.99" name="peso_pg" x-model.number="pesoPg" class="input" required>
                <p class="text-xs text-gray-500 mt-1">Peso de CF: <span x-text="(100 - pesoPg).toFixed(2)"></span>%</p>
            </div>
            <div>
                <label class="label">Nota mínima de aprovação</label>
                <input type="number" step="0.01" min="0" max="20" name="nota_minima_aprovacao" x-model.number="notaMinima" class="input" required>
            </div>
        </div>

        <template x-for="periodo in [1,2,3]" :key="periodo">
            <div class="mb-6 border rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold" x-text="`${periodo}º Trimestre`"></h4>
                    <button type="button" class="btn btn-outline btn-sm" @click="adicionarProva(periodo)">+ Adicionar Prova</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2">Nome</th><th>Código</th><th>Peso</th><th>Ativo</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(prova, idx) in provas[periodo]" :key="`${periodo}-${idx}`">
                                <tr class="border-b">
                                    <td class="py-2 pr-2"><input class="input" :name="`provas[${periodo}][${idx}][nome]`" x-model="prova.nome" required></td>
                                    <td class="py-2 pr-2"><input class="input" :name="`provas[${periodo}][${idx}][codigo]`" x-model="prova.codigo" required></td>
                                    <td class="py-2 pr-2"><input type="number" step="0.0001" min="0.0001" class="input" :name="`provas[${periodo}][${idx}][peso]`" x-model.number="prova.peso" required></td>
                                    <td class="py-2 pr-2 text-center"><input type="checkbox" value="1" :name="`provas[${periodo}][${idx}][ativo]`" x-model="prova.ativo"></td>
                                    <td class="py-2"><button type="button" class="text-red-600" @click="removerProva(periodo, idx)">Remover</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-amber-600 mt-2" x-show="!temAtiva(periodo)">Este período precisa de pelo menos uma prova ativa.</p>
            </div>
        </template>

        <div class="flex justify-end">
            <button type="button" class="btn btn-outline" @click="restaurarPadrao">Restaurar Padrão</button>
        </div>
    </x-card>
</div>

@push('scripts')
<script>
function configAvaliacao(padrao, oldProvas) {
    return {
        pesoPg: Number(@json(old('peso_pg', $configuracaoPadrao['peso_pg'] ?? 40))),
        notaMinima: Number(@json(old('nota_minima_aprovacao', $configuracaoPadrao['nota_minima_aprovacao'] ?? 10))),
        provas: Object.keys(oldProvas || {}).length ? oldProvas : JSON.parse(JSON.stringify(padrao.provas || {})),
        adicionarProva(periodo) {
            this.provas[periodo].push({ nome: 'Nova Prova', codigo: `nova_${periodo}_${Date.now()}`.toLowerCase(), peso: 1, ativo: true });
        },
        removerProva(periodo, idx) {
            this.provas[periodo].splice(idx, 1);
        },
        temAtiva(periodo) {
            return (this.provas[periodo] || []).some(p => !!p.ativo);
        },
        restaurarPadrao() {
            this.pesoPg = Number(padrao.peso_pg || 40);
            this.notaMinima = Number(padrao.nota_minima_aprovacao || 10);
            this.provas = JSON.parse(JSON.stringify(padrao.provas || {}));
        }
    }
}
</script>
@endpush

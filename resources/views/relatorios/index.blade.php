@extends('layouts.app')

@section('page-title', 'Relatórios')

@section('content')
<div class="space-y-6">

    {{-- ================= BOLETIM (UNIFICADO) ================= --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                <i class="fas fa-file-pdf text-sm"></i>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Boletim</h2>
                <p class="text-xs text-gray-400">Individual por aluno ou em massa por turma</p>
            </div>
        </div>

        <div class="p-6 space-y-5">

            {{-- Linha 1: Turma + Aluno --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Turma</label>
                    <select id="turma_id_boletim_massa" name="turma_id" class="form-input w-full">
                        <option value="">Todas as turmas</option>
                        @foreach($turmas as $turma)
                            <option value="{{ $turma->id }}">
                                {{ $turma->nome_completo }} — {{ $turma->anoLetivo->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Aluno</label>
                    <select id="aluno_id_boletim" name="aluno_id" class="form-input w-full">
                        <option value="">Todos os alunos (massa)</option>
                        @foreach($alunos as $aluno)
                            <option value="{{ $aluno->id }}" data-turmas="{{ $aluno->turmas->pluck('id')->implode(',') }}">
                                {{ $aluno->name }} ({{ $aluno->numero_processo }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Linha 2: Ano letivo + Disciplina + Trimestre --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Ano Letivo</label>
                    <select id="ano_letivo_boletim" name="ano_letivo_id" class="form-input js-ano-filtro-index w-full" required>
                        <option value="">Ano letivo</option>
                        @foreach($anosLetivos as $ano)
                            <option value="{{ $ano->id }}" @selected($anoLetivo?->id === $ano->id)>
                                {{ $ano->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Disciplina</label>
                    <select name="disciplina_id" class="form-input w-full">
                        <option value="">Todas as disciplinas</option>
                        @foreach($disciplinas as $disciplina)
                            <option value="{{ $disciplina->id }}">{{ $disciplina->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Trimestre</label>
                    <select name="trimestre" class="form-input w-full">
                        <option value="final">Final (CFD)</option>
                        <option value="1">1º Trimestre</option>
                        <option value="2" selected>2º Trimestre</option>
                        <option value="3">3º Trimestre</option>
                    </select>
                </div>
            </div>

            {{-- Linha 3: Acções --}}
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <form id="form-boletim-individual" method="GET" action="{{ route('relatorios.boletim') }}">
                    @csrf
                    <input type="hidden" name="_boletim_individual" value="1">
                    <button type="submit" id="btn-boletim-ver" class="btn btn-outline">
                        <i class="fas fa-eye mr-2"></i>Ver (individual)
                    </button>
                </form>

                <form id="form-boletim-massa" method="GET" action="{{ route('relatorios.boletins-massa') }}">
                    @csrf
                    <div class="flex gap-2">
                        <button type="submit" name="formato" value="xlsx" class="btn btn-success">
                            <i class="fas fa-file-excel mr-2"></i>XLSX
                        </button>
                        <button type="submit" name="formato" value="pdf" class="btn btn-primary">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                    </div>
                </form>

                <p class="text-xs text-gray-400 ml-auto hidden md:block">
                    <i class="fas fa-info-circle mr-1"></i>
                    Sem aluno selecionado: exportação em massa. Com aluno: exportação individual.
                </p>
            </div>
            <p class="text-xs text-gray-400 md:hidden">
                <i class="fas fa-info-circle mr-1"></i>
                Sem aluno selecionado: exportação em massa. Com aluno: exportação individual.
            </p>

        </div>
    </div>


    {{-- ================= PAUTA DISCIPLINA ================= --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                <i class="fas fa-table text-sm"></i>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Pauta da Turma</h2>
                <p class="text-xs text-gray-400">Por disciplina/trimestre ou geral do ano letivo</p>
            </div>
        </div>

        <div class="p-6 space-y-5">

            {{-- Linha 1: Turma + Ano letivo --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Turma</label>
                    <select name="turma_id" id="turma_id_pauta" class="form-input w-full" required>
                        <option value="">Turma</option>
                        @foreach($turmas as $turma)
                            <option value="{{ $turma->id }}">
                                {{ $turma->nome_completo }} - {{ $turma->anoLetivo->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Ano Letivo</label>
                    <select name="ano_letivo_id" class="form-input js-ano-filtro-index w-full" required>
                        @foreach($anosLetivos as $ano)
                            <option value="{{ $ano->id }}" @selected($anoLetivo?->id === $ano->id)>
                                {{ $ano->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Linha 2: Disciplina + Trimestre --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Disciplina</label>
                    <select name="disciplina" id="disciplina_id_pauta" class="form-input w-full">
                        <option value="">Todas as disciplinas</option>
                        @foreach($disciplinas as $disciplina)
                            <option value="{{ $disciplina->id }}">{{ $disciplina->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Trimestre</label>
                    <select name="trimestre" class="form-input w-full">
                        <option value="final">Final (CFD)</option>
                        <option value="1">1º Trimestre</option>
                        <option value="2">2º Trimestre</option>
                        <option value="3">3º Trimestre</option>
                    </select>
                </div>
            </div>

            {{-- Acções separadas por tipo de pauta --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                {{-- Pauta por Disciplina --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 space-y-3">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                        <i class="fas fa-book mr-1 text-purple-400"></i> Por disciplina
                    </p>
                    <form id="form-pauta"
                          method="GET"
                          action="{{ route('relatorios.pauta', ['turma' => $turmas->first()?->id ?? 1]) }}">
                        @csrf
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-outline flex-1">
                                <i class="fas fa-eye mr-2"></i>Ver
                            </button>
                            <button type="submit" name="formato" value="pdf" class="btn btn-primary flex-1">
                                <i class="fas fa-file-pdf mr-2"></i>PDF
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Pauta Geral --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 space-y-3">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                        <i class="fas fa-list-check mr-1 text-purple-400"></i> Pauta geral do ano
                    </p>
                    <form id="form-pauta-geral"
                          method="GET"
                          action="{{ route('relatorios.pauta-geral', ['turma' => $turmas->first()?->id ?? 1]) }}">
                        @csrf
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-outline flex-1">
                                <i class="fas fa-eye mr-2"></i>Ver
                            </button>
                            <button type="submit" name="formato" value="xlsx" class="btn btn-success flex-1">
                                <i class="fas fa-file-excel mr-2"></i>XLSX
                            </button>
                            <button type="submit" name="formato" value="pdf" class="btn btn-primary flex-1">
                                <i class="fas fa-file-pdf mr-2"></i>PDF
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>


    @if(auth()->user()?->isAdmin() || auth()->user()?->isSecretaria())
    {{-- ================= HISTÓRICOS (ABAS) ================= --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                <i class="fas fa-history text-sm"></i>
            </span>
            <h2 class="text-sm font-semibold text-gray-800">Históricos</h2>
        </div>

        {{-- Tabs --}}
        <div class="border-b border-gray-100">
            <nav class="flex px-6 gap-0" id="historico-tabs" role="tablist">
                <button type="button"
                        role="tab"
                        data-tab="aluno"
                        class="tab-btn tab-active px-4 py-3 text-sm font-medium border-b-2 border-amber-500 text-amber-600 -mb-px transition-colors">
                    <i class="fas fa-user-graduate mr-2"></i>Aluno
                </button>
                <button type="button"
                        role="tab"
                        data-tab="professor"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px transition-colors">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Professor
                </button>
            </nav>
        </div>

        <div class="p-6">
            {{-- Painel Aluno --}}
            <div id="tab-aluno" role="tabpanel" class="tab-panel space-y-4">
                <p class="text-xs text-gray-400">Histórico académico completo do aluno em todos os anos letivos.</p>
                <form method="GET" action="{{ route('relatorios.historico') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Aluno</label>
                        <select name="aluno_id" class="form-input w-full" required>
                            <option value="">Selecionar aluno</option>
                            @foreach($alunos as $aluno)
                                <option value="{{ $aluno->id }}">
                                    {{ $aluno->name }} ({{ $aluno->numero_processo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" formtarget="_blank" class="btn btn-outline">
                            <i class="fas fa-eye mr-2"></i>Ver
                        </button>
                        <button type="submit" name="formato" value="pdf" class="btn btn-primary">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                    </div>
                </form>
            </div>

            {{-- Painel Professor --}}
            <div id="tab-professor" role="tabpanel" class="tab-panel hidden space-y-4">
                <p class="text-xs text-gray-400">Historial de turmas e disciplinas leccionadas pelo professor.</p>
                <form method="GET" action="{{ route('relatorios.historico-professor') }}" target="_blank" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Professor</label>
                        <select name="professor_id" class="form-input w-full" required>
                            <option value="">Selecionar professor</option>
                            @foreach($professores as $professor)
                                <option value="{{ $professor->id }}">{{ $professor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-outline">
                            <i class="fas fa-eye mr-2"></i>Ver
                        </button>
                        <button type="submit" name="formato" value="pdf" class="btn btn-primary">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ─── URLs base ────────────────────────────────────────────────────────────
    const BASE_URL       = '{{ url("relatorios/pauta") }}';
    const BASE_URL_GERAL = '{{ url("relatorios/pauta-geral") }}';

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function $ (id) { return document.getElementById(id); }

    // ─── Pauta por Disciplina ────────────────────────────────────────────────
    const formPauta = $('form-pauta');
    if (formPauta) {
        formPauta.addEventListener('submit', function () {
            const turmaId      = $('turma_id_pauta')?.value;
            const disciplinaId = $('disciplina_id_pauta')?.value;
            if (!turmaId) return;
            this.action = disciplinaId
                ? `${BASE_URL}/${turmaId}/${disciplinaId}`
                : `${BASE_URL}/${turmaId}`;
        });
    }

    // ─── Pauta Geral ─────────────────────────────────────────────────────────
    const formPautaGeral = $('form-pauta-geral');
    if (formPautaGeral) {
        formPautaGeral.addEventListener('submit', function () {
            const turmaId = $('turma_id_pauta')?.value;
            if (turmaId) this.action = `${BASE_URL_GERAL}/${turmaId}`;
        });
    }

    // ─── Ano letivo → recarrega página com filtro ─────────────────────────
    document.querySelectorAll('.js-ano-filtro-index').forEach(function (select) {
        select.addEventListener('change', function () {
            if (!this.value) return;
            const url = new URL('{{ route("relatorios.index") }}', window.location.origin);
            url.searchParams.set('ano_letivo_id', this.value);
            window.location.href = url.toString();
        });
    });

    // ─── Boletim Unificado: sincroniza campos nos dois forms ocultos ────────
    const turmaBoletim  = $('turma_id_boletim_massa');
    const alunoBoletim  = $('aluno_id_boletim');
    const formIndividual = $('form-boletim-individual');
    const formMassa      = $('form-boletim-massa');

    // Filtra alunos pelo turma selecionada
    function filtrarAlunosBoletim() {
        if (!turmaBoletim || !alunoBoletim) return;
        const turmaId = turmaBoletim.value;
        Array.from(alunoBoletim.options).forEach(function (opt, i) {
            if (i === 0) { opt.hidden = false; return; }
            const turmas  = (opt.dataset.turmas || '').split(',').filter(Boolean);
            const visivel = !turmaId || turmas.includes(turmaId);
            opt.hidden = !visivel;
            if (!visivel && opt.selected) alunoBoletim.value = '';
        });
    }

    if (turmaBoletim) {
        turmaBoletim.addEventListener('change', filtrarAlunosBoletim);
        filtrarAlunosBoletim();
    }

    // Antes de submeter, copia os campos partilhados para o form correcto
    function copiarCamposParaForm(form) {
        const campos = ['turma_id', 'aluno_id', 'ano_letivo_id', 'disciplina_id', 'trimestre'];
        campos.forEach(function (nome) {
            // Remove cópia anterior para evitar duplicados
            form.querySelectorAll(`[name="${nome}"][data-clone]`).forEach(function (el) { el.remove(); });

            let valor = null;
            if (nome === 'turma_id'    && turmaBoletim)  valor = turmaBoletim.value;
            if (nome === 'aluno_id'    && alunoBoletim)  valor = alunoBoletim.value;
            if (nome === 'ano_letivo_id') {
                const sel = $('ano_letivo_boletim');
                if (sel) valor = sel.value;
            }
            // disciplina_id e trimestre — procura o select que existe na secção Boletim
            if (nome === 'disciplina_id' || nome === 'trimestre') {
                // Os selects estão no DOM fora dos forms; pega pelo name dentro do card Boletim
                const card = document.querySelector('[id="turma_id_boletim_massa"]')?.closest('.rounded-2xl');
                if (card) {
                    const sel = card.querySelector(`select[name="${nome}"]`);
                    if (sel) valor = sel.value;
                }
            }
            if (valor === null) return;
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = nome;
            hidden.value = valor;
            hidden.dataset.clone = '1';
            form.appendChild(hidden);
        });
    }

    if (formIndividual) {
        formIndividual.addEventListener('submit', function () { copiarCamposParaForm(this); });
    }
    if (formMassa) {
        formMassa.addEventListener('submit', function () { copiarCamposParaForm(this); });
    }

    // ─── Abas de Histórico ───────────────────────────────────────────────────
    const tabBtns   = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.dataset.tab;

            // Painéis
            tabPanels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.id !== `tab-${target}`);
            });

            // Botões
            tabBtns.forEach(function (b) {
                const active = b.dataset.tab === target;
                b.classList.toggle('border-amber-500', active);
                b.classList.toggle('text-amber-600', active);
                b.classList.toggle('border-transparent', !active);
                b.classList.toggle('text-gray-500', !active);
            });
        });
    });

})();
</script>
@endpush
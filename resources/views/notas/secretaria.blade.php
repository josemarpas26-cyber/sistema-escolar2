@extends('layouts.app')

@section('page-title', 'Gerenciar Notas')

@section('content')

@php
    $podeReabrirNotas      = auth()->user()?->can('notas.reabrir') ?? false;
    $podeFinalizarNotas    = auth()->user()?->can('notas.editar') ?? false;
    $turmas                = $turmas                ?? collect();
    $disciplinas           = $disciplinas           ?? collect();
    $notas                 = $notas                 ?? collect();
    $notasAgrupadas        = $notasAgrupadas        ?? collect();
    $turmaSelecionada      = $turmaSelecionada      ?? null;
    $disciplinaSelecionada = $disciplinaSelecionada ?? null;

    $notasComCfd     = $notas->whereNotNull('cfd');
    $mediaGeral      = $notasComCfd->avg('cfd');
    $totalAprovados  = $notasComCfd->filter(fn($n) => $n->isAprovado())->count();
    $totalReprovados = $notasComCfd->filter(fn($n) => !$n->isAprovado())->count();
    $totalPendentes  = $notas->whereNull('cfd')->count();

    $totalFinalizadas  = $notas->where('status', 'finalizado')->count();
    $totalEmLancamento = $notas->where('status', '!=', 'finalizado')->count();
    $opcoesAlunosOperacao = $notas->pluck('aluno')->filter()->unique('id')->sortBy('name')->values();
@endphp

<div id="notas-root">

    {{-- ██ FILTROS ██ --}}
    <div class="nr-card nr-mb">
        <div class="nr-card-head">
            <i class="fas fa-filter nr-head-icon"></i>
            <span>Filtros de Pesquisa</span>
        </div>
        <div class="nr-card-body">
            <form method="GET" action="{{ route('notas.secretaria-index') }}">
                <div class="nr-filter-grid">

                    <div class="nr-field">
                        <label class="nr-label"><i class="fas fa-users"></i> Turma</label>
                        <select name="turma_id" class="nr-input" onchange="this.form.submit()">
                            <option value="">Todas as turmas</option>
                            @foreach($turmas as $turma)
                                <option value="{{ $turma->id }}" {{ request('turma_id') == $turma->id ? 'selected' : '' }}>
                                    {{ $turma->nome_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="nr-field">
                        <label class="nr-label"><i class="fas fa-book-open"></i> Disciplina <span style="color:#dc2626">*</span></label>
                        <select name="disciplina_id" class="nr-input {{ !request('turma_id') ? 'nr-disabled' : '' }}"
                                {{ !request('turma_id') ? 'disabled' : '' }}
                                onchange="this.form.submit()">
                            <option value="" disabled {{ !request('disciplina_id') ? 'selected' : '' }}>Selecione uma disciplina</option>
                            @if(request('turma_id') && $disciplinas->isNotEmpty())
                                @foreach($disciplinas as $disciplina)
                                    <option value="{{ $disciplina->id }}" {{ request('disciplina_id') == $disciplina->id ? 'selected' : '' }}>
                                        {{ $disciplina->nome }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p id="nr-disc-error" style="display:none;align-items:center;gap:5px;font-size:.72rem;color:#dc2626;margin:3px 0 0">
                            <i class="fas fa-exclamation-circle"></i> Selecione uma disciplina antes de buscar.
                        </p>
                    </div>

                    <div class="nr-field">
                        <label class="nr-label"><i class="fas fa-user-graduate"></i> Aluno</label>
                        <input type="text" name="aluno" value="{{ request('aluno') }}"
                               placeholder="Nome ou Nº Processo" class="nr-input">
                    </div>

                    <div class="nr-field nr-field-btns">
                        <label class="nr-label">&nbsp;</label>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="nr-btn nr-btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="{{ route('notas.secretaria-index') }}" class="nr-btn nr-btn-ghost" title="Limpar">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @if($turmaSelecionada)

        {{-- ██ CABEÇALHO DA SELEÇÃO ██ --}}
        <div class="nr-sel-header nr-mb">
            <div style="min-width:0">
                <div class="nr-sel-title">
                    <i class="fas fa-chalkboard-teacher" style="color:#3b82f6;flex-shrink:0"></i>
                    <strong>{{ $turmaSelecionada->nome_completo }}</strong>
                    <span style="color:#94a3b8">›</span>
                    @if($disciplinaSelecionada)
                        <span style="color:#3b82f6">{{ $disciplinaSelecionada->nome }}</span>
                    @else
                        <span style="color:#64748b">Todas as Disciplinas</span>
                    @endif
                </div>
                <div class="nr-sel-sub">
                    {{ $turmaSelecionada->curso->nome ?? '' }}
                    @if($turmaSelecionada->anoLetivo ?? null)
                        · Ano Letivo: {{ $turmaSelecionada->anoLetivo->nome }}
                    @endif
                </div>
            </div>
            @if($disciplinaSelecionada)
                <div class="nr-sel-actions">
                    <a href="{{ route('relatorios.pauta', [$turmaSelecionada, $disciplinaSelecionada]) }}"
                       class="nr-btn nr-btn-ghost" target="_blank">
                        <i class="fas fa-file-alt"></i> Ver Pauta
                    </a>
                    <a href="{{ route('relatorios.pauta', [$turmaSelecionada, $disciplinaSelecionada, 'formato' => 'pdf']) }}"
                       class="nr-btn nr-btn-pdf">
                        <i class="fas fa-file-pdf"></i> Baixar PDF
                    </a>

                    @if($podeFinalizarNotas)
                        <form method="POST" action="{{ route('notas.finalizar') }}" class="nr-op-form">
                            @csrf
                            <input type="hidden" name="turma_id" value="{{ $turmaSelecionada->id }}">
                            <input type="hidden" name="disciplina_id" value="{{ $disciplinaSelecionada->id }}">
                            <span class="nr-op-label">Escopo:</span>
                            <select name="trimestre" class="nr-input nr-input-inline">
                                <option value="">Finalização Geral</option>
                                <option value="1">Bloquear 1º Tri</option>
                                <option value="2">Bloquear 2º Tri</option>
                                <option value="3">Bloquear 3º Tri</option>
                            </select>
                            <span class="nr-op-label">Aluno:</span>
                            <select name="aluno_id" class="nr-input nr-input-inline">
                                <option value="">Todos os alunos</option>
                                @foreach($opcoesAlunosOperacao as $al)
                                    <option value="{{ $al->id }}">{{ $al->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="nr-btn nr-btn-primary"
                                    {{ $notas->isEmpty() || $totalEmLancamento === 0 ? 'disabled' : '' }}
                                    title="Finalizar e bloquear edição desta pauta">
                                <i class="fas fa-lock"></i> Finalizar/Bloquear
                            </button>
                        </form>
                    @endif

                    @if($podeReabrirNotas)
                        <form method="POST" action="{{ route('notas.reabrir') }}" class="nr-op-form"
                              onsubmit="return confirm('Deseja reabrir esta pauta para edição?')">
                            @csrf
                            <input type="hidden" name="turma_id" value="{{ $turmaSelecionada->id }}">
                            <input type="hidden" name="disciplina_id" value="{{ $disciplinaSelecionada->id }}">
                            <span class="nr-op-label">Escopo:</span>
                            <select name="trimestre" class="nr-input nr-input-inline">
                                <option value="">Reabertura Geral</option>
                                <option value="1">Desbloq 1º Tri</option>
                                <option value="2">Desbloq 2º Tri</option>
                                <option value="3">Desbloq 3º Tri</option>
                            </select>
                            <span class="nr-op-label">Aluno:</span>
                            <select name="aluno_id" class="nr-input nr-input-inline">
                                <option value="">Todos os alunos</option>
                                @foreach($opcoesAlunosOperacao as $al)
                                    <option value="{{ $al->id }}">{{ $al->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="nr-btn nr-btn-ghost"
                                    {{ $notas->isEmpty() || $totalFinalizadas === 0 ? 'disabled' : '' }}
                                    title="Reabrir pauta finalizada">
                                <i class="fas fa-lock-open"></i> Reabrir/Desbloquear
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        {{-- ██ ESTATÍSTICAS ██ --}}
        @if($notas->isNotEmpty())
        <div class="nr-stats nr-mb">
            <div class="nr-stat nr-stat-blue">
                <div class="nr-stat-ico"><i class="fas fa-list-ol"></i></div>
                <div><div class="nr-stat-val">{{ $notas->count() }}</div><div class="nr-stat-lbl">Registros</div></div>
            </div>
            <div class="nr-stat nr-stat-purple">
                <div class="nr-stat-ico"><i class="fas fa-chart-line"></i></div>
                <div><div class="nr-stat-val">{{ $mediaGeral ? number_format($mediaGeral, 2) : '—' }}</div><div class="nr-stat-lbl">Média (CFD)</div></div>
            </div>
            <div class="nr-stat nr-stat-green">
                <div class="nr-stat-ico"><i class="fas fa-check-circle"></i></div>
                <div><div class="nr-stat-val">{{ $totalAprovados }}</div><div class="nr-stat-lbl">Aprovados</div></div>
            </div>
            <div class="nr-stat nr-stat-red">
                <div class="nr-stat-ico"><i class="fas fa-times-circle"></i></div>
                <div><div class="nr-stat-val">{{ $totalReprovados }}</div><div class="nr-stat-lbl">Reprovados</div></div>
            </div>
            <div class="nr-stat nr-stat-amber">
                <div class="nr-stat-ico"><i class="fas fa-clock"></i></div>
                <div><div class="nr-stat-val">{{ $totalPendentes }}</div><div class="nr-stat-lbl">Pendentes</div></div>
            </div>
        </div>
        @endif

        {{-- ██ TABELA ██ --}}
        <div class="nr-card">
            <div class="nr-card-head">
                <i class="fas fa-table nr-head-icon"></i>
                <span>Notas dos Alunos</span>
            </div>

            @if($disciplinaSelecionada)
            {{-- ── VISTA POR DISCIPLINA ── --}}
            <div class="nr-tbl-scroll-top" id="nr-stop-disc"><div class="nr-tbl-scroll-top-inner" id="nr-stop-disc-inner"></div></div>
            <div class="nr-tbl-wrap" id="nr-wrap-disc">
                <table class="nr-tbl">
                    <thead>
                        <tr>
                            <th style="width:44px;text-align:center">Nº</th>
                            <th style="min-width:180px;text-align:left">Aluno</th>
                            <th class="nr-th-c">MT1</th>
                            <th class="nr-th-c">MT2</th>
                            <th class="nr-th-c nr-th-bold">MFT2</th>
                            <th class="nr-th-c">MT3</th>
                            <th class="nr-th-c nr-th-cfd">CFD</th>
                            <th class="nr-th-c" style="width:110px">Status</th>
                            <th class="nr-th-c" style="width:54px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $cnt = 1; @endphp
                        @forelse($notas as $nota)
                        @php
                            $temCfd = !is_null($nota->cfd ?? null);
                            $aprov  = $temCfd && $nota->isAprovado();
                            $locked = ($nota->status ?? '') === 'finalizado' && !$podeReabrirNotas;
                        @endphp
                        <tr class="{{ $loop->odd ? 'nr-odd' : '' }}">
                            <td class="nr-td-c nr-muted">{{ $cnt++ }}</td>
                            <td>
                                <a href="{{ route('users.show', $nota->aluno) }}" class="nr-aluno-link">{{ $nota->aluno->name ?? '—' }}</a>
                                <span class="nr-proc">{{ $nota->aluno->numero_processo ?? '' }}</span>
                            </td>
                            <td class="nr-td-c">{{ $nota->mt1 ? number_format($nota->mt1,2) : '—' }}</td>
                            <td class="nr-td-c">{{ $nota->mt2 ? number_format($nota->mt2,2) : '—' }}</td>
                            <td class="nr-td-c nr-td-bold">{{ $nota->mft2 ? number_format($nota->mft2,2) : '—' }}</td>
                            <td class="nr-td-c">{{ $nota->mt3 ? number_format($nota->mt3,2) : '—' }}</td>
                            <td class="nr-td-c">
                                @if($temCfd)
                                    <strong class="{{ $aprov ? 'nr-ok' : 'nr-fail' }}">{{ number_format($nota->cfd,2) }}</strong>
                                @else
                                    <span class="nr-muted">—</span>
                                @endif
                            </td>
                            <td class="nr-td-c">
                                @if($temCfd)
                                    <span class="nr-badge {{ $aprov ? 'nr-badge-ok' : 'nr-badge-fail' }}">
                                        <i class="fas {{ $aprov ? 'fa-check' : 'fa-times' }}"></i>
                                        {{ $aprov ? 'Aprovado' : 'Reprovado' }}
                                    </span>
                                @else
                                    <span class="nr-badge nr-badge-pend"><i class="fas fa-hourglass-half"></i> Pendente</span>
                                @endif
                            </td>
                            <td class="nr-td-c">
                                @if($locked)
                                    <span class="nr-act-lock" title="Finalizado"><i class="fas fa-lock"></i></span>
                                @else
                                    <a href="{{ route('notas.edit', $nota) }}" class="nr-act-edit" title="Editar"><i class="fas fa-pen"></i></a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="nr-empty">
                            <i class="fas fa-clipboard-list" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:10px"></i>
                            Nenhuma nota encontrada para os filtros selecionados
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @else
            {{-- ── SEM DISCIPLINA SELECIONADA ── --}}
            <div style="text-align:center;padding:36px 20px;color:#94a3b8">
                <i class="fas fa-book-open" style="font-size:2rem;color:#bfdbfe;display:block;margin-bottom:10px"></i>
                <p style="font-size:.875rem;font-weight:600;color:#64748b;margin:0 0 4px">Selecione uma disciplina</p>
                <p style="font-size:.8rem;margin:0">Escolha uma disciplina no filtro acima para visualizar as notas.</p>
            </div>
            @endif

        </div>{{-- /nr-card tabela --}}

    @else
        {{-- ██ EMPTY STATE ██ --}}
        <div class="nr-card">
            <div class="nr-card-body" style="text-align:center;padding:60px 20px">
                <div style="width:68px;height:68px;border-radius:50%;background:#eff6ff;display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;color:#3b82f6;margin-bottom:18px">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 8px">Pesquisar Notas</h3>
                <p style="font-size:.875rem;color:#64748b;max-width:340px;margin:0 auto">
                    Selecione uma turma acima para visualizar as notas dos alunos.
                </p>
            </div>
        </div>
    @endif

</div>{{-- /notas-root --}}

@endsection

@push('styles')
<style>
#notas-root, #notas-root * { box-sizing: border-box; }

#notas-root {
    width: 100%;
    min-width: 0;
    /* CORREÇÃO 1: contém qualquer vazamento horizontal */
    overflow-x: hidden;
}

.nr-mb { margin-bottom: 16px; }

/* ─── Cards ─── */
.nr-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    /* CORREÇÃO 2: era overflow:hidden — quebrava o scroll interno;
       overflow:clip faz o mesmo clipping visual sem criar novo scroll context */
    overflow: clip;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    width: 100%;
    min-width: 0;
}
.nr-card-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
    font-size: .875rem;
    font-weight: 700;
    color: #0f172a;
    background: #f8fafc;
}
.nr-head-icon { color: #3b82f6; }
.nr-card-body { padding: 18px; }

/* ─── Filtros ─── */
.nr-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 12px;
    align-items: end;
    width: 100%;
}
@media (max-width: 860px) { .nr-filter-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 500px) { .nr-filter-grid { grid-template-columns: 1fr; } }

.nr-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.nr-label {
    font-size: .71rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; color: #64748b;
}
.nr-label i { color: #3b82f6; margin-right: 3px; }

.nr-input {
    height: 38px; padding: 0 10px;
    border: 1.5px solid #e2e8f0; border-radius: 6px;
    background: #fff; color: #0f172a; font-size: .85rem;
    width: 100%; min-width: 0;
    transition: border-color .18s, box-shadow .18s;
}
.nr-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.nr-input.nr-input-error {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220,38,38,.12) !important;
    animation: nr-shake .3s ease;
}
@keyframes nr-shake {
    0%,100% { transform: translateX(0); }
    25%      { transform: translateX(-4px); }
    75%      { transform: translateX(4px); }
}
.nr-input.nr-disabled, .nr-input:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }

/* CORREÇÃO 3: select inline nas operações — largura auto sem forçar expansão */
.nr-input-inline {
    width: auto;
    display: inline-block;
    padding: .45rem .6rem;
}

/* ─── Botões ─── */
.nr-btn {
    display: inline-flex; align-items: center; gap: 6px;
    height: 38px; padding: 0 16px; border-radius: 6px;
    font-size: .82rem; font-weight: 600; text-decoration: none;
    border: none; cursor: pointer; white-space: nowrap;
    transition: opacity .15s, background .15s;
    flex-shrink: 0;   /* CORREÇÃO 4: botões não encolhem e não forçam expansão */
}
.nr-btn-primary { background: #3b82f6; color: #fff; box-shadow: 0 2px 5px rgba(59,130,246,.3); }
.nr-btn-primary:hover { background: #1d4ed8; }
.nr-btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
.nr-btn-ghost:hover { border-color: #3b82f6; color: #3b82f6; }
.nr-btn-pdf { background: #dc2626; color: #fff; }
.nr-btn-pdf:hover { opacity: .86; }
.nr-btn:disabled { opacity: .45; cursor: not-allowed; }

/* ─── Cabeçalho da seleção ─── */
.nr-sel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    width: 100%;
    min-width: 0;
    /* CORREÇÃO 5: contém filhos que possam vazar lateralmente */
    overflow: hidden;
}
.nr-sel-title {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    font-size: .9rem; font-weight: 600; color: #0f172a; min-width: 0;
}
.nr-sel-sub { font-size: .78rem; color: #64748b; margin-top: 4px; }

/* CORREÇÃO 6: container dos botões/forms de ação — flex wrap para não estourar */
.nr-sel-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    flex-shrink: 0;
    max-width: 100%;
}

/* CORREÇÃO 7: forms inline também precisam de flex wrap */
.nr-op-form {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.nr-op-label {
    font-size: .7rem;
    color: #64748b;
    white-space: nowrap;
}

/* ─── Estatísticas ─── */
.nr-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    width: 100%;
}
@media (max-width: 1080px) { .nr-stats { grid-template-columns: repeat(3,1fr); } }
@media (max-width: 600px)  { .nr-stats { grid-template-columns: repeat(2,1fr); } }

.nr-stat {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid;
    border-radius: 10px; padding: 13px 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05); min-width: 0;
    transition: transform .12s;
}
.nr-stat:hover { transform: translateY(-2px); }
.nr-stat-blue   { border-left-color: #3b82f6; }
.nr-stat-purple { border-left-color: #8b5cf6; }
.nr-stat-green  { border-left-color: #16a34a; }
.nr-stat-red    { border-left-color: #dc2626; }
.nr-stat-amber  { border-left-color: #d97706; }
.nr-stat-ico {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
}
.nr-stat-blue   .nr-stat-ico { background:#eff6ff; color:#3b82f6; }
.nr-stat-purple .nr-stat-ico { background:#f5f3ff; color:#8b5cf6; }
.nr-stat-green  .nr-stat-ico { background:#f0fdf4; color:#16a34a; }
.nr-stat-red    .nr-stat-ico { background:#fef2f2; color:#dc2626; }
.nr-stat-amber  .nr-stat-ico { background:#fffbeb; color:#d97706; }
.nr-stat-val { font-size: 1.35rem; font-weight: 700; color: #0f172a; line-height: 1; }
.nr-stat-lbl { font-size: .67rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: 3px; }

/* ═══════════════════════════════════════════════════════════════
   CONTENTOR DA TABELA
═══════════════════════════════════════════════════════════════ */
.nr-tbl-scroll-top {
    display: block;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    height: 12px;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    scrollbar-width: thin;
    scrollbar-color: #64748b #dde3ea;
}
.nr-tbl-scroll-top::-webkit-scrollbar        { height: 10px; }
.nr-tbl-scroll-top::-webkit-scrollbar-track  { background: #dde3ea; }
.nr-tbl-scroll-top::-webkit-scrollbar-thumb  { background: #64748b; border-radius: 4px; }
.nr-tbl-scroll-top-inner { height: 1px; display: block; }

.nr-tbl-wrap {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 520px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: auto;
    scrollbar-color: #64748b #dde3ea;
}
.nr-tbl-wrap::-webkit-scrollbar         { width: 10px; height: 10px; }
.nr-tbl-wrap::-webkit-scrollbar-track   { background: #dde3ea; }
.nr-tbl-wrap::-webkit-scrollbar-thumb   { background: #64748b; border-radius: 4px; border: 2px solid #dde3ea; }
.nr-tbl-wrap::-webkit-scrollbar-thumb:hover { background: #334155; }
.nr-tbl-wrap::-webkit-scrollbar-corner  { background: #dde3ea; }

.nr-tbl {
    border-collapse: separate;
    border-spacing: 0;
    font-size: .82rem;
    color: #0f172a;
    width: 100%;
}
.nr-tbl-wide {
    width: max-content;
    min-width: 100%;
}

.nr-tbl thead th {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #f1f5f9;
    color: #64748b;
    font-size: .67rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: 10px 12px;
    white-space: nowrap;
    border-bottom: 2px solid #e2e8f0;
}
.nr-tbl tbody tr td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    vertical-align: middle;
}
.nr-tbl tbody tr.nr-odd td  { background: #fafbfd; }
.nr-tbl tbody tr:hover td   { background: #eff6ff !important; transition: background .1s; }
.nr-tbl tbody tr:last-child td { border-bottom: none; }

.nr-th-c, .nr-td-c { text-align: center; width: 68px; }
.nr-th-bold, .nr-td-bold { font-weight: 700; color: #0f172a; }
.nr-th-cfd { width: 72px; }

.nr-th-st-num {
    position: sticky; left: 0; z-index: 7;
    width: 44px; min-width: 44px; text-align: center;
    background: #f1f5f9;
    box-shadow: 2px 0 4px rgba(0,0,0,.07);
}
.nr-td-st-num {
    position: sticky; left: 0; z-index: 2;
    width: 44px; min-width: 44px; text-align: center;
    background: inherit;
    box-shadow: 2px 0 4px rgba(0,0,0,.05);
}
.nr-th-st-aluno {
    position: sticky; left: 44px; z-index: 7;
    min-width: 160px; max-width: 200px;
    background: #f1f5f9;
    box-shadow: 3px 0 6px rgba(0,0,0,.07);
    border-right: 1px solid #e2e8f0;
}
.nr-td-st-aluno {
    position: sticky; left: 44px; z-index: 2;
    min-width: 160px; max-width: 200px;
    background: inherit;
    box-shadow: 3px 0 6px rgba(0,0,0,.05);
    border-right: 1px solid #f0f4ff;
}
.nr-tbl tbody tr.nr-odd .nr-td-st-num,
.nr-tbl tbody tr.nr-odd .nr-td-st-aluno { background: #fafbfd; }
.nr-tbl tbody tr:hover .nr-td-st-num,
.nr-tbl tbody tr:hover .nr-td-st-aluno  { background: #eff6ff !important; }

.nr-th-disc {
    background: #eff6ff !important;
    color: #1d4ed8;
    font-size: .67rem; font-weight: 700;
    text-align: center;
    border-left: 2px solid #3b82f6;
    padding: 7px 6px;
    max-width: 130px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.nr-th-sub {
    background: #f8fafc !important;
    font-size: .63rem; color: #64748b;
    text-align: center; padding: 5px 8px;
}
.nr-th-sub-first { border-left: 2px solid #bfdbfe; }
.nr-td-sub { text-align: center; font-size: .81rem; }
.nr-td-sub-first { border-left: 2px solid #f0f4ff; }

.nr-ok   { color: #16a34a; }
.nr-fail { color: #dc2626; }
.nr-muted { color: #94a3b8; }

.nr-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 20px;
    font-size: .68rem; font-weight: 700; white-space: nowrap;
}
.nr-badge-ok   { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.nr-badge-fail { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.nr-badge-pend { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

.nr-aluno-link {
    display: block; font-weight: 600; color: #3b82f6;
    text-decoration: none; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; max-width: 185px;
    transition: color .14s;
}
.nr-aluno-link:hover { color: #1d4ed8; text-decoration: underline; }
.nr-proc { display: block; font-size: .7rem; color: #94a3b8; margin-top: 1px; }

.nr-act-edit {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    color: #3b82f6; background: #eff6ff; text-decoration: none;
    transition: background .15s, color .15s;
}
.nr-act-edit:hover { background: #3b82f6; color: #fff; }
.nr-act-lock {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    color: #cbd5e1; background: #f8fafc; cursor: not-allowed;
}

.nr-empty {
    text-align: center; padding: 48px 20px !important;
    color: #94a3b8; background: #fff !important; font-size: .875rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Validação: disciplina obrigatória se turma selecionada ── */
    var form = document.querySelector('#notas-root form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var turma = form.querySelector('[name="turma_id"]');
            var disc  = form.querySelector('[name="disciplina_id"]');
            if (turma && disc && turma.value && !disc.value) {
                e.preventDefault();
                disc.classList.add('nr-input-error');
                disc.focus();
                var msg = document.getElementById('nr-disc-error');
                if (msg) msg.style.display = 'flex';
                setTimeout(function () {
                    disc.classList.remove('nr-input-error');
                }, 2500);
            }
        });
        var discSel = form.querySelector('[name="disciplina_id"]');
        if (discSel) {
            discSel.addEventListener('change', function () {
                var msg = document.getElementById('nr-disc-error');
                if (msg) msg.style.display = 'none';
                discSel.classList.remove('nr-input-error');
            });
        }
    }

    /* ── Sincroniza barra superior com inferior ── */
    function syncScrollBars(topEl, topInner, wrapEl) {
        if (!topEl || !topInner || !wrapEl) return;

        function setInnerWidth() {
            topInner.style.width = wrapEl.scrollWidth + 'px';
        }
        setInnerWidth();

        function toggleTop() {
            topEl.style.display = wrapEl.scrollWidth > wrapEl.clientWidth ? 'block' : 'none';
        }
        toggleTop();

        var syncing = false;
        topEl.addEventListener('scroll', function () {
            if (syncing) return;
            syncing = true;
            wrapEl.scrollLeft = topEl.scrollLeft;
            syncing = false;
        });
        wrapEl.addEventListener('scroll', function () {
            if (syncing) return;
            syncing = true;
            topEl.scrollLeft = wrapEl.scrollLeft;
            syncing = false;
        });

        new ResizeObserver(function () {
            setInnerWidth();
            toggleTop();
        }).observe(wrapEl);
    }

    syncScrollBars(
        document.getElementById('nr-stop-disc'),
        document.getElementById('nr-stop-disc-inner'),
        document.getElementById('nr-wrap-disc')
    );

    syncScrollBars(
        document.getElementById('nr-stop-wide'),
        document.getElementById('nr-stop-wide-inner'),
        document.getElementById('nr-wrap-wide')
    );

    var hint = document.getElementById('nr-scroll-hint');
    if (hint) {
        var wrap = document.getElementById('nr-wrap-wide');
        if (wrap && wrap.scrollWidth > wrap.clientWidth) {
            hint.style.display = 'block';
        }
    }
});
</script>
@endpush
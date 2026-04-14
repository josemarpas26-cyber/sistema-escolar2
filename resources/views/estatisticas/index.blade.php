@extends('layouts.app')

@section('page-title', 'Estatísticas Académicas')

@section('header-actions')
<div class="flex items-center gap-2 text-sm text-gray-500">
    <i class="fas fa-calendar-alt text-blue-500"></i>
    <span class="font-semibold text-gray-700">{{ $anoLetivo->nome }}</span>
</div>
@endsection

@push('styles')
<style>
/* ── Variáveis de tema ── */
:root {
    --est-blue:   #2563eb;
    --est-teal:   #0f766e;
    --est-amber:  #d97706;
    --est-red:    #dc2626;
    --est-green:  #16a34a;
    --est-purple: #7c3aed;
    --est-slate:  #334155;
}

/* ── Filtros ── */
.est-filters {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.est-filter-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr) auto;
    gap: 10px;
    align-items: end;
}
@media (max-width: 1100px) { .est-filter-grid { grid-template-columns: repeat(4,1fr) auto; } }
@media (max-width: 700px)  { .est-filter-grid { grid-template-columns: 1fr auto; } }

.est-field { display: flex; flex-direction: column; gap: 4px; }
.est-label {
    font-size: .67rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    color: #64748b;
}
.est-select, .est-input {
    height: 38px; padding: 0 10px;
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .85rem; color: #0f172a; background: #fff;
    width: 100%; transition: border-color .15s, box-shadow .15s;
}
.est-select:focus, .est-input:focus {
    outline: none; border-color: var(--est-blue);
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.est-btn {
    height: 38px; padding: 0 16px;
    border-radius: 8px; font-size: .82rem; font-weight: 600;
    border: none; cursor: pointer; display: inline-flex;
    align-items: center; gap: 6px; white-space: nowrap;
    transition: opacity .15s, background .15s;
}
.est-btn-primary { background: var(--est-blue); color: #fff; }
.est-btn-primary:hover { background: #1d4ed8; }
.est-btn-ghost {
    background: #fff; color: #64748b;
    border: 1.5px solid #e2e8f0;
}
.est-btn-ghost:hover { border-color: var(--est-blue); color: var(--est-blue); }

/* ── Tabs contexto ── */
.est-tabs {
    display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px;
}
.est-tab {
    padding: 6px 14px; border-radius: 20px;
    font-size: .75rem; font-weight: 600;
    border: 1.5px solid transparent; cursor: pointer;
    transition: all .15s; background: #f1f5f9; color: #64748b;
    text-decoration: none;
}
.est-tab:hover { border-color: var(--est-blue); color: var(--est-blue); }
.est-tab.active { background: var(--est-blue); color: #fff; border-color: var(--est-blue); }

/* ── Cards de secção ── */
.est-section {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    margin-bottom: 18px;
}
.est-section-head {
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(to right, #f8fafc, #fff);
    display: flex; flex-wrap: wrap; gap: 12px;
    align-items: flex-start; justify-content: space-between;
}
.est-section-meta { min-width: 0; }
.est-section-tipo {
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: var(--est-blue); margin-bottom: 3px;
}
.est-section-titulo { font-size: 1rem; font-weight: 700; color: #0f172a; }
.est-section-desc { font-size: .78rem; color: #64748b; margin-top: 2px; }

/* ── KPIs de resumo ── */
.est-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px; padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
@media (max-width: 800px) { .est-kpis { grid-template-columns: repeat(2,1fr); } }

.est-kpi {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 12px 14px;
}
.est-kpi-label {
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    color: #64748b; margin-bottom: 6px;
}
.est-kpi-val {
    font-size: 1.5rem; font-weight: 800; line-height: 1;
    color: var(--est-blue);
}
.est-kpi-sub { font-size: .7rem; color: #94a3b8; margin-top: 3px; }

/* ── Itens colapsáveis ── */
.est-item {
    border-bottom: 1px solid #f1f5f9;
}
.est-item:last-child { border-bottom: none; }
.est-item-head {
    padding: 13px 20px;
    display: flex; flex-wrap: wrap; gap: 10px;
    align-items: center; justify-content: space-between;
    cursor: pointer;
    transition: background .1s;
}
.est-item-head:hover { background: #f8fafc; }
.est-item-title { font-size: .9rem; font-weight: 600; color: #0f172a; }
.est-item-sub { font-size: .75rem; color: #64748b; margin-top: 1px; }
.est-item-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.est-chip {
    font-size: .65rem; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; white-space: nowrap;
}
.est-chip-green  { background: #f0fdf4; color: var(--est-green); border: 1px solid #bbf7d0; }
.est-chip-red    { background: #fef2f2; color: var(--est-red);   border: 1px solid #fecaca; }
.est-chip-blue   { background: #eff6ff; color: var(--est-blue);  border: 1px solid #bfdbfe; }
.est-chip-amber  { background: #fffbeb; color: var(--est-amber); border: 1px solid #fde68a; }
.est-chip-gray   { background: #f1f5f9; color: #64748b;          border: 1px solid #e2e8f0; }

/* ── Chevron ── */
.est-chevron {
    font-size: .75rem; color: #94a3b8;
    transition: transform .2s; flex-shrink: 0;
}
.est-item-open .est-chevron { transform: rotate(180deg); }

/* ── Corpo do item (tabela) ── */
.est-item-body { display: none; }
.est-item-open .est-item-body { display: block; }

/* ── Tabela de trimestres ── */
.est-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.est-table thead tr { background: #f1f5f9; }
.est-table thead th {
    padding: 8px 12px; text-align: left;
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    color: #64748b; border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
.est-table thead th.tc { text-align: center; }
.est-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.est-table tbody tr:last-child { border-bottom: none; }
.est-table tbody tr:hover { background: #f8fafc; }
.est-table tbody td { padding: 8px 12px; }
.est-table tbody td.tc { text-align: center; }

.est-trim-pill {
    display: inline-block; padding: 2px 8px;
    border-radius: 12px; font-size: .65rem; font-weight: 700;
}
.est-t1 { background: #eff6ff; color: #2563eb; }
.est-t2 { background: #f0fdfa; color: #0f766e; }
.est-t3 { background: #fff7ed; color: #c2410c; }

.est-bar-wrap {
    display: flex; align-items: center; gap: 6px; min-width: 120px;
}
.est-bar-track {
    flex: 1; height: 6px; border-radius: 3px;
    background: #e2e8f0; overflow: hidden;
}
.est-bar-fill { height: 100%; border-radius: 3px; }
.est-bar-label {
    font-size: .7rem; font-weight: 700;
    width: 36px; text-align: right; flex-shrink: 0;
}

/* ── Empty state ── */
.est-empty {
    text-align: center; padding: 48px 20px; color: #94a3b8;
}
.est-empty i { font-size: 2.5rem; display: block; margin-bottom: 12px; }
.est-empty-title { font-size: .95rem; font-weight: 600; color: #475569; }
.est-empty-sub { font-size: .8rem; margin-top: 4px; }

/* ── Disciplina header (admin/coord) ── */
.est-disc-header {
    padding: 8px 20px;
    background: #eff6ff;
    border-top: 1px solid #bfdbfe;
    display: flex; align-items: center; gap: 10px;
    font-size: .78rem;
}
.est-disc-name { font-weight: 700; color: #1e40af; }
.est-disc-code { color: #3b82f6; font-size: .7rem; }
</style>
@endpush

@section('content')

@php
    $tipoLabels = [
        'professor'   => 'Professor',
        'coord_turma' => 'Diretor de Turma',
        'coord_curso' => 'Coordenador de Curso',
        'coord_disciplina' => 'Coordenador de Disciplina',
        'admin'       => 'Administrativo',
    ];
    $tipoIcons = [
        'professor'   => 'fas fa-chalkboard-teacher',
        'coord_turma' => 'fas fa-users',
        'coord_curso' => 'fas fa-sitemap',
        'coord_disciplina' => 'fas fa-book-open',
        'admin'       => 'fas fa-shield-alt',
    ];
    $tipoColors = [
        'professor'   => '#2563eb',
        'coord_turma' => '#0f766e',
        'coord_curso' => '#7c3aed',
        'coord_disciplina' => '#1d4ed8',
        'admin'       => '#d97706',
    ];
    $trimLabels = [
        'todos' => 'Todos os Trimestres',
        '1'     => '1º Trimestre',
        '2'     => '2º Trimestre',
        '3'     => '3º Trimestre',
    ];
    $trimCores = ['', 'est-t1', 'est-t2', 'est-t3'];
@endphp

{{-- ── FILTROS ── --}}
<div class="est-filters">
    <form method="GET" action="{{ route('estatisticas.index') }}">
        @csrf
        <div class="est-filter-grid">

            {{-- Secção / Contexto --}}
            <div class="est-field">
                <label class="est-label">
                    <i class="fas fa-layer-group mr-1"></i> Contexto
                </label>
                <select name="secao" class="est-select">
                    <option value="">Todos os contextos</option>
                    @foreach($contextos as $ctx)
                        <option value="{{ $ctx }}" @selected($filtroSecaoTipo === $ctx)>
                            {{ $tipoLabels[$ctx] ?? ucfirst($ctx) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Turma --}}
            <div class="est-field">
                <label class="est-label">
                    <i class="fas fa-school mr-1"></i> Turma
                </label>
                <select name="turma_id" class="est-select">
                    <option value="">Todas as turmas</option>
                    @foreach($filtros['turmas'] as $turma)
                        <option value="{{ $turma->id }}" @selected($filtroTurmaId === $turma->id)>
                            {{ $turma->nome_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Disciplina --}}
            <div class="est-field">
                <label class="est-label">
                    <i class="fas fa-book mr-1"></i> Disciplina
                </label>
                <select name="disciplina_id" class="est-select">
                    <option value="">Todas as disciplinas</option>
                    @foreach($filtros['disciplinas'] as $disc)
                        <option value="{{ $disc->id }}" @selected($filtroDisciplinaId === $disc->id)>
                            {{ $disc->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Trimestre --}}
            <div class="est-field">
                <label class="est-label">
                    <i class="fas fa-calendar-check mr-1"></i> Período
                </label>
                <select name="trimestre" class="est-select">
                    @foreach($trimLabels as $val => $lbl)
                        <option value="{{ $val }}" @selected($filtroTrimestre === $val)>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Aluno --}}
            <div class="est-field">
                <label class="est-label">
                    <i class="fas fa-user-graduate mr-1"></i> Aluno
                </label>
                <select name="aluno_id" class="est-select">
                    <option value="">Todos os alunos</option>
                    @foreach($filtros['alunos'] as $aluno)
                        <option value="{{ $aluno->id }}" @selected($filtroAlunoId === $aluno->id)>
                            {{ $aluno->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Botões --}}
            <div class="est-field" style="flex-direction:row; gap:6px; align-items:flex-end;">
                <button type="submit" class="est-btn est-btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="{{ route('estatisticas.index') }}" class="est-btn est-btn-ghost" title="Limpar">
                    <i class="fas fa-times"></i>
                </a>
            </div>

        </div>

        {{-- Indicadores de filtros activos --}}
        @if($filtroTurmaId || $filtroDisciplinaId || $filtroAlunoId || $filtroTrimestre !== 'todos' || $filtroSecaoTipo)
        <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
            <span style="font-size:.7rem; color:#64748b; font-weight:600;">Filtros ativos:</span>

            @if($filtroSecaoTipo)
                <span class="est-chip est-chip-blue">
                    <i class="fas fa-layer-group mr-1"></i>
                    {{ $tipoLabels[$filtroSecaoTipo] ?? $filtroSecaoTipo }}
                </span>
            @endif
            @if($filtroTurmaId)
                @php $t = $filtros['turmas']->firstWhere('id', $filtroTurmaId); @endphp
                <span class="est-chip est-chip-blue">
                    <i class="fas fa-school mr-1"></i>
                    {{ $t?->nome_completo ?? "Turma #{$filtroTurmaId}" }}
                </span>
            @endif
            @if($filtroDisciplinaId)
                @php $d = $filtros['disciplinas']->firstWhere('id', $filtroDisciplinaId); @endphp
                <span class="est-chip est-chip-blue">
                    <i class="fas fa-book mr-1"></i>
                    {{ $d?->nome ?? "Disc. #{$filtroDisciplinaId}" }}
                </span>
            @endif
            @if($filtroAlunoId)
                @php $a = $filtros['alunos']->firstWhere('id', $filtroAlunoId); @endphp
                <span class="est-chip est-chip-blue">
                    <i class="fas fa-user-graduate mr-1"></i>
                    {{ $a?->name ?? "Aluno #{$filtroAlunoId}" }}
                </span>
            @endif
            @if($filtroTrimestre !== 'todos')
                <span class="est-chip est-chip-amber">
                    <i class="fas fa-calendar-check mr-1"></i>
                    {{ $trimLabels[$filtroTrimestre] ?? $filtroTrimestre }}
                </span>
            @endif
        </div>
        @endif
    </form>
</div>

{{-- ── TABS DE CONTEXTO ── --}}
@if($contextos->count() > 1)
<div class="est-tabs">
    <a href="{{ route('estatisticas.index', array_merge(request()->except('secao'), [])) }}"
       class="est-tab {{ !$filtroSecaoTipo ? 'active' : '' }}">
        <i class="fas fa-th-large mr-1"></i> Todos
    </a>
    @foreach($contextos as $ctx)
        <a href="{{ route('estatisticas.index', array_merge(request()->except('secao'), ['secao' => $ctx])) }}"
           class="est-tab {{ $filtroSecaoTipo === $ctx ? 'active' : '' }}">
            <i class="{{ $tipoIcons[$ctx] ?? 'fas fa-circle' }} mr-1"></i>
            {{ $tipoLabels[$ctx] ?? ucfirst($ctx) }}
        </a>
    @endforeach
</div>
@endif

{{-- ── CONTEÚDO ── --}}
@if($secoes->isEmpty())
    <div class="est-empty" style="background:#fff; border:1px solid #e2e8f0; border-radius:14px;">
        <i class="fas fa-filter text-gray-300"></i>
        <div class="est-empty-title">Nenhum resultado para os filtros selecionados</div>
        <div class="est-empty-sub">Tente ajustar ou limpar os filtros acima.</div>
        <a href="{{ route('estatisticas.index') }}" class="est-btn est-btn-primary" style="margin-top:14px;display:inline-flex;">
            <i class="fas fa-times mr-1"></i> Limpar filtros
        </a>
    </div>
@else

@foreach($secoes as $secaoIdx => $secao)
<div class="est-section">

    {{-- Cabeçalho da secção --}}
    <div class="est-section-head">
        <div class="est-section-meta">
            <div class="est-section-tipo" style="color: {{ $tipoColors[$secao['tipo']] ?? '#2563eb' }}">
                <i class="{{ $tipoIcons[$secao['tipo']] ?? 'fas fa-circle' }} mr-1"></i>
                {{ $tipoLabels[$secao['tipo']] ?? ucfirst($secao['tipo']) }}
            </div>
            <div class="est-section-titulo">{{ $secao['titulo'] }}</div>
            <div class="est-section-desc">{{ $secao['descricao'] }}</div>
        </div>

        {{-- KPI resumo global da secção --}}
        @if($secao['resumo']['total_notas'] > 0)
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <span class="est-chip est-chip-gray">
                {{ number_format($secao['resumo']['total_notas']) }} notas
            </span>
            <span class="est-chip est-chip-green">
                {{ $secao['resumo']['pct_aprovacao'] }}% aprovação
            </span>
            <span class="est-chip est-chip-red">
                {{ $secao['resumo']['pct_reprovacao'] }}% reprovação
            </span>
            @if($secao['resumo']['media_geral'] !== null)
            <span class="est-chip est-chip-blue">
                Média {{ number_format($secao['resumo']['media_geral'], 1) }}
            </span>
            @endif
        </div>
        @endif
    </div>

    {{-- KPIs secundários --}}
    @if($secao['resumo']['total_notas'] > 0)
    <div class="est-kpis">
        <div class="est-kpi">
            <div class="est-kpi-label">Total de notas</div>
            <div class="est-kpi-val" style="color:#2563eb">{{ number_format($secao['resumo']['total_notas']) }}</div>
        </div>
        <div class="est-kpi">
            <div class="est-kpi-label">% Aprovação</div>
            <div class="est-kpi-val" style="color:{{ $secao['resumo']['pct_aprovacao'] >= 70 ? '#16a34a' : ($secao['resumo']['pct_aprovacao'] >= 50 ? '#d97706' : '#dc2626') }}">
                {{ $secao['resumo']['pct_aprovacao'] }}%
            </div>
            <div class="est-kpi-sub">{{ $secao['resumo']['total_positivas'] }} positivas</div>
        </div>
        <div class="est-kpi">
            <div class="est-kpi-label">% Reprovação</div>
            <div class="est-kpi-val" style="color:#dc2626">{{ $secao['resumo']['pct_reprovacao'] }}%</div>
            <div class="est-kpi-sub">{{ $secao['resumo']['total_negativas'] }} negativas</div>
        </div>
        <div class="est-kpi">
            <div class="est-kpi-label">Média geral</div>
            <div class="est-kpi-val" style="color:#0f766e">
                {{ $secao['resumo']['media_geral'] !== null ? number_format($secao['resumo']['media_geral'], 1) : '—' }}
            </div>
        </div>
    </div>
    @endif

    {{-- Itens --}}
    @if(empty($secao['itens']))
        <div class="est-empty">
            <i class="fas fa-inbox"></i>
            <div class="est-empty-title">Sem dados para este contexto</div>
            <div class="est-empty-sub">Nenhuma nota lançada até ao momento.</div>
        </div>
    @else

    @foreach($secao['itens'] as $itemIdx => $item)
    @php
        $itemId = "est-item-{$secaoIdx}-{$itemIdx}";

        // Determinar título e subtítulo do item
        // DEPOIS — correto para todos os tipos
        if ($secao['tipo'] === 'professor') {
            $itemTitulo = $item['disciplina']->nome . ' — ' . $item['disciplina']->codigo;
            $itemSub    = $item['turma']->nome_completo;
            $itemTrims  = $item['trimestres'];
            $itemResumo = $item['resumo'];
        } elseif ($secao['tipo'] === 'coord_curso') {
            $itemTitulo = $item['curso']->nome ?? '—';
            $itemSub    = collect($item['turmas'])->count() . ' turma(s)';
            $itemTrims  = null;
            $itemResumo = $item['resumo'];
        } elseif ($secao['tipo'] === 'coord_disciplina') {
            $itemTitulo = $item['disciplina']->nome . ' â€” ' . $item['disciplina']->codigo;
            $itemTitulo = $item['disciplina']->nome . ' - ' . $item['disciplina']->codigo;
            $itemSub    = collect($item['turmas'])->count() . ' turma(s) no ano lectivo';
            $itemTrims  = $item['trimestres'];
            $itemResumo = $item['resumo'];
        } else {
            // coord_turma e admin
            $itemTitulo = $item['turma']->nome_completo ?? '—';
            $itemSub    = $item['turma']->curso->nome   ?? '—';
            $itemTrims  = null;
            $itemResumo = $item['resumo'];

                }
    @endphp

    <div class="est-item {{ $itemIdx === 0 ? 'est-item-open' : '' }}" id="{{ $itemId }}">

        {{-- Cabeçalho do item (clicável) --}}
        <div class="est-item-head" onclick="toggleEstItem('{{ $itemId }}')">
            <div>
                <div class="est-item-title">{{ $itemTitulo }}</div>
                <div class="est-item-sub">{{ $itemSub }}</div>
            </div>
            <div class="est-item-chips">
                @if($itemResumo['total_notas'] > 0)
                    <span class="est-chip est-chip-gray">{{ $itemResumo['total_notas'] }} notas</span>
                    <span class="est-chip est-chip-green">{{ $itemResumo['pct_aprovacao'] }}%</span>
                    <span class="est-chip est-chip-red">{{ $itemResumo['pct_reprovacao'] }}%</span>
                    @if($itemResumo['media_geral'] !== null)
                    <span class="est-chip est-chip-blue">Média {{ number_format($itemResumo['media_geral'],1) }}</span>
                    @endif
                @else
                    <span class="est-chip est-chip-gray">Sem dados</span>
                @endif
                <i class="fas fa-chevron-down est-chevron"></i>
            </div>
        </div>

        {{-- Corpo do item --}}
        <div class="est-item-body">

            {{-- === PROFESSOR: trimestres diretos === --}}
            @if($secao['tipo'] === 'professor' && $itemTrims !== null)
                @if($itemTrims->isEmpty())
                    <div class="est-empty" style="padding:28px 20px;">
                        <i class="fas fa-clipboard-list" style="font-size:1.6rem;"></i>
                        <div class="est-empty-title">Sem notas lançadas</div>
                    </div>
                @else
                <div style="overflow-x:auto; padding:0 1px 1px;">
                    <table class="est-table">
                        <thead>
                            <tr>
                                <th>Trimestre</th>
                                <th class="tc">Total</th>
                                <th class="tc">Masc.</th>
                                <th class="tc">Masc. Aprov.</th>
                                <th class="tc">Fem.</th>
                                <th class="tc">Fem. Aprov.</th>
                                <th class="tc">Positivas</th>
                                <th class="tc">Negativas</th>
                                <th>% Aprovação</th>
                                <th class="tc">% Reprov.</th>
                                <th class="tc">Média</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itemTrims as $trim)
                            <tr>
                                <td>
                                    <span class="est-trim-pill {{ $trimCores[$trim['trimestre']] ?? '' }}">
                                        {{ $trim['trimestre'] }}º Trimestre
                                    </span>
                                </td>
                                <td class="tc font-medium">{{ $trim['total'] }}</td>
                                <td class="tc" style="color:#64748b">{{ $trim['masculino'] }}</td>
                                <td class="tc" style="color:#16a34a">{{ $trim['masculino_aprov'] }}</td>
                                <td class="tc" style="color:#64748b">{{ $trim['feminino'] }}</td>
                                <td class="tc" style="color:#16a34a">{{ $trim['feminino_aprov'] }}</td>
                                <td class="tc" style="color:#16a34a; font-weight:700">{{ $trim['positivas'] }}</td>
                                <td class="tc" style="color:#dc2626; font-weight:700">{{ $trim['negativas'] }}</td>
                                <td>
                                    @php
                                        $pct = $trim['pct_aprovacao'];
                                        $barColor = $pct >= 70 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
                                    @endphp
                                    <div class="est-bar-wrap">
                                        <div class="est-bar-track">
                                            <div class="est-bar-fill" style="width:{{ $pct }}%; background:{{ $barColor }}"></div>
                                        </div>
                                        <span class="est-bar-label" style="color:{{ $barColor }}">{{ $pct }}%</span>
                                    </div>
                                </td>
                                <td class="tc">
                                    <span style="font-size:.7rem; font-weight:700; color:{{ $trim['pct_reprovacao'] > 30 ? '#dc2626' : '#64748b' }}">
                                        {{ $trim['pct_reprovacao'] }}%
                                    </span>
                                </td>
                                <td class="tc" style="font-weight:700; color:#0f766e">
                                    {{ $trim['media'] !== null ? number_format($trim['media'],1) : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

            {{-- === COORD_DISCIPLINA: resumo da disciplina + detalhe por turma === --}}
            @elseif($secao['tipo'] === 'coord_disciplina' && $itemTrims !== null)
                @if($itemTrims->isEmpty())
                    <div class="est-empty" style="padding:28px 20px;">
                        <i class="fas fa-clipboard-list" style="font-size:1.6rem;"></i>
                        <div class="est-empty-title">Sem notas lanÃ§adas</div>
                    </div>
                @else
                <div style="padding:14px 20px 0; font-size:.78rem; font-weight:700; color:#1d4ed8; text-transform:uppercase; letter-spacing:.04em;">
                    Visao consolidada da disciplina
                </div>
                <div style="overflow-x:auto; padding:12px 1px 1px;">
                    <table class="est-table">
                        <thead>
                            <tr>
                                <th>Trimestre</th>
                                <th class="tc">Total</th>
                                <th class="tc">Masc.</th>
                                <th class="tc">Masc. Aprov.</th>
                                <th class="tc">Fem.</th>
                                <th class="tc">Fem. Aprov.</th>
                                <th class="tc">Positivas</th>
                                <th class="tc">Negativas</th>
                                <th>% AprovaÃ§Ã£o</th>
                                <th class="tc">% Reprov.</th>
                                <th class="tc">MÃ©dia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itemTrims as $trim)
                            <tr>
                                <td>
                                    <span class="est-trim-pill {{ $trimCores[$trim['trimestre']] ?? '' }}">
                                        {{ $trim['trimestre'] }}Âº Trimestre
                                    </span>
                                </td>
                                <td class="tc font-medium">{{ $trim['total'] }}</td>
                                <td class="tc" style="color:#64748b">{{ $trim['masculino'] }}</td>
                                <td class="tc" style="color:#16a34a">{{ $trim['masculino_aprov'] }}</td>
                                <td class="tc" style="color:#64748b">{{ $trim['feminino'] }}</td>
                                <td class="tc" style="color:#16a34a">{{ $trim['feminino_aprov'] }}</td>
                                <td class="tc" style="color:#16a34a; font-weight:700">{{ $trim['positivas'] }}</td>
                                <td class="tc" style="color:#dc2626; font-weight:700">{{ $trim['negativas'] }}</td>
                                <td>
                                    @php
                                        $pct = $trim['pct_aprovacao'];
                                        $barColor = $pct >= 70 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
                                    @endphp
                                    <div class="est-bar-wrap">
                                        <div class="est-bar-track">
                                            <div class="est-bar-fill" style="width:{{ $pct }}%; background:{{ $barColor }}"></div>
                                        </div>
                                        <span class="est-bar-label" style="color:{{ $barColor }}">{{ $pct }}%</span>
                                    </div>
                                </td>
                                <td class="tc">
                                    <span style="font-size:.7rem; font-weight:700; color:{{ $trim['pct_reprovacao'] > 30 ? '#dc2626' : '#64748b' }}">
                                        {{ $trim['pct_reprovacao'] }}%
                                    </span>
                                </td>
                                <td class="tc" style="font-weight:700; color:#0f766e">
                                    {{ $trim['media'] !== null ? number_format($trim['media'],1) : 'â€”' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @php $estatisticasTurmas = collect($item['estatisticas'] ?? []); @endphp

                @if($estatisticasTurmas->isNotEmpty())
                    <div style="padding:18px 20px 0; font-size:.78rem; font-weight:700; color:#0f766e; text-transform:uppercase; letter-spacing:.04em;">
                        Detalhe por turma
                    </div>
                    @foreach($estatisticasTurmas as $turmaItem)
                        <div class="est-disc-header">
                            <span class="est-disc-name">{{ $turmaItem['turma']->nome_completo ?? 'â€”' }}</span>
                            <span class="est-disc-code">{{ $turmaItem['turma']->curso->nome ?? '' }}</span>
                            @if($turmaItem['resumo']['total_notas'] > 0)
                                <span style="margin-left:auto; display:flex; gap:6px;">
                                    <span class="est-chip est-chip-green" style="font-size:.6rem; padding:2px 6px;">{{ $turmaItem['resumo']['pct_aprovacao'] }}% apr</span>
                                    <span class="est-chip est-chip-red" style="font-size:.6rem; padding:2px 6px;">{{ $turmaItem['resumo']['pct_reprovacao'] }}% rep</span>
                                    @if($turmaItem['resumo']['media_geral'] !== null)
                                    <span class="est-chip est-chip-blue" style="font-size:.6rem; padding:2px 6px;">MÃ©d. {{ number_format($turmaItem['resumo']['media_geral'],1) }}</span>
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if($turmaItem['trimestres']->isEmpty())
                            <div style="padding:10px 20px; font-size:.8rem; color:#94a3b8;">Sem notas lanÃ§adas nesta turma.</div>
                        @else
                        <div style="overflow-x:auto;">
                            <table class="est-table">
                                <thead>
                                    <tr>
                                        <th>Trimestre</th>
                                        <th class="tc">Total</th>
                                        <th class="tc">Masc.</th>
                                        <th class="tc">Fem.</th>
                                        <th class="tc">Positivas</th>
                                        <th class="tc">Negativas</th>
                                        <th>% AprovaÃ§Ã£o</th>
                                        <th class="tc">% Reprov.</th>
                                        <th class="tc">MÃ©dia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($turmaItem['trimestres'] as $trim)
                                    <tr>
                                        <td>
                                            <span class="est-trim-pill {{ $trimCores[$trim['trimestre']] ?? '' }}">
                                                {{ $trim['trimestre'] }}Âº Trimestre
                                            </span>
                                        </td>
                                        <td class="tc font-medium">{{ $trim['total'] }}</td>
                                        <td class="tc" style="color:#64748b">{{ $trim['masculino'] }}</td>
                                        <td class="tc" style="color:#64748b">{{ $trim['feminino'] }}</td>
                                        <td class="tc" style="color:#16a34a; font-weight:700">{{ $trim['positivas'] }}</td>
                                        <td class="tc" style="color:#dc2626; font-weight:700">{{ $trim['negativas'] }}</td>
                                        <td>
                                            @php
                                                $pct = $trim['pct_aprovacao'];
                                                $bc  = $pct >= 70 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
                                            @endphp
                                            <div class="est-bar-wrap">
                                                <div class="est-bar-track">
                                                    <div class="est-bar-fill" style="width:{{ $pct }}%; background:{{ $bc }}"></div>
                                                </div>
                                                <span class="est-bar-label" style="color:{{ $bc }}">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                        <td class="tc">
                                            <span style="font-size:.7rem; font-weight:700; color:{{ $trim['pct_reprovacao'] > 30 ? '#dc2626' : '#64748b' }}">
                                                {{ $trim['pct_reprovacao'] }}%
                                            </span>
                                        </td>
                                        <td class="tc" style="font-weight:700; color:#0f766e">
                                            {{ $trim['media'] !== null ? number_format($trim['media'],1) : 'â€”' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    @endforeach
                @endif

            {{-- === COORD_TURMA / ADMIN / COORD_CURSO: lista de disciplinas === --}}
            @else
                @php $estatisticas = collect($item['estatisticas'] ?? []); @endphp

                @if($estatisticas->isEmpty())
                    <div class="est-empty" style="padding:28px 20px;">
                        <i class="fas fa-clipboard-list" style="font-size:1.6rem;"></i>
                        <div class="est-empty-title">Sem notas lançadas</div>
                    </div>
                @else
                @foreach($estatisticas as $discItem)
                    {{-- Cabeçalho por disciplina --}}
                    <div class="est-disc-header">
                        <span class="est-disc-name">{{ $discItem['disciplina']->nome }}</span>
                        <span class="est-disc-code">{{ $discItem['disciplina']->codigo }}</span>
                        @if($discItem['resumo']['total_notas'] > 0)
                            <span style="margin-left:auto; display:flex; gap:6px;">
                                <span class="est-chip est-chip-green" style="font-size:.6rem; padding:2px 6px;">{{ $discItem['resumo']['pct_aprovacao'] }}% apr</span>
                                <span class="est-chip est-chip-red"   style="font-size:.6rem; padding:2px 6px;">{{ $discItem['resumo']['pct_reprovacao'] }}% rep</span>
                                @if($discItem['resumo']['media_geral'] !== null)
                                <span class="est-chip est-chip-blue"  style="font-size:.6rem; padding:2px 6px;">Méd. {{ number_format($discItem['resumo']['media_geral'],1) }}</span>
                                @endif
                            </span>
                        @endif
                    </div>

                    {{-- Tabela dos trimestres da disciplina --}}
                    @if($discItem['trimestres']->isEmpty())
                        <div style="padding:10px 20px; font-size:.8rem; color:#94a3b8;">Sem notas lançadas nesta disciplina.</div>
                    @else
                    <div style="overflow-x:auto;">
                        <table class="est-table">
                            <thead>
                                <tr>
                                    <th>Trimestre</th>
                                    <th class="tc">Total</th>
                                    <th class="tc">Masc.</th>
                                    <th class="tc">Fem.</th>
                                    <th class="tc">Positivas</th>
                                    <th class="tc">Negativas</th>
                                    <th>% Aprovação</th>
                                    <th class="tc">% Reprov.</th>
                                    <th class="tc">Média</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($discItem['trimestres'] as $trim)
                                <tr>
                                    <td>
                                        <span class="est-trim-pill {{ $trimCores[$trim['trimestre']] ?? '' }}">
                                            {{ $trim['trimestre'] }}º Trimestre
                                        </span>
                                    </td>
                                    <td class="tc font-medium">{{ $trim['total'] }}</td>
                                    <td class="tc" style="color:#64748b">{{ $trim['masculino'] }}</td>
                                    <td class="tc" style="color:#64748b">{{ $trim['feminino'] }}</td>
                                    <td class="tc" style="color:#16a34a; font-weight:700">{{ $trim['positivas'] }}</td>
                                    <td class="tc" style="color:#dc2626; font-weight:700">{{ $trim['negativas'] }}</td>
                                    <td>
                                        @php
                                            $pct = $trim['pct_aprovacao'];
                                            $bc  = $pct >= 70 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
                                        @endphp
                                        <div class="est-bar-wrap">
                                            <div class="est-bar-track">
                                                <div class="est-bar-fill" style="width:{{ $pct }}%; background:{{ $bc }}"></div>
                                            </div>
                                            <span class="est-bar-label" style="color:{{ $bc }}">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                    <td class="tc">
                                        <span style="font-size:.7rem; font-weight:700; color:{{ $trim['pct_reprovacao'] > 30 ? '#dc2626' : '#64748b' }}">
                                            {{ $trim['pct_reprovacao'] }}%
                                        </span>
                                    </td>
                                    <td class="tc" style="font-weight:700; color:#0f766e">
                                        {{ $trim['media'] !== null ? number_format($trim['media'],1) : '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                @endforeach
                @endif
            @endif

        </div>{{-- /est-item-body --}}
    </div>{{-- /est-item --}}
    @endforeach

    @endif{{-- /itens --}}
</div>{{-- /est-section --}}
@endforeach

@endif{{-- /secoes --}}

@endsection

@push('scripts')
<script>
function toggleEstItem(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('est-item-open');
}

// Abrir 1º item de cada secção por padrão
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.est-item:first-child').forEach(el => {
        el.classList.add('est-item-open');
    });
});
</script>
@endpush

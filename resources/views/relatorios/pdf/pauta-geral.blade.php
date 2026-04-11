<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <title>Pauta Geral - {{ $turma->nome_completo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px; /* Aumentado para A3 */
            color: #1a202c;
            padding: 15px;
            background: white;
        }

        /* ── HEADER ── */
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 4px solid #3B82F6;
        }
        .header h1 { font-size: 22px; color: #3B82F6; margin-bottom: 5px; }
        .header p  { font-size: 11px; color: #64748b; margin: 2px 0; }

        /* ── INFO TABLE (Substitui o Grid para Dompdf) ── */
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 15px;
        }
        .info-table td {
            width: 20%;
            background: #f3f4f6;
            border: none;
            border-left: 4px solid #3B82F6;
            border-radius: 4px;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }
        .info-label { font-size: 8px; color: #64748b; text-transform: uppercase; display: block; }
        .info-value { font-size: 11px; font-weight: bold; color: #1e293b; margin-top: 3px; display: block; }

        /* ── TABELA DE NOTAS ── */
        table.pauta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: fixed;
        }

        th.disc-group {
            background: #1e40af;
            color: white;
            font-size: 10px; /* Aumentado */
            padding: 6px 2px;
            text-align: center;
            border: 1px solid #1e3a8a;
        }

        th.sub-col {
            background: #3B82F6;
            color: white;
            font-size: 8px; /* Aumentado */
            padding: 5px 1px;
            text-align: center;
            border: 1px solid #2563EB;
        }

        th.fix-col {
            background: #374151;
            color: white;
            font-size: 10px;
            padding: 6px 3px;
            text-align: center;
            border: 1px solid #1f2937;
        }

        table.pauta-table td {
            padding: 4px 1px;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-size: 9px; /* Aumentado */
        }

        td.nome-aluno {
            text-align: left !important;
            padding-left: 6px !important;
            font-size: 10px !important;
            font-weight: bold;
        }

        tbody tr:nth-child(even) { background: #f9fafb; }

        td.mt-col   { background: #dbeafe; font-weight: bold; }
        td.cfd-col  { background: #d1fae5; font-weight: bold; }
        td.ca-col   { background: #fef3c7; font-weight: bold; }

        .aprovado { color: #059669; font-weight: bold; }
        .reprovado { color: #dc2626; font-weight: bold; }

        /* ── RESUMO (Substitui Grid para Dompdf) ── */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            background: #eff6ff;
            border: 2px solid #3B82F6;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .summary-table td {
            width: 25%;
            background: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            border: none;
        }
        .summary-label { font-size: 9px; color: #64748b; display: block; }
        .summary-value { font-size: 18px; font-weight: bold; margin-top: 5px; display: block; }
        .summary-value.blue  { color: #3B82F6; }
        .summary-value.green { color: #10B981; }
        .summary-value.red   { color: #EF4444; }

        /* ── ASSINATURAS (Substitui Grid para Dompdf) ── */
        .assinaturas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }
        .assinaturas-table td {
            width: 33.33%;
            text-align: center;
            border: none;
            padding: 0 30px;
        }
        .assinatura-linha { border-top: 1px solid #1e293b; padding-top: 10px; margin-bottom: 5px; }
        .assinatura-label { font-size: 10px; color: #64748b; }

        /* ── LEGENDA E FOOTER ── */
        .legenda {
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 8.5px;
            color: #475569;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
        }

        @page {
            size: A3 landscape;
            margin: 10mm;
        }
    </style>
</head>
<body>

@php
    $trimestreLabel = match($trimestre) {
        '1'     => '1º Trimestre',
        '2'     => '2º Trimestre',
        '3'     => '3º Trimestre',
        default => 'Final (CFD)',
    };

    $isFinal = $trimestre === 'final' || !in_array($trimestre, ['1','2','3']);
    $disciplinas = $turma->disciplinas->sortBy('nome');
    $alunos = $turma->alunos->sortBy('name');

    $notasIndex = [];
    foreach ($turma->notas as $nota) {
        $notasIndex[$nota->aluno_id][$nota->disciplina_id] = $nota;
    }

    $configAvaliacao = $anoLetivo?->configuracaoAvaliacao ?? $turma->anoLetivo?->configuracaoAvaliacao;
    $colsDisciplina = [];

    if (in_array($trimestre, ['1','2','3'], true) && $configAvaliacao) {
        $colsDisciplina = $configAvaliacao->provas
            ->where('periodo', (int) $trimestre)
            ->where('ativo', true)
            ->sortBy('ordem')
            ->map(fn($prova) => ['campo' => $prova->codigo, 'label' => strtoupper($prova->codigo), 'tipo' => 'normal'])
            ->values()
            ->all();

        $colsDisciplina[] = ['campo' => 'mt'.$trimestre, 'label' => 'MT'.$trimestre, 'tipo' => 'mt'];

        if ($trimestre === '2') {
            $colsDisciplina[] = ['campo' => 'mft2', 'label' => 'MFT2', 'tipo' => 'mt'];
        }

        if ($trimestre === '3') {
            $colsDisciplina[] = ['campo' => 'cf', 'label' => 'CF', 'tipo' => 'mt'];
            $colsDisciplina[] = ['campo' => 'pg', 'label' => 'PG', 'tipo' => 'normal'];
            $colsDisciplina[] = ['campo' => 'ca', 'label' => 'CA', 'tipo' => 'ca'];
        }
    }

    if (empty($colsDisciplina)) {
        $colsDisciplina = [
            ['campo' => 'mt1', 'label' => 'MT1', 'tipo' => 'mt'],
            ['campo' => 'mt2', 'label' => 'MT2', 'tipo' => 'mt'],
            ['campo' => 'mt3', 'label' => 'MT3', 'tipo' => 'mt'],
            ['campo' => 'pg',  'label' => 'PG',  'tipo' => 'normal'],
            ['campo' => 'ca',  'label' => 'CA',  'tipo' => 'ca'],
            ['campo' => 'cfd', 'label' => 'CFD', 'tipo' => 'cfd'],
        ];
    }

    $numCols   = count($colsDisciplina);
    $campoMT   = match($trimestre) { '1' => 'mt1', '2' => 'mt2', '3' => 'mt3', default => 'cfd' };

    $totalAprovados  = 0;
    $totalReprovados = 0;
    $somaMedias      = 0;
    $countMedias     = 0;

    foreach ($alunos as $aluno) {
        $notas = $notasIndex[$aluno->id] ?? [];
        foreach ($disciplinas as $disc) {
            $nota = $notas[$disc->id] ?? null;
            if ($nota && $nota->$campoMT !== null) {
                $somaMedias += $nota->$campoMT;
                $countMedias++;
                if ($nota->$campoMT >= 10) $totalAprovados++;
                else $totalReprovados++;
            }
        }
    }
    $mediaGeral = $countMedias > 0 ? $somaMedias / $countMedias : null;
    $totalGeral = $totalAprovados + $totalReprovados;
    $taxaGeral  = $totalGeral > 0 ? ($totalAprovados / $totalGeral) * 100 : 0;
@endphp

{{-- ── HEADER ── --}}
<div class="header">
    <h1>📊 PAUTA GERAL DA TURMA</h1>
    <p>{{ $turma->curso->nome }} — {{ $turma->classe }}ª Classe &nbsp;|&nbsp; {{ $turma->nome_completo }} &nbsp;|&nbsp; Ano Letivo: {{ $turma->anoLetivo->nome }}</p>
    <p><strong>Período: {{ $trimestreLabel }}</strong></p>
</div>

{{-- ── INFO TABLE ── --}}
<table class="info-table">
    <tr>
        <td>
            <span class="info-label">Curso</span>
            <span class="info-value">{{ $turma->curso->nome }}</span>
        </td>
        <td>
            <span class="info-label">Classe</span>
            <span class="info-value">{{ $turma->classe }}ª</span>
        </td>
        <td>
            <span class="info-label">Período</span>
            <span class="info-value">{{ $trimestreLabel }}</span>
        </td>
        <td>
            <span class="info-label">Disciplinas</span>
            <span class="info-value">{{ $disciplinas->count() }}</span>
        </td>
        <td>
            <span class="info-label">Total Alunos</span>
            <span class="info-value">{{ $alunos->count() }}</span>
        </td>
    </tr>
</table>

{{-- ── LEGENDA ── --}}
<div class="legenda">
    <strong>Legenda:</strong>
    @if(!$isFinal)
        Provas configuradas dinamicamente para o período selecionado &nbsp;|&nbsp; MT = Média Trimestral
    @else
        MT1/MT2/MT3 = Médias Trimestrais &nbsp;|&nbsp; PG = Prova Global &nbsp;|&nbsp; CA = Classif. Anual &nbsp;|&nbsp; CFD = Classif. Final Disciplina &nbsp;|&nbsp; ✓ Aprovado ≥ 10
    @endif
</div>

{{-- ── TABELA PRINCIPAL ── --}}
<table class="pauta-table">
    <thead>
        <tr>
            <th class="fix-col" style="width:3%" rowspan="2">Nº</th>
            <th class="fix-col" style="width:14%" rowspan="2">Nome do Aluno</th>
            <th class="fix-col" style="width:8%" rowspan="2">Processo</th>
            <th class="fix-col" style="width:3%" rowspan="2">Gen</th>
            @foreach($disciplinas as $disc)
                <th class="disc-group" colspan="{{ $numCols }}">
                    {{ $disc->codigo ?? $disc->nome }}
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach($disciplinas as $disc)
                @foreach($colsDisciplina as $col)
                    <th class="sub-col">{{ $col['label'] }}</th>
                @endforeach
            @endforeach
        </tr>
    </thead>

    <tbody>
        @php $contador = 1; @endphp
        @foreach($alunos as $aluno)
        @php $notasAluno = $notasIndex[$aluno->id] ?? []; @endphp
        <tr>
            <td>{{ $contador++ }}</td>
            <td class="nome-aluno">{{ $aluno->name }}</td>
            <td>{{ $aluno->numero_processo ?? "-" }}</td>
            <td>{{ in_array($aluno->genero, ["M", "F"]) ? $aluno->genero : "-" }}</td>
            
            @foreach($disciplinas as $disc)
                @php $nota = $notasAluno[$disc->id] ?? null; @endphp
                @foreach($colsDisciplina as $col)
                    @php
                        $val     = $nota ? $nota->{$col['campo']} : null;
                        $tdClass = match($col['tipo']) {
                            'mt'  => 'mt-col',
                            'cfd' => 'cfd-col',
                            'ca'  => 'ca-col',
                            default => '',
                        };
                        $statusClass = '';
                        if (in_array($col['tipo'], ['cfd', 'mt', 'ca']) && $val !== null) {
                            $statusClass = $val >= 10 ? 'aprovado' : 'reprovado';
                        }
                    @endphp
                    <td class="{{ $tdClass }}">
                        <span class="{{ $statusClass }}">
                            {{ $val !== null ? number_format($val, 1) : '—' }}
                        </span>
                    </td>
                @endforeach
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── RESUMO ── --}}
<table class="summary-table">
    <tr>
        <td>
            <span class="summary-label">Média Geral ({{ $trimestreLabel }})</span>
            <span class="summary-value blue">{{ $mediaGeral !== null ? number_format($mediaGeral, 2) : '—' }}</span>
        </td>
        <td>
            <span class="summary-label">Total Aprovações</span>
            <span class="summary-value green">{{ $totalAprovados }}</span>
        </td>
        <td>
            <span class="summary-label">Total Reprovações</span>
            <span class="summary-value red">{{ $totalReprovados }}</span>
        </td>
        <td>
            <span class="summary-label">Taxa Geral de Aprovação</span>
            <span class="summary-value {{ $taxaGeral >= 70 ? 'green' : 'red' }}">{{ number_format($taxaGeral, 1) }}%</span>
        </td>
    </tr>
</table>

{{-- ── ASSINATURAS ── --}}
<table class="assinaturas-table">
    <tr>
        <td>
            <div class="assinatura-linha"></div>
            <span class="assinatura-label">Coordenador da Turma</span>
        </td>
        <td>
            <div class="assinatura-linha"></div>
            <span class="assinatura-label">Coordenador Pedagógico</span>
        </td>
        <td>
            <div class="assinatura-linha"></div>
            <span class="assinatura-label">Direção</span>
        </td>
    </tr>
</table>

{{-- ── FOOTER ── --}}
<div class="footer">
    <p>Gerado em {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; Sistema de Gestão Escolar 🇦🇴</p>
</div>

</body>
</html>
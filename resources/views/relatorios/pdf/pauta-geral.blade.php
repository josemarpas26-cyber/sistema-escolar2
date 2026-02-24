<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <title>Pauta Geral - {{ $turma->nome_completo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: #1a202c;
            padding: 8px;
            background: white;
        }

        /* ── HEADER ── */
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 3px solid #3B82F6;
        }
        .header h1 { font-size: 16px; color: #3B82F6; margin-bottom: 3px; }
        .header p  { font-size: 9px; color: #64748b; margin: 1px 0; }

        /* ── INFO GRID ── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-bottom: 10px;
        }
        .info-item {
            padding: 5px 7px;
            background: #f3f4f6;
            border-radius: 4px;
            border-left: 3px solid #3B82F6;
        }
        .info-label { font-size: 6.5px; color: #64748b; text-transform: uppercase; }
        .info-value { font-size: 9px; font-weight: bold; color: #1e293b; margin-top: 2px; }

        /* ── TABELA ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            table-layout: fixed;
        }

        /* cabeçalho de grupo de disciplina */
        th.disc-group {
            background: #1e40af;
            color: white;
            font-size: 7px;
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #1e3a8a;
            letter-spacing: 0.3px;
        }

        /* cabeçalho de sub-coluna */
        th.sub-col {
            background: #3B82F6;
            color: white;
            font-size: 6px;
            padding: 3px 1px;
            text-align: center;
            border: 1px solid #2563EB;
        }

        /* cabeçalho fixo (Nº / Nome) */
        th.fix-col {
            background: #374151;
            color: white;
            font-size: 7px;
            padding: 4px 3px;
            text-align: center;
            border: 1px solid #1f2937;
        }

        td {
            padding: 3px 1px;
            border: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6.5px;
            overflow: hidden;
        }

        td.nome-aluno {
            text-align: left;
            padding-left: 4px;
            font-size: 7px;
            font-weight: 600;
        }

        tbody tr:nth-child(even) { background: #f9fafb; }

        /* média / nota final da disciplina — destaque */
        td.mt-col   { background: #dbeafe; font-weight: bold; font-size: 7px; }
        td.cfd-col  { background: #d1fae5; font-weight: bold; font-size: 7px; }
        td.ca-col   { background: #fef3c7; font-weight: bold; font-size: 7px; }

        .aprovado { color: #059669; font-weight: bold; }
        .reprovado { color: #dc2626; font-weight: bold; }

        /* ── RESUMO ── */
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            background: #eff6ff;
            border: 2px solid #3B82F6;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 12px;
        }
        .summary-item { text-align: center; background: white; padding: 6px; border-radius: 4px; }
        .summary-label { font-size: 7px; color: #64748b; }
        .summary-value { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .summary-value.blue  { color: #3B82F6; }
        .summary-value.green { color: #10B981; }
        .summary-value.red   { color: #EF4444; }

        /* ── ASSINATURAS ── */
        .assinaturas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 25px;
        }
        .assinatura-item { text-align: center; }
        .assinatura-linha { border-top: 1px solid #1e293b; padding-top: 30px; margin-bottom: 4px; }
        .assinatura-label { font-size: 8px; color: #64748b; }

        /* ── FOOTER ── */
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 7px;
            color: #64748b;
        }

        .legenda {
            background: #f3f4f6;
            padding: 5px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 6.5px;
            color: #475569;
        }

        @media print {
            body { padding: 0; }
            @page { size: A3 landscape; margin: 8mm; }
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

    // Recolher disciplinas com notas nesta turma
    $disciplinas = $turma->disciplinas->sortBy('nome');

    // Recolher todos os alunos com notas nesta turma
    $alunos = $turma->alunos->sortBy('name');

    // Indexar notas: [aluno_id][disciplina_id] => Nota
    $notasIndex = [];
    foreach ($turma->notas as $nota) {
        $notasIndex[$nota->aluno_id][$nota->disciplina_id] = $nota;
    }

    // Colunas por trimestre
    $colsDisciplina = match($trimestre) {
        '1'     => [
            ['campo' => 'mac1', 'label' => 'MAC', 'tipo' => 'normal'],
            ['campo' => 'pp1',  'label' => 'PP',  'tipo' => 'normal'],
            ['campo' => 'pt1',  'label' => 'PT',  'tipo' => 'normal'],
            ['campo' => 'mt1',  'label' => 'MT1', 'tipo' => 'mt'],
        ],
        '2'     => [
            ['campo' => 'mac2', 'label' => 'MAC', 'tipo' => 'normal'],
            ['campo' => 'pp2',  'label' => 'PP',  'tipo' => 'normal'],
            ['campo' => 'pt2',  'label' => 'PT',  'tipo' => 'normal'],
            ['campo' => 'mt2',  'label' => 'MT2', 'tipo' => 'mt'],
            ['campo' => 'mft2', 'label' => 'MFT', 'tipo' => 'mt'],
        ],
        '3'     => [
            ['campo' => 'mac3', 'label' => 'MAC', 'tipo' => 'normal'],
            ['campo' => 'pp3',  'label' => 'PP',  'tipo' => 'normal'],
            ['campo' => 'mt3',  'label' => 'MT3', 'tipo' => 'mt'],
            ['campo' => 'cf',   'label' => 'CF',  'tipo' => 'mt'],
            ['campo' => 'pg',   'label' => 'PG',  'tipo' => 'normal'],
            ['campo' => 'ca',   'label' => 'CA',  'tipo' => 'ca'],
        ],
        default => [
            ['campo' => 'mt1', 'label' => 'MT1', 'tipo' => 'mt'],
            ['campo' => 'mt2', 'label' => 'MT2', 'tipo' => 'mt'],
            ['campo' => 'mt3', 'label' => 'MT3', 'tipo' => 'mt'],
            ['campo' => 'pg',  'label' => 'PG',  'tipo' => 'normal'],
            ['campo' => 'ca',  'label' => 'CA',  'tipo' => 'ca'],
            ['campo' => 'cfd', 'label' => 'CFD', 'tipo' => 'cfd'],
        ],
    };

    $numCols   = count($colsDisciplina);
    $campoMT   = match($trimestre) { '1' => 'mt1', '2' => 'mt2', '3' => 'mt3', default => 'cfd' };

    // Estatísticas gerais
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

{{-- ── INFO ── --}}
<div class="info-grid">
    <div class="info-item">
        <div class="info-label">Curso</div>
        <div class="info-value">{{ $turma->curso->nome }}</div>
    </div>
    <div class="info-item">
        <div class="info-label">Classe</div>
        <div class="info-value">{{ $turma->classe }}ª</div>
    </div>
    <div class="info-item">
        <div class="info-label">Período</div>
        <div class="info-value">{{ $trimestreLabel }}</div>
    </div>
    <div class="info-item">
        <div class="info-label">Nº Disciplinas</div>
        <div class="info-value">{{ $disciplinas->count() }}</div>
    </div>
    <div class="info-item">
        <div class="info-label">Total de Alunos</div>
        <div class="info-value">{{ $alunos->count() }}</div>
    </div>
</div>

{{-- ── LEGENDA ── --}}
<div class="legenda">
    <strong>Legenda:</strong>
    @if(!$isFinal)
        MAC = Média de Avaliações Contínuas &nbsp;|&nbsp;
        PP = Prova do Professor &nbsp;|&nbsp;
        @if($trimestre == '1') PT = Prova Trimestral &nbsp;|&nbsp; MT1 = Média 1º Trim @endif
        @if($trimestre == '2') PT = Prova Trimestral &nbsp;|&nbsp; MT2 = Média 2º Trim &nbsp;|&nbsp; MFT = Média Final até T2 @endif
        @if($trimestre == '3') MT3 = Média 3º Trim &nbsp;|&nbsp; CF = Classificação Final &nbsp;|&nbsp; PG = Prova Global &nbsp;|&nbsp; CA = Classificação Anual @endif
    @else
        MT1/MT2/MT3 = Médias Trimestrais &nbsp;|&nbsp; PG = Prova Global &nbsp;|&nbsp; CA = Classif. Anual &nbsp;|&nbsp; CFD = Classif. Final Disciplina &nbsp;|&nbsp;
        ✓ Aprovado ≥ 10 valores
    @endif
    &nbsp;|&nbsp; Cabeçalhos de coluna = código da disciplina
</div>

{{-- ── TABELA PRINCIPAL ── --}}
<table>
    <thead>
        {{-- Linha 1: Nº | Nome | [Código disciplina] agrupado --}}
        <tr>
            <th class="fix-col" style="width:2.5%" rowspan="2">Nº</th>
            <th class="fix-col" style="width:12%" rowspan="2">Nome do Aluno</th>
            @foreach($disciplinas as $disc)
                <th class="disc-group" colspan="{{ $numCols }}">
                    {{ $disc->codigo ?? $disc->nome }}
                </th>
            @endforeach
        </tr>

        {{-- Linha 2: sub-colunas de cada disciplina --}}
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
        @php
            $notasAluno = $notasIndex[$aluno->id] ?? [];
            // calcular média do aluno neste período (para linha de status)
            $somaAluno  = 0;
            $countAluno = 0;
            foreach ($disciplinas as $disc) {
                $n = $notasAluno[$disc->id] ?? null;
                if ($n && $n->$campoMT !== null) {
                    $somaAluno += $n->$campoMT;
                    $countAluno++;
                }
            }
            $mediaAluno = $countAluno > 0 ? round($somaAluno / $countAluno, 1) : null;
        @endphp
        <tr>
            <td>{{ $contador++ }}</td>
            <td class="nome-aluno">{{ $aluno->name }}</td>

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
                        // colorir CFD/MT aprovado ou reprovado
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
<div class="summary">
    <div class="summary-item">
        <div class="summary-label">Média Geral ({{ $trimestreLabel }})</div>
        <div class="summary-value blue">{{ $mediaGeral !== null ? number_format($mediaGeral, 2) : '—' }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Total Aprovações</div>
        <div class="summary-value green">{{ $totalAprovados }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Total Reprovações</div>
        <div class="summary-value red">{{ $totalReprovados }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Taxa Geral de Aprovação</div>
        <div class="summary-value {{ $taxaGeral >= 70 ? 'green' : 'red' }}">{{ number_format($taxaGeral, 1) }}%</div>
    </div>
</div>

{{-- ── ASSINATURAS ── --}}
<div class="assinaturas">
    <div class="assinatura-item">
        <div class="assinatura-linha"></div>
        <div class="assinatura-label">Coordenador da Turma</div>
    </div>
    <div class="assinatura-item">
        <div class="assinatura-linha"></div>
        <div class="assinatura-label">Coordenador Pedagógico</div>
    </div>
    <div class="assinatura-item">
        <div class="assinatura-linha"></div>
        <div class="assinatura-label">Direção</div>
    </div>
</div>

{{-- ── FOOTER ── --}}
<div class="footer">
    <p>Gerado em {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; Sistema de Gestão Escolar 🇦🇴</p>
</div>

</body>
</html>
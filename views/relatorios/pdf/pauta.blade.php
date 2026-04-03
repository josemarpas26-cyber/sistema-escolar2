<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pauta de Notas</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 9px;
        color: #333;
        padding: 15px;
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 3px solid #3B82F6;
        padding-bottom: 10px;
    }

    .header h1 {
        font-size: 18px;
        color: #3B82F6;
        margin-bottom: 5px;
    }

    .info-section {
        background: #F3F4F6;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-around;
    }

    .info-item {
        text-align: center;
    }

    .info-label {
        font-weight: bold;
        color: #374151;
        font-size: 10px;
    }

    .info-value {
        color: #1F2937;
        font-size: 11px;
        margin-top: 3px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        font-size: 8px;
    }

    th {
        background: #3B82F6;
        color: white;
        padding: 6px 2px;
        text-align: center;
        font-weight: bold;
        border: 1px solid #2563EB;
        font-size: 8px;
    }

    td {
        padding: 5px 2px;
        border: 1px solid #E5E7EB;
        text-align: center;
    }

    tr:nth-child(even) {
        background: #F9FAFB;
    }

    .nome-aluno {
        text-align: left;
        padding-left: 5px;
        font-size: 9px;
    }

    .aprovado {
        color: #10B981;
        font-weight: bold;
    }

    .reprovado {
        color: #EF4444;
        font-weight: bold;
    }

    .summary {
        background: #EFF6FF;
        border: 2px solid #3B82F6;
        padding: 10px;
        border-radius: 5px;
        display: flex;
        justify-content: space-around;
        margin-top: 15px;
    }

    .summary-item {
        text-align: center;
    }

    .summary-label {
        font-weight: bold;
        color: #1E40AF;
        font-size: 10px;
    }

    .summary-value {
        font-weight: bold;
        color: #1F2937;
        font-size: 14px;
        margin-top: 5px;
    }

    .footer {
        margin-top: 20px;
        text-align: center;
        font-size: 8px;
        color: #6B7280;
        border-top: 1px solid #E5E7EB;
        padding-top: 10px;
    }

    .assinatura {
        margin-top: 40px;
        display: flex;
        justify-content: space-around;
        font-size: 10px;
    }

    .assinatura-item {
        text-align: center;
        width: 30%;
    }

    .linha {
        border-top: 1px solid #000;
        margin-top: 30px;
        margin-bottom: 5px;
    }

    .trimestre-badge {
        display: inline-block;
        background: #DBEAFE;
        color: #1E40AF;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
        margin-left: 6px;
    }
</style>
</head>
<body>

    @php
        $trimestreLabel = match($trimestre) {
            '1' => '1º Trimestre',
            '2' => '2º Trimestre',
            '3' => '3º Trimestre',
            default => 'Final (CFD)',
        };
    @endphp

    <!-- Header -->
    <div class="header">
        <h1>📊 PAUTA DE NOTAS</h1>
        <p style="font-size: 11px;">
            {{ $turma->nome_completo }} - {{ $disciplina->nome }}
            <span class="trimestre-badge">{{ $trimestreLabel }}</span>
        </p>
    </div>

    <!-- Info -->
    <div class="info-section">
        <div class="info-item">
            <div class="info-label">Curso</div>
            <div class="info-value">{{ $turma->curso->nome }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Classe</div>
            <div class="info-value">{{ $turma->classe }}ª</div>
        </div>
        <div class="info-item">
            <div class="info-label">Disciplina</div>
            <div class="info-value">{{ $disciplina->nome }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Período</div>
            <div class="info-value">{{ $trimestreLabel }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Ano Letivo</div>
            <div class="info-value">{{ $anoLetivo->nome }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Total de Alunos</div>
            <div class="info-value">{{ $totalAlunos }}</div>
        </div>
    </div>

    <!-- Notas -->
    <table>
        <thead>
            <tr>
                <th width="3%">Nº</th>
                <th width="20%">Nome do Aluno</th>
                <th width="10%">Nº Processo</th>
                <th width="4%">Gênero</th>

                @if($trimestre === '1')
                    <th>MAC1</th>
                    <th>PP1</th>
                    <th>PT1</th>
                    <th>MT1</th>

                @elseif($trimestre === '2')
                    <th>MAC2</th>
                    <th>PP2</th>
                    <th>PT2</th>
                    <th>MT2</th>
                    <th>MFT2</th>

                @elseif($trimestre === '3')
                    <th>MAC3</th>
                    <th>PP3</th>
                    <th>MT3</th>
                    <th>CF</th>
                    <th>PG</th>
                    <th>CA</th>

                @else {{-- Final --}}
                    <th>MT1</th>
                    <th>MT2</th>
                    <th>MFT2</th>
                    <th>MT3</th>
                    <th>CF</th>
                    <th>PG</th>
                    <th>CA</th>
                    <th>CFD</th>
                @endif

                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $contador = 1; @endphp
            @foreach($notas as $nota)
            @php
                $valorPeriodo = match($trimestre) {
                    '1' => $nota->mt1,
                    '2' => $nota->mt2,
                    '3' => $nota->mt3,
                    default => $nota->cfd,
                };
                $temNota = $valorPeriodo !== null;
                $aprovado = $temNota && $valorPeriodo >= 10;
            @endphp
            <tr>
                <td>{{ $contador++ }}</td>
                <td class="nome-aluno">{{ $nota->aluno->name }}</td>
                <td>{{ $nota->aluno->numero_processo ?? "-" }}</td>
                <td>{{ in_array($nota->aluno->genero, ["M", "F"]) ? $nota->aluno->genero : "-" }}</td>

                @if($trimestre === '1')
                    <td>{{ $nota->mac1 !== null ? number_format($nota->mac1, 1) : '-' }}</td>
                    <td>{{ $nota->pp1  !== null ? number_format($nota->pp1,  1) : '-' }}</td>
                    <td>{{ $nota->pt1  !== null ? number_format($nota->pt1,  1) : '-' }}</td>
                    <td><strong>{{ $nota->mt1 !== null ? number_format($nota->mt1, 1) : '-' }}</strong></td>

                @elseif($trimestre === '2')
                    <td>{{ $nota->mac2 !== null ? number_format($nota->mac2, 1) : '-' }}</td>
                    <td>{{ $nota->pp2  !== null ? number_format($nota->pp2,  1) : '-' }}</td>
                    <td>{{ $nota->pt2  !== null ? number_format($nota->pt2,  1) : '-' }}</td>
                    <td><strong>{{ $nota->mt2  !== null ? number_format($nota->mt2,  1) : '-' }}</strong></td>
                    <td><strong>{{ $nota->mft2 !== null ? number_format($nota->mft2, 1) : '-' }}</strong></td>

                @elseif($trimestre === '3')
                    <td>{{ $nota->mac3 !== null ? number_format($nota->mac3, 1) : '-' }}</td>
                    <td>{{ $nota->pp3  !== null ? number_format($nota->pp3,  1) : '-' }}</td>
                    <td><strong>{{ $nota->mt3 !== null ? number_format($nota->mt3, 1) : '-' }}</strong></td>
                    <td><strong>{{ $nota->cf  !== null ? number_format($nota->cf,  1) : '-' }}</strong></td>
                    <td>{{ $nota->pg  !== null ? number_format($nota->pg,  1) : '-' }}</td>
                    <td><strong>{{ $nota->ca  !== null ? number_format($nota->ca,  1) : '-' }}</strong></td>

                @else {{-- Final --}}
                    <td>{{ $nota->mt1  !== null ? number_format($nota->mt1,  1) : '-' }}</td>
                    <td>{{ $nota->mt2  !== null ? number_format($nota->mt2,  1) : '-' }}</td>
                    <td>{{ $nota->mft2 !== null ? number_format($nota->mft2, 1) : '-' }}</td>
                    <td>{{ $nota->mt3  !== null ? number_format($nota->mt3,  1) : '-' }}</td>
                    <td>{{ $nota->cf   !== null ? number_format($nota->cf,   1) : '-' }}</td>
                    <td>{{ $nota->pg   !== null ? number_format($nota->pg,   1) : '-' }}</td>
                    <td>{{ $nota->ca   !== null ? number_format($nota->ca,   1) : '-' }}</td>
                    <td><strong>{{ $nota->cfd !== null ? number_format($nota->cfd, 1) : '-' }}</strong></td>
                @endif

                <td class="{{ $temNota ? ($aprovado ? 'aprovado' : 'reprovado') : '' }}">
                    {{ !$temNota ? '-' : ($aprovado ? 'A' : 'R') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Estatísticas -->
    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Período</div>
            <div class="summary-value" style="font-size: 11px;">{{ $trimestreLabel }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Média Geral</div>
            <div class="summary-value">{{ number_format($mediaGeral, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Aprovados</div>
            <div class="summary-value" style="color: #10B981;">{{ $aprovacoes }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Reprovados</div>
            <div class="summary-value" style="color: #EF4444;">{{ $reprovacoes }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Taxa de Aprovação</div>
            <div class="summary-value">{{ $totalAlunos > 0 ? number_format(($aprovacoes / $totalAlunos) * 100, 1) : 0 }}%</div>
        </div>
    </div>

    <!-- Assinaturas -->
    <div class="assinatura">
        <div class="assinatura-item">
            <div class="linha"></div>
            <div>Professor(a)</div>
        </div>
        <div class="assinatura-item">
            <div class="linha"></div>
            <div>Coordenador(a)</div>
        </div>
        <div class="assinatura-item">
            <div class="linha"></div>
            <div>Diretor(a)</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <p>Sistema de Gestão Escolar - NotasEscola 🇦🇴</p>
    </div>

</body>
</html>
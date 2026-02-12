<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pauta Geral - {{ $turma->nome_completo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #1a202c;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px;
        }

        .container {
            background: white;
            padding: 15px;
            border-radius: 8px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3B82F6;
        }

        .header h1 {
            font-size: 20px;
            color: #3B82F6;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0;
        }

        .info-section {
            background: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .info-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #3B82F6;
        }

        .info-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
        }

        thead {
            background: #3B82F6;
            color: white;
        }

        th {
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2563eb;
            font-size: 7px;
        }

        td {
            padding: 5px 3px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:hover {
            background-color: #eff6ff;
        }

        .disciplina-col {
            text-align: left !important;
            font-weight: 600;
            color: #1e293b;
            padding-left: 8px !important;
        }

        .media-col {
            background-color: #dbeafe;
            font-weight: bold;
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
            background: #eff6ff;
            padding: 12px;
            border-radius: 6px;
            border: 2px solid #3B82F6;
            margin-bottom: 15px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            text-align: center;
        }

        .summary-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
        }

        .summary-label {
            font-size: 8px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }

        .summary-value.green {
            color: #10B981;
        }

        .summary-value.red {
            color: #EF4444;
        }

        .summary-value.blue {
            color: #3B82F6;
        }

        .assinaturas {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .assinatura-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .assinatura-item {
            text-align: center;
        }

        .assinatura-linha {
            border-top: 1px solid #1e293b;
            margin-bottom: 5px;
            padding-top: 40px;
        }

        .assinatura-label {
            font-size: 9px;
            color: #64748b;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            font-size: 8px;
            color: #64748b;
        }

        .legenda {
            background: #f3f4f6;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 7px;
            color: #475569;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <h1>📊 PAUTA GERAL DA TURMA</h1>
            <p>{{ $turma->curso->nome }} - {{ $turma->classe }}ª Classe</p>
            <p>{{ $turma->nome_completo }} | Ano Letivo: {{ $turma->anoLetivo->nome }}</p>
        </div>

        <!-- Informações -->
        <div class="info-section">
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
                    <div class="info-label">Total de Disciplinas</div>
                    <div class="info-value">{{ $turma->disciplinas->count() }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total de Alunos</div>
                    <div class="info-value">{{ $turma->total_alunos }}</div>
                </div>
            </div>
        </div>

        <!-- Legenda -->
        <div class="legenda">
            <strong>Legenda:</strong> 
            Média Turma = Média de todos os alunos na disciplina | 
            Aprovados = CFD ≥ 10 | 
            Reprovados = CFD &lt; 10 | 
            Taxa = % de aprovação
        </div>

        <!-- Tabela Principal -->
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Disciplina</th>
                    <th style="width: 12%;">Média Turma</th>
                    <th style="width: 12%;">Maior Nota</th>
                    <th style="width: 12%;">Menor Nota</th>
                    <th style="width: 10%;">Aprovados</th>
                    <th style="width: 10%;">Reprovados</th>
                    <th style="width: 14%;">Taxa Aprovação</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalAprovadosGeral = 0;
                    $totalReprovadosGeral = 0;
                    $somaMediasGerais = 0;
                    $disciplinasComNotas = 0;
                @endphp

                @foreach($turma->disciplinas as $disciplina)
                    @php
                        // Buscar todas as notas desta disciplina nesta turma
                        $notasDisciplina = $turma->notas
                            ->where('disciplina_id', $disciplina->id)
                            ->whereNotNull('cfd');
                        
                        $total = $notasDisciplina->count();
                        
                        if ($total > 0) {
                            $media = $notasDisciplina->avg('cfd');
                            $maior = $notasDisciplina->max('cfd');
                            $menor = $notasDisciplina->min('cfd');
                            $aprovados = $notasDisciplina->filter(function($nota) {
                                return $nota->isAprovado();
                            })->count();
                            $reprovados = $total - $aprovados;
                            $taxa = ($aprovados / $total) * 100;
                            
                            $totalAprovadosGeral += $aprovados;
                            $totalReprovadosGeral += $reprovados;
                            $somaMediasGerais += $media;
                            $disciplinasComNotas++;
                        } else {
                            $media = null;
                            $maior = null;
                            $menor = null;
                            $aprovados = 0;
                            $reprovados = 0;
                            $taxa = 0;
                        }
                    @endphp

                    <tr>
                        <td class="disciplina-col">{{ $disciplina->nome }}</td>
                        <td class="media-col">
                            {{ $media ? number_format($media, 2) : '-' }}
                        </td>
                        <td>{{ $maior ? number_format($maior, 2) : '-' }}</td>
                        <td>{{ $menor ? number_format($menor, 2) : '-' }}</td>
                        <td class="aprovado">{{ $aprovados }}</td>
                        <td class="reprovado">{{ $reprovados }}</td>
                        <td>
                            <span class="{{ $taxa >= 70 ? 'aprovado' : 'reprovado' }}">
                                {{ number_format($taxa, 1) }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Estatísticas Gerais -->
        <div class="summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Média Geral da Turma</div>
                    <div class="summary-value blue">
                        {{ $disciplinasComNotas > 0 ? number_format($somaMediasGerais / $disciplinasComNotas, 2) : '-' }}
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total de Aprovações</div>
                    <div class="summary-value green">{{ $totalAprovadosGeral }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total de Reprovações</div>
                    <div class="summary-value red">{{ $totalReprovadosGeral }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Taxa Geral</div>
                    <div class="summary-value {{ ($totalAprovadosGeral + $totalReprovadosGeral) > 0 && (($totalAprovadosGeral / ($totalAprovadosGeral + $totalReprovadosGeral)) * 100) >= 70 ? 'green' : 'red' }}">
                        {{ ($totalAprovadosGeral + $totalReprovadosGeral) > 0 ? number_format(($totalAprovadosGeral / ($totalAprovadosGeral + $totalReprovadosGeral)) * 100, 1) : '0' }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div style="background: #fef3c7; padding: 10px; border-radius: 4px; border-left: 4px solid #f59e0b; margin-bottom: 15px;">
            <p style="font-size: 8px; color: #92400e; margin-bottom: 5px;">
                <strong>📌 Observações:</strong>
            </p>
            <ul style="font-size: 8px; color: #92400e; margin-left: 15px;">
                <li>Média Turma: Média aritmética das CFDs de todos os alunos na disciplina</li>
                <li>Taxa de Aprovação: Percentual de alunos com CFD ≥ 10 valores</li>
                <li>Este relatório apresenta o desempenho geral da turma por disciplina</li>
            </ul>
        </div>

        <!-- Assinaturas -->
        <div class="assinaturas">
            <div class="assinatura-grid">
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
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Gerado em {{ now()->format('d/m/Y H:i') }} | Sistema de Gestão Escolar 🇦🇴</p>
            <p>Este documento apresenta estatísticas gerais de desempenho da turma</p>
        </div>

    </div>
</body>
</html>
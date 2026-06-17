<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletim Escolar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            color: #3B82F6;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            color: #666;
        }

        .info-section {
            background: #F3F4F6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #374151;
        }

        .info-value {
            color: #1F2937;
        }

        .foto-perfil-wrap {
            width: 80px;
            height: 80px;
            border: 3px solid #3B82F6;
            border-radius: 50%;
            overflow: hidden;
            float: right;
            margin-left: 20px;
            background: #FFFFFF;
        }

        .foto-perfil {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #3B82F6;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2563EB;
        }

        td {
            padding: 10px 8px;
            border: 1px solid #E5E7EB;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #F9FAFB;
        }

        .disciplina {
            text-align: left;
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
            background: #EFF6FF;
            border: 2px solid #3B82F6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-label {
            font-weight: bold;
            color: #1E40AF;
        }

        .summary-value {
            font-weight: bold;
            color: #1F2937;
            font-size: 16px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #6B7280;
            border-top: 1px solid #E5E7EB;
            padding-top: 15px;
        }

        .legenda {
            font-size: 10px;
            color: #6B7280;
            margin-top: 20px;
            padding: 10px;
            background: #F9FAFB;
            border-radius: 4px;
        }

        .legenda strong {
            color: #374151;
        }

        .trimestre-badge {
            display: inline-block;
            background: #DBEAFE;
            color: #1E40AF;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }

        .logo { text-align: center; margin-bottom: 2px; }
        .logo img { height: 50px; }
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

    $fotoBase64 = $aluno->foto_perfil_pdf_src;
    $isDecimaTerceiraFinal = (int) ($turma->classe ?? 0) === 13 && $trimestre === 'final';
    $usaPgTerceiro = (int) ($turma->classe ?? 0) === 12;
    $campoAvaliacaoFinalTerceiro = $usaPgTerceiro ? 'pg' : 'pt3';
    $labelAvaliacaoFinalTerceiro = $usaPgTerceiro ? 'PG' : 'PT3';
    $resumo13 = $classificacaoEnsinoMedioResumo ?? null;
@endphp

    <div class="header">
        @php
            $path = public_path('images/logo1.png');
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        @endphp
        <div class="logo">
            <img src="{{ $base64 }}" alt="Logo">
        </div>
        <h1> BOLETIM ESCOLAR</h1>
        <p>
            {{ $turma->curso->nome }} - {{ $turma->classe }}ª Classe
            <span class="trimestre-badge">{{ $trimestreLabel }}</span>
        </p>
    </div>

    <div class="info-section">
        <div class="foto-perfil-wrap">
            <img src="{{ $fotoBase64 }}" alt="Foto" class="foto-perfil">
        </div>

        <div class="info-row">
            <span class="info-label">Aluno:</span>
            <span class="info-value">{{ $aluno->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nº Processo:</span>
            <span class="info-value">{{ $aluno->numero_processo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Turma:</span>
            <span class="info-value">{{ $turma->nome_completo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Ano Letivo:</span>
            <span class="info-value">{{ $anoLetivo->nome }}</span>
        </div>
        @if($aluno->nome_encarregado)
        <div class="info-row">
            <span class="info-label">Encarregado:</span>
            <span class="info-value">{{ $aluno->nome_encarregado }} - {{ $aluno->contacto_encarregado }}</span>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="35%">Disciplina</th>

                @if($isDecimaTerceiraFinal)
                    <th>CFD</th>
                    <th>PC</th>
                    <th>E. C. S</th>
                    <th>PAP</th>
                    <th>MF</th>
                    <th>Resultado</th>
                    <th>Observações</th>
                @elseif($trimestre === '1')
                    <th>MAC1</th>
                    <th>PP1</th>
                    <th>PT1</th>
                    <th>MT1</th>
                @elseif($trimestre === '2')
                    <th>MAC2</th>
                    <th>PP2</th>
                    <th>PT2</th>
                    <th>MT2</th>
                @elseif($trimestre === '3')
                    <th>MAC3</th>
                    <th>PP3</th>
                    <th>MT3</th>
                    <th>CF</th>
                    <th>{{ $labelAvaliacaoFinalTerceiro }}</th>
                    <th>CA</th>
                @else
                    <th>MT1</th>
                    <th>MT2</th>
                    <th>MT3</th>
                    <th>{{ $labelAvaliacaoFinalTerceiro }}</th>
                    <th>CFD</th>
                @endif

                @unless($isDecimaTerceiraFinal)
                    <th>Status</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $nota)
                @php
                    $valorPeriodo = match($trimestre) {
                        '1' => $nota->mt1,
                        '2' => $nota->mt2,
                        '3' => $nota->mt3,
                        default => $nota->cfd_efetiva,
                    };
                    $temNota = $valorPeriodo !== null;
                    $aprovadoPeriodo = $temNota && $valorPeriodo >= 10;
                @endphp
                <tr>
                    <td class="disciplina">{{ $nota->disciplina->nome }}</td>

                    @if($isDecimaTerceiraFinal)
                        <td><strong>{{ $nota->cfd_efetiva !== null ? number_format($nota->cfd_efetiva, 2) : '-' }}</strong></td>
                        <td>{{ is_numeric(data_get($resumo13, 'pc')) ? number_format((float) data_get($resumo13, 'pc'), 2) : '-' }}</td>
                        <td>{{ is_numeric(data_get($resumo13, 'classificacao.ecs')) ? number_format((float) data_get($resumo13, 'classificacao.ecs'), 2) : '-' }}</td>
                        <td>{{ is_numeric(data_get($resumo13, 'classificacao.pap')) ? number_format((float) data_get($resumo13, 'classificacao.pap'), 2) : '-' }}</td>
                        <td><strong>{{ is_numeric(data_get($resumo13, 'media_final')) ? number_format((float) data_get($resumo13, 'media_final'), 2) : '-' }}</strong></td>
                        <td class="{{ strtoupper((string) data_get($resumo13, 'resultado')) === 'APROVADO' ? 'aprovado' : (strtoupper((string) data_get($resumo13, 'resultado')) === 'REPROVADO' ? 'reprovado' : '') }}">
                            {{ data_get($resumo13, 'resultado', 'Pendente') }}
                        </td>
                        <td style="text-align:left;">{{ data_get($resumo13, 'classificacao.observacoes') ?: '-' }}</td>
                    @elseif($trimestre === '1')
                        <td>{{ $nota->mac1 !== null ? number_format($nota->mac1, 2) : '-' }}</td>
                        <td>{{ $nota->pp1  !== null ? number_format($nota->pp1,  2) : '-' }}</td>
                        <td>{{ $nota->pt1  !== null ? number_format($nota->pt1,  2) : '-' }}</td>
                        <td><strong>{{ $nota->mt1 !== null ? number_format($nota->mt1, 2) : '-' }}</strong></td>
                    @elseif($trimestre === '2')
                        <td>{{ $nota->mac2 !== null ? number_format($nota->mac2, 2) : '-' }}</td>
                        <td>{{ $nota->pp2  !== null ? number_format($nota->pp2,  2) : '-' }}</td>
                        <td>{{ $nota->pt2  !== null ? number_format($nota->pt2,  2) : '-' }}</td>
                        <td><strong>{{ $nota->mt2 !== null ? number_format($nota->mt2, 2) : '-' }}</strong></td>
                    @elseif($trimestre === '3')
                        <td>{{ $nota->mac3 !== null ? number_format($nota->mac3, 2) : '-' }}</td>
                        <td>{{ $nota->pp3  !== null ? number_format($nota->pp3,  2) : '-' }}</td>
                        <td>{{ $nota->mt3  !== null ? number_format($nota->mt3,  2) : '-' }}</td>
                        <td>{{ $nota->cf   !== null ? number_format($nota->cf,   2) : '-' }}</td>
                        <td>{{ $nota->{$campoAvaliacaoFinalTerceiro} !== null ? number_format($nota->{$campoAvaliacaoFinalTerceiro}, 2) : '-' }}</td>
                        <td><strong>{{ $nota->ca !== null ? number_format($nota->ca, 2) : '-' }}</strong></td>
                    @else
                        <td>{{ $nota->mt1  !== null ? number_format($nota->mt1,  2) : '-' }}</td>
                        <td>{{ $nota->mt2  !== null ? number_format($nota->mt2,  2) : '-' }}</td>
                        <td>{{ $nota->mt3  !== null ? number_format($nota->mt3,  2) : '-' }}</td>
                        <td>{{ $nota->{$campoAvaliacaoFinalTerceiro} !== null ? number_format($nota->{$campoAvaliacaoFinalTerceiro}, 2) : '-' }}</td>
                        <td>
                            <strong>{{ $nota->cfd_efetiva !== null ? number_format($nota->cfd_efetiva, 2) : '-' }}</strong>
                            @if($nota->recursoMelhoraClassificacaoFinal())
                                <div style="font-size:10px;color:#b45309;">Recurso</div>
                            @endif
                        </td>
                    @endif

                    @unless($isDecimaTerceiraFinal)
                        <td class="{{ $nota->recursoPendente() ? '' : ($temNota ? ($aprovadoPeriodo ? 'aprovado' : 'reprovado') : '') }}">
                            @if(!$temNota)
                                Pendente
                            @elseif($nota->recursoPendente())
                                Em recurso
                            @elseif($aprovadoPeriodo)
                                Aprovado
                            @else
                                Reprovado
                            @endif
                        </td>
                    @endunless
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Período:</span>
            <span class="summary-value">{{ $trimestreLabel }}</span>
        </div>
        @if($isDecimaTerceiraFinal)
            <div class="summary-row">
                <span class="summary-label">CFD:</span>
                <span class="summary-value">
                    {{ optional($notas->first())->cfd_efetiva !== null ? number_format((float) optional($notas->first())->cfd_efetiva, 2) : '-' }}
                </span>
            </div>
            <div class="summary-row">
                <span class="summary-label">PC:</span>
                <span class="summary-value">{{ is_numeric(data_get($resumo13, 'pc')) ? number_format((float) data_get($resumo13, 'pc'), 2) : '-' }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">E. C. S:</span>
                <span class="summary-value">{{ is_numeric(data_get($resumo13, 'classificacao.ecs')) ? number_format((float) data_get($resumo13, 'classificacao.ecs'), 2) : '-' }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">PAP:</span>
                <span class="summary-value">{{ is_numeric(data_get($resumo13, 'classificacao.pap')) ? number_format((float) data_get($resumo13, 'classificacao.pap'), 2) : '-' }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Média Final (MF):</span>
                <span class="summary-value">{{ is_numeric(data_get($resumo13, 'media_final')) ? number_format((float) data_get($resumo13, 'media_final'), 2) : '-' }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Resultado:</span>
                <span class="summary-value">{{ data_get($resumo13, 'resultado', 'Pendente') }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Observações:</span>
                <span class="summary-value" style="font-size:13px;">{{ data_get($resumo13, 'classificacao.observacoes') ?: '-' }}</span>
            </div>
        @else
            <div class="summary-row">
                <span class="summary-label">Média Geral:</span>
                <span class="summary-value">{{ number_format($mediaGeral, 2) }} valores</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Disciplinas Aprovadas:</span>
                <span class="summary-value">{{ $aprovacoes }} de {{ $aprovacoes + $reprovacoes }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Taxa de Aprovação:</span>
                <span class="summary-value">
                    {{ $aprovacoes + $reprovacoes > 0 ? number_format(($aprovacoes / ($aprovacoes + $reprovacoes)) * 100, 1) : 0 }}%
                </span>
            </div>
        @endif
    </div>

    <div class="legenda">
        <strong>Legenda:</strong>
        @if($isDecimaTerceiraFinal)
            CFD = Classificação Final da Disciplina | PC = Média dos CFDs da 13ª | E.C.S = Exame de Curso | PAP = Prova de Aptidão Profissional | MF = Média Final
        @elseif($trimestre === '1')
            MAC1 = Média de Avaliações Contínuas | PP1 = Prova do Professor | PT1 = Prova Trimestral | MT1 = Média do 1º Trimestre
        @elseif($trimestre === '2')
            MAC2 = Média de Avaliações Contínuas | PP2 = Prova do Professor | PT2 = Prova Trimestral | MT2 = Média do 2º Trimestre
        @elseif($trimestre === '3')
            MAC3 = Média de Avaliações Contínuas | PP3 = Prova do Professor | {{ $labelAvaliacaoFinalTerceiro }} = {{ $usaPgTerceiro ? 'Prova Global' : 'Prova Trimestral do 3º Trimestre' }} | MT3 = Média do 3º Trimestre | CF = Classificação Final | CA = Classificação Anual
        @else
            MT1, MT2, MT3 = Médias dos Trimestres | {{ $labelAvaliacaoFinalTerceiro }} = {{ $usaPgTerceiro ? 'Prova Global' : 'Prova Trimestral do 3º Trimestre' }} | CFD = Classificação Final da Disciplina
        @endif
    </div>

    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <p>Sistema de Gestão Escolar - NotasEscola AO</p>
    </div>

</body>
</html>

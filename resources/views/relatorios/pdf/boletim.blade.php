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

        .foto-perfil {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3B82F6;
            float: right;
            margin-left: 20px;
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
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1>📋 BOLETIM ESCOLAR</h1>
        <p>{{ $turma->curso->nome }} - {{ $turma->classe }}ª Classe</p>
    </div>

    <!-- Informações do Aluno -->
    <div class="info-section">
        <img src="{{ public_path($aluno->foto_perfil ?: 'storage/fotos_perfil/default.png') }}" alt="Foto" class="foto-perfil">
        
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

    <!-- Notas -->
    <table>
        <thead>
            <tr>
                <th width="30%">Disciplina</th>
                <th width="10%">MT1</th>
                <th width="10%">MT2</th>
                <th width="10%">MT3</th>
                <th width="10%">PG</th>
                <th width="10%">CFD</th>
                <th width="20%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $nota)
            <tr>
                <td class="disciplina">{{ $nota->disciplina->nome }}</td>
                <td>{{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}</td>
                <td>{{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}</td>
                <td>{{ $nota->mt3 ? number_format($nota->mt3, 2) : '-' }}</td>
                <td>{{ $nota->pg ? number_format($nota->pg, 2) : '-' }}</td>
                <td><strong>{{ $nota->cfd ? number_format($nota->cfd, 2) : '-' }}</strong></td>
                <td class="{{ $nota->isAprovado() ? 'aprovado' : 'reprovado' }}">
                    {{ $nota->cfd ? ($nota->isAprovado() ? '✓ Aprovado' : '✗ Reprovado') : 'Pendente' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Resumo -->
    <div class="summary">
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
    </div>

    <!-- Legenda -->
    <div class="legenda">
        <strong>Legenda:</strong>
        MT1, MT2, MT3 = Médias dos Trimestres | 
        PG = Prova Global | 
        CFD = Classificação Final da Disciplina
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <p>Sistema de Gestão Escolar - NotasEscola 🇦🇴</p>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Académico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 22px;
            color: #3B82F6;
            margin-bottom: 5px;
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
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3B82F6;
            float: right;
            margin-left: 15px;
        }

        .ano-letivo {
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .ano-letivo-header {
            background: #3B82F6;
            color: white;
            padding: 10px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th {
            background: #E0E7FF;
            color: #1E40AF;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #C7D2FE;
            font-size: 10px;
        }

        td {
            padding: 8px 6px;
            border: 1px solid #E5E7EB;
            text-align: center;
            font-size: 10px;
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

        .summary-box {
            background: #EFF6FF;
            border: 2px solid #3B82F6;
            padding: 10px;
            border-radius: 0 0 5px 5px;
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
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
            font-size: 13px;
            margin-top: 3px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #6B7280;
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
        }

        .assinatura {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
        }

        .assinatura-item {
            text-align: center;
            width: 40%;
        }

        .linha {
            border-top: 1px solid #000;
            margin-top: 30px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1>📚 HISTÓRICO ACADÉMICO</h1>
        <p style="font-size: 12px;">Documento Oficial de Aproveitamento Escolar</p>
    </div>

    <!-- Informações do Aluno -->
    <div class="info-section">
        <img src="{{ public_path($aluno->foto_perfil ?: 'storage/fotos_perfil/default.png') }}" alt="Foto" class="foto-perfil">
        
        <div class="info-row">
            <span class="info-label">Nome Completo:</span>
            <span class="info-value">{{ $aluno->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nº Processo:</span>
            <span class="info-value">{{ $aluno->numero_processo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Bilhete de Identidade:</span>
            <span class="info-value">{{ $aluno->bi ?? 'Não informado' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Data de Nascimento:</span>
            <span class="info-value">{{ $aluno->data_nascimento ? $aluno->data_nascimento->format('d/m/Y') : 'Não informado' }}</span>
        </div>
        @if($aluno->nome_encarregado)
        <div class="info-row">
            <span class="info-label">Encarregado de Educação:</span>
            <span class="info-value">{{ $aluno->nome_encarregado }} - {{ $aluno->contacto_encarregado }}</span>
        </div>
        @endif
    </div>

    <!-- Histórico por Ano Letivo -->
    @foreach($historico as $anoLetivoId => $registros)
        @php
            $anoLetivo = $registros->first()->anoLetivo;
            $turma = $registros->first()->turma;
            $mediaGeral = $registros->avg('classificacao_final');
            $aprovacoes = $registros->filter(fn($r) => $r->classificacao_final >= 10)->count();
        @endphp

        <div class="ano-letivo">
            <div class="ano-letivo-header">
                {{ $anoLetivo->nome }} - {{ $turma->nome_completo }} ({{ $turma->curso->nome }})
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="35%">Disciplina</th>
                        <th width="20%">Classe</th>
                        <th width="20%">Classificação Final</th>
                        <th width="20%">Data de Conclusão</th>
                        <th width="25%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registros as $registro)
                    <tr>
                        <td class="disciplina">{{ $registro->disciplina->nome }}</td>
                        <td>{{ $registro->classe }}ª</td>
                        <td><strong>{{ number_format($registro->classificacao_final, 2) }}</strong></td>
                         <td>{{ optional($registro->data_conclusao)->format('d/m/Y') }}</td>
                        <td class="{{ $registro->classificacao_final >= 10 ? 'aprovado' : 'reprovado' }}">
                            {{ $registro->classificacao_final >= 10 ? 'Aprovado' : 'Reprovado' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary-box">
                <div class="summary-item">
                    <div class="summary-label">Média Geral</div>
                    <div class="summary-value">{{ number_format($mediaGeral, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Aprovações</div>
                    <div class="summary-value" style="color: #10B981;">{{ $aprovacoes }}/{{ $registros->count() }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Resultado</div>
                    <div class="summary-value" style="color: {{ $aprovacoes == $registros->count() ? '#10B981' : '#EF4444' }};">
                        {{ $aprovacoes == $registros->count() ? 'Aprovado' : 'Reprovado' }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if($historico->count() == 0)
    <div style="text-align: center; padding: 40px; color: #6B7280;">
        <p style="font-size: 16px;">Nenhum histórico disponível</p>
    </div>
    @endif

    <!-- Assinaturas -->
    <div class="assinatura">
        <div class="assinatura-item">
            <div class="linha"></div>
            <div>Secretaria Escolar</div>
        </div>
        <div class="assinatura-item">
            <div class="linha"></div>
            <div>Direção</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <p><strong>Este documento tem validade oficial</strong></p>
        <p>Sistema de Gestão Escolar - NotasEscola 🇦🇴</p>
    </div>

</body>
</html>

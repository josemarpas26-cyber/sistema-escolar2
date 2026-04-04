<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico Académico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

        /* ═══ CLEARFIX ═══ */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* ═══ HEADER ═══ */
        .header {
            padding: 18px 32px 14px;
            border-bottom: 3px solid #1a1a6e;
        }
        .header-logo {
            float: left;
            height: 72px;
            width: auto;
            margin-right: 14px;
        }
        .school-info {
            float: left;
            line-height: 1.45;
            padding-top: 8px;
        }
        .school-name { font-size: 13px; font-weight: 700; color: #1a1a6e; text-transform: uppercase; }
        .school-sub  { font-size: 10.5px; color: #555; text-transform: uppercase; }
        .school-num  { font-size: 10.5px; color: #555; }
        .header-right {
            float: right;
            text-align: right;
            padding-top: 12px;
        }
        .doc-title { font-size: 22px; font-weight: 900; color: #1a1a6e; text-transform: uppercase; letter-spacing: 1px; }
        .doc-subtitle { font-size: 10px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        .accent-bar { height: 4px; background: #1a1a6e; margin: 0 32px; opacity: .15; }

        /* ═══ STUDENT SECTION ═══ */
        .student-section {
            margin: 18px 32px;
        }
        .photo-box {
            float: right;
            width: 100px;
            height: 118px;
            border: 1.5px solid #cbd5e1;
            overflow: hidden;
            background: #f8fafc;
            margin-left: 16px;
        }
        .photo-box img {
            width: 100px;
            height: 118px;
        }
        .student-fields {
            overflow: hidden; /* contain floats */
        }
        .field-row {
            margin-bottom: 6px;
        }
        .field-label {
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            width: 165px;
            vertical-align: top;
        }
        .field-value {
            display: inline-block;
            font-size: 13px;
            font-weight: 600;
            color: #111;
            vertical-align: top;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 18px 32px;
        }

        /* ═══ TABLE ═══ */
        .section-wrap { margin: 16px 32px; }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: #1a1a6e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 2px solid #1a1a6e;
        }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead tr { background: #1a1a6e; color: #fff; }
        thead th {
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:nth-child(odd)  { background: #ffffff; }
        tbody td {
            padding: 7px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11.5px;
        }
        .td-center { text-align: center; }
        .badge-aprovado  { color: #16a34a; font-weight: 700; }
        .badge-reprovado { color: #dc2626; font-weight: 700; }
        .badge-transito  { color: #d97706; font-weight: 700; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        .empty-title { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 4px; }
        .empty-sub { font-size: 11px; color: #94a3b8; }

        /* ═══ SIGNATURES (usando table) ═══ */
        .signatures-table {
            width: 80%;
            margin: 40px auto 20px;
        }
        .signatures-table td {
            text-align: center;
            padding: 0 20px;
            width: 50%;
        }
        .sig-line {
            border-top: 1.5px solid #1a1a1a;
            margin-bottom: 6px;
        }
        .sig-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 12px 32px;
            text-align: center;
            margin-top: 10px;
        }
        .footer p { font-size: 9px; color: #64748b; line-height: 1.6; }
        .footer .system-name { color: #1a1a6e; font-weight: 700; font-size: 10px; }
    </style>
</head>
<body>

@php
    // ─── Logo em base64 ───
    $logoPath = public_path('images/logo1.png');
    $logoBase64 = null;
    if (file_exists($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoMime = mime_content_type($logoPath);
        $logoBase64 = "data:{$logoMime};base64,{$logoData}";
    }

    // ─── Foto do aluno em base64 ───
    $fotoBase64 = $aluno->foto_perfil_pdf_src ?? null;
@endphp

{{-- ═══ HEADER ═══ --}}
<div class="header clearfix">
    @if($logoBase64)
        <img src="{{ $logoBase64 }}" alt="Logo" class="header-logo">
    @endif
    <div class="school-info">
        <div class="school-name">IPIKK - NV</div>
        <div class="school-sub">Instituto Politécnico Industrial do Kilamba</div>
        <div class="school-num">Nº 0050</div>
    </div>
    <div class="header-right">
        <div class="doc-title">Histórico Académico</div>
        <div class="doc-subtitle">Documento Oficial de Aproveitamento Escolar</div>
    </div>
</div>

<div class="accent-bar"></div>

{{-- ═══ DADOS DO ALUNO ═══ --}}
<div class="student-section clearfix">
    {{-- Foto PRIMEIRO (float right) --}}
    <div class="photo-box">
        @if($fotoBase64)
            <img src="{{ $fotoBase64 }}" alt="Foto do Aluno">
        @endif
    </div>

    {{-- Campos DEPOIS --}}
    <div class="student-fields">
        <div class="field-row">
            <span class="field-label">Nome Completo:</span>
            <span class="field-value">{{ $aluno->name ?? '—' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Nº Processo:</span>
            <span class="field-value">{{ $aluno->numero_processo ?? '—' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Bilhete de Identidade:</span>
            <span class="field-value">{{ $aluno->bi ?? $aluno->bilhete_identidade ?? '—' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Data de Nascimento:</span>
            <span class="field-value">
                {{ $aluno->data_nascimento ? \Carbon\Carbon::parse($aluno->data_nascimento)->format('d/m/Y') : '—' }}
            </span>
        </div>
        <div class="field-row">
            <span class="field-label">Encarregado de Educação:</span>
            <span class="field-value">
                {{ $aluno->nome_encarregado ?? $aluno->encarregado ?? '—' }}
                @if(!empty($aluno->contacto_encarregado)) - {{ $aluno->contacto_encarregado }} @endif
            </span>
        </div>
    </div>
</div>

<hr class="divider">

{{-- ═══ HISTÓRICO ═══ --}}
<div class="section-wrap">
    @if(isset($historico) && $historico->count() > 0)
        <div class="section-title">Histórico Escolar</div>
        <table>
            <thead>
                <tr>
                    <th>Ano Letivo</th>
                    <th>Disciplina</th>
                    <th>Turma</th>
                    <th class="td-center">Classe</th>
                    <th class="td-center">CFD</th>
                    <th class="td-center">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($historico as $registros)
                    @foreach($registros as $row)
                        <tr>
                            <td>{{ $row->anoLetivo->nome ?? '—' }}</td>
                            <td>{{ $row->disciplina->nome ?? '—' }}</td>
                            <td>{{ $row->turma->nome_completo ?? $row->turma->nome ?? '—' }}</td>
                            <td class="td-center">{{ $row->classe ?? '—' }}</td>
                            <td class="td-center">{{ isset($row->classificacao_final) ? number_format($row->classificacao_final, 2, ',', '.') : '—' }}</td>
                            <td class="td-center">
                                @php $r = strtolower($row->resultado ?? ''); @endphp
                                @if(str_contains($r, 'aprovado') && !str_contains($r, 'reprovado'))
                                    <span class="badge-aprovado">Aprovado</span>
                                @elseif(str_contains($r, 'reprovado'))
                                    <span class="badge-reprovado">Reprovado</span>
                                @else
                                    <span class="badge-transito">{{ $row->resultado ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-title">Nenhum histórico disponível</div>
            <div class="empty-sub">
                As notas do aluno para o ano letivo atual não foram inseridas
                ou processadas no sistema.
            </div>
        </div>
    @endif
</div>

{{-- ═══ ASSINATURAS (table layout) ═══ --}}
<table class="signatures-table">
    <tr>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">Secretaria Escolar</div>
        </td>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">Direção Geral</div>
        </td>
    </tr>
</table>

{{-- ═══ FOOTER ═══ --}}
<div class="footer">
    <p>
        DOCUMENTO GERADO ELECTRONICAMENTE EM
        {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }}<br>
        ESTE DOCUMENTO TEM VALIDADE OFICIAL QUANDO DEVIDAMENTE CARIMBADO E ASSINADO
    </p>
    <p class="system-name">SISTEMA DE GESTÃO ESCOLAR - IPIKK-NV</p>
</div>

</body>
</html>
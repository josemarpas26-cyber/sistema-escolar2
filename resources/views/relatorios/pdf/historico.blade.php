<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
  <title>Histórico Académico</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 32px 14px;
      border-bottom: 3px solid #1a1a6e;
    }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .header-left img { height: 72px; width: auto; }
    .school-info { line-height: 1.45; }
    .school-name { font-size: 13px; font-weight: 700; color: #1a1a6e; text-transform: uppercase; }
    .school-sub  { font-size: 10.5px; color: #555; text-transform: uppercase; }
    .school-num  { font-size: 10.5px; color: #555; }
    .header-right { text-align: right; }
    .doc-title { font-size: 22px; font-weight: 900; color: #1a1a6e; text-transform: uppercase; letter-spacing: 1px; }
    .doc-subtitle { font-size: 10px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

    .accent-bar { height: 4px; background: #1a1a6e; margin: 0 32px 0; opacity: .15; }

    .student-section {
      margin: 18px 32px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
    }
    .student-fields { flex: 1; }
    .field-row { display: flex; gap: 0; margin-bottom: 6px; }
    .field-label {
      font-size: 9.5px; font-weight: 700; color: #555;
      text-transform: uppercase; letter-spacing: 0.4px;
      width: 165px; padding-top: 1px;
    }
    .field-value { font-size: 13px; font-weight: 600; color: #111; }
    .photo-box {
      width: 100px; height: 118px;
      border: 1.5px solid #cbd5e1;
      border-radius: 6px;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: #94a3b8; font-size: 8px; text-align: center;
      gap: 4px; flex-shrink: 0;
      overflow: hidden;
    }
    .photo-box svg { width: 28px; height: 28px; opacity: .4; }
    .photo-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }

    .divider { border: none; border-top: 1px solid #e2e8f0; margin: 0 32px; }

    .section-wrap { margin: 16px 32px; }
    .section-title {
      font-size: 10px; font-weight: 700;
      color: #1a1a6e; text-transform: uppercase;
      letter-spacing: 0.5px; margin-bottom: 10px;
      padding-bottom: 4px;
      border-bottom: 2px solid #1a1a6e;
    }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead tr { background: #1a1a6e; color: #fff; }
    thead th {
      padding: 8px 12px; text-align: left;
      font-weight: 600; font-size: 10.5px;
      text-transform: uppercase; letter-spacing: 0.3px;
    }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr:nth-child(odd)  { background: #ffffff; }
    tbody td { padding: 7px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; }
    .td-center { text-align: center; }
    .badge-aprovado  { color: #16a34a; font-weight: 700; }
    .badge-reprovado { color: #dc2626; font-weight: 700; }
    .badge-transito  { color: #d97706; font-weight: 700; }

    .empty-state {
      text-align: center; padding: 40px 20px; color: #94a3b8;
    }
    .empty-state svg {
      width: 44px; height: 44px; margin: 0 auto 10px;
      display: block; opacity: .4;
    }
    .empty-title { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 4px; }
    .empty-sub { font-size: 11px; color: #94a3b8; }

    .signatures {
      display: flex;
      justify-content: space-around;
      margin: 40px 32px 20px;
      padding-top: 10px;
    }
    .sig-block { text-align: center; width: 190px; }
    .sig-line { border-top: 1.5px solid #1a1a1a; margin-bottom: 6px; }
    .sig-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

    .footer {
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      padding: 12px 32px;
      text-align: center;
      margin-top: 10px;
    }
    .footer p { font-size: 9px; color: #64748b; line-height: 1.6; }
    .footer .system-name {
      color: #1a1a6e; font-weight: 700; font-size: 10px;
    }
  </style>
</head>
<body>

<div class="header">
    <div class="header-left">
      <img src="{{ asset('images/logo1.png') }}" alt="Logo">
      <div class="school-info">
        <div class="school-name">IPIKK - NV</div>
        <div class="school-sub">Instituto Politécnico Industrial do Kilamba</div>
        <div class="school-num">Nº 0050</div>
      </div>
    </div>
    <div class="header-right">
      <div class="doc-title">Histórico Académico</div>
      <div class="doc-subtitle">Documento Oficial de Aproveitamento Escolar</div>
    </div>
  </div>
  <div class="accent-bar"></div>

  <div class="student-section">
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
    <div class="photo-box">
      @php
        $fotoRelativa = $aluno->foto_perfil ?? $aluno->foto ?? null;
      @endphp

      @if($fotoRelativa)
      @php
          $fotoRelativa = $aluno->foto_perfil ?? $aluno->foto ?? null;
          $fotoSrc = null;

          if ($fotoRelativa) {
              $storagePath = storage_path('app/public/' . $fotoRelativa);
              $directPath  = public_path('storage/' . $fotoRelativa);
              $publicPath  = public_path($fotoRelativa);

              if (request('formato') === 'pdf') {
                  // PDF: caminho absoluto do disco, nunca URL HTTP
                  if (file_exists($storagePath))     $fotoSrc = $storagePath;
                  elseif (file_exists($directPath))  $fotoSrc = $directPath;
                  elseif (file_exists($publicPath))  $fotoSrc = $publicPath;
              } else {
                  // Web: URL normal
                  $fotoSrc = asset('storage/' . ltrim($fotoRelativa, '/'));
              }
          }
      @endphp

      <div class="photo-box">
          @if($fotoSrc)
              <img src="{{ $fotoSrc }}" alt="Foto do Aluno">
          @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                  <circle cx="12" cy="7" r="4"/>
              </svg>
              <span>Foto do Aluno</span>
          @endif
      </div>

        @if($fotoSrc)
          <img src="{{ $fotoSrc }}" alt="Foto do Aluno" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">
        @else
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <span>Foto do Aluno</span>
        @endif
      @else
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Foto do Aluno</span>
      @endif
    </div>
  </div>

  <hr class="divider">

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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19 a2 2 0 01-2 2z"/>
        </svg>
        <div class="empty-title">Nenhum histórico disponível</div>
        <div class="empty-sub">
          As notas do aluno para o ano letivo atual não foram inseridas
          ou processadas no sistema.
        </div>
      </div>
    @endif
    </div>

  <div class="signatures">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-label">Secretaria Escolar</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-label">Direção Geral</div>
    </div>
      </div>

  <div class="footer">
    <p>
      DOCUMENTO GERADO ELECTRONICAMENTE EM
      {{ now()->format('d/m/Y') }}
      {{ now()->format('H:i') }}<br>
      ESTE DOCUMENTO TEM VALIDADE OFICIAL QUANDO DEVIDAMENTE CARIMBADO E ASSINADO
    </p>
    <p class="system-name">SISTEMA DE GESTÃO ESCOLAR - IPIKK-NV</p>
  </div>

</body>
</html>

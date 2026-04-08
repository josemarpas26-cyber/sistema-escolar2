<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Boletins em Massa</title>
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .row { width: 100%; margin-bottom: 12px; }
        .col { width: 48.7%; display: inline-block; vertical-align: top; }
        .col + .col { margin-left: 2%; }
        .boletim { border: 1px solid #d9d9d9; padding: 6px; min-height: 355px; }
        .school { text-align: center; font-size: 9px; font-weight: 700; }
        .area, .curso, .titulo, .periodo { text-align: center; font-size: 9px; }
        .titulo { color: #00B050; font-weight: 700; }
        .curso { font-weight: 700; }
        .aluno { margin-top: 5px; color: #f00; font-weight: 700; font-size: 10px; text-transform: uppercase; }
        .meta { color: #002060; font-size: 9px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9d9d9; padding: 2px 4px; font-size: 9px; }
        th { color: #00B050; font-weight: 700; text-align: center; }
        td:first-child { width: 58%; }
        td:not(:first-child) { text-align: center; }
        .diretor { margin-top: 8px; }
        .label { font-size: 9px; }
        .nome-diretor { margin-top: 6px; font-size: 10px; }
        .logo { text-align: center; margin-bottom: 2px; }
        .logo img { height: 30px; }
    </style>
</head>
<body>
@php
    $alunos = $turma->alunos->values();
    $chunks = $alunos->chunk(2);
@endphp

@foreach($chunks as $dupla)
<div class="page">
    <div class="row">
        @foreach($dupla as $index => $aluno)
            @php
                $notas = $notasPorAluno->get($aluno->id, collect())->sortBy(fn($nota) => $nota->disciplina?->nome)->take(12)->values();
                $numeroOrdem = $alunos->search(fn($item) => $item->id === $aluno->id) + 1;
            @endphp
            <div class="col">
                <div class="boletim">
                    <div class="logo">
                        <img src="{{ public_path('images/logo1.png') }}" alt="Logo">
                    </div>
                    <div class="school">{{ config('app.nome_escola', 'INST. POLITÉCN. INDUSTRIAL Nº 8050 LDA - NOVA VIDA - KILAMBA KIAXI') }}</div>
                    <div class="area">{{ strtoupper(config('app.area_formacao', 'ÁREA DE FORMAÇÃO DE INFORMÁTICA')) }}</div>
                    <div class="curso">CURSO DE {{ strtoupper($turma->curso?->nome ?? 'CURSO') }}</div>
                    <div class="titulo">BOLETIM DE NOTAS</div>
                    <div class="periodo">ANO LECTIVO: {{ $turma->anoLetivo?->nome }} — {{ $periodoLabel }}</div>
                    <div class="aluno">{{ $aluno->name }}</div>
                    <div class="meta">{{ $turma->classe }}.ª CLASSE &nbsp; Nº {{ $numeroOrdem }} &nbsp; TURMA: {{ $turma->nome }} &nbsp; SALA Nº {{ $turma->sala ?? '—' }}</div>

                    <table>
                        <thead>
                            <tr>
                                <th>DISCIPLINA</th>
                                @foreach($configNotas as $config)
                                    <th>{{ $config['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 12; $i++)
                                @php $nota = $notas[$i] ?? null; @endphp
                                <tr>
                                    <td>{{ $nota?->disciplina?->nome ?? '' }}</td>
                                    @foreach($configNotas as $config)
                                        @php $valor = $nota?->{$config['key']}; @endphp
                                        <td>{{ $valor !== null ? number_format((float) $valor, 2, ',', '') : '' }}</td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    <div class="diretor">
                        <div class="label">O DIRECTOR DE TURMA:</div>
                        <div class="nome-diretor">{{ $turma->coordenadorTurma?->name ?? '' }}</div>
                    </div>
                </div>
            </div>
        @endforeach

        @if($dupla->count() === 1)
            <div class="col"></div>
        @endif
    </div>
</div>
@endforeach
</body>
</html>

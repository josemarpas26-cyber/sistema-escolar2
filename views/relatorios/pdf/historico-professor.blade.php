<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico do Professor</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { text-align: center; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #eff6ff; text-align: left; }
        .bloco { margin-top: 14px; }
    </style>
</head>
<body>
<h1>Histórico de Turmas Lecionadas</h1>
<p><strong>Professor:</strong> {{ $professor->name }}</p>

@forelse($atribuicoes as $anoId => $lista)
    <div class="bloco">
        <h3>{{ optional($lista->first()->anoLetivo)->nome ?? 'Ano letivo' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Turma</th>
                    <th>Curso</th>
                    <th>Disciplina</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lista as $item)
                    <tr>
                        <td>{{ $item->turma->nome_completo }}</td>
                        <td>{{ $item->turma->curso->nome }}</td>
                        <td>{{ $item->disciplina->nome }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p>Sem atribuições encontradas para este professor.</p>
@endforelse
</body>
</html>

<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\Nota;

class CoordenacaoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        abort_unless($user->isCoordenadorCurso() || $user->isCoordenadorTurma(), 403, 'Área exclusiva da coordenação.');

        $anoLetivoAtivo = AnoLetivo::ativo()->first();

        $curso = $user->cursoCoordenado()
            ->with(['turmas' => fn ($query) => $query->withCount('alunos')->orderBy('nome')])
            ->first();

        $turma = $user->turmaCoordenada()
            ->with(['curso', 'disciplinas', 'alunos'])
            ->first();

        $totalAlunosTurma = $turma?->alunos->count() ?? 0;

        $mediaTurma = null;
        if ($anoLetivoAtivo && $turma) {
            $mediaTurma = Nota::query()
                ->where('turma_id', $turma->id)
                ->where('ano_letivo_id', $anoLetivoAtivo->id)
                ->whereNotNull('cfd')
                ->avg('cfd');
        }

        return view('coordenacao.index', [
            'curso' => $curso,
            'turma' => $turma,
            'anoLetivoAtivo' => $anoLetivoAtivo,
            'totalAlunosTurma' => $totalAlunosTurma,
            'mediaTurma' => $mediaTurma,
        ]);
    }
}

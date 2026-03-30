<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        return match (true) {
            $user->isAdmin() => $this->adminDashboard($request),
            $user->isSecretaria() => $this->secretariaDashboard($request),
            $user->isProfessor() => $this->professorDashboard($request),
            $user->isAluno() => $this->alunoDashboard($request),
            default => abort(403, 'Papel de usuário não reconhecido'),
        };
    }

    private function adminDashboard(Request $request)
    {
        $stats = $this->dashboardService->adminStats($request->only(['curso_id', 'turma_id', 'disciplina_id']));

        return view('dashboard.admin', $stats);
    }

    private function secretariaDashboard(Request $request)
    {
        $anoLetivo = AnoLetivo::ativo()->first();
        $stats = $this->dashboardService->secretariaStats(
            $anoLetivo,
            $request->only(['curso_id', 'turma_id', 'disciplina_id'])
        );

        return view('dashboard.secretaria', $stats);
    }

    private function professorDashboard(Request $request)
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $stats = $this->dashboardService->professorStats(
            $professor,
            $anoLetivo,
            $request->only(['curso_id', 'turma_id', 'disciplina_id'])
        );

        return view('dashboard.professor', $stats);
    }

    private function alunoDashboard(Request $request)
    {
        $aluno = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $stats = $this->dashboardService->alunoStats(
            $aluno,
            $anoLetivo,
            $request->only(['curso_id', 'turma_id', 'disciplina_id'])
        );

        return view('dashboard.aluno', $stats);
    }
}

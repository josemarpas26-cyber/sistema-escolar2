<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index()
    {
        $user = auth()->user();

        return match (true) {
            $user->isAdmin() => $this->adminDashboard(),
            $user->isSecretaria() => $this->secretariaDashboard(),
            $user->isProfessor() => $this->professorDashboard(),
            $user->isAluno() => $this->alunoDashboard(),
            default => abort(403, 'Papel de usuário não reconhecido'),
        };
    }

    private function adminDashboard()
    {
        $stats = $this->dashboardService->adminStats();

        return view('dashboard.admin', $stats);
    }

    private function secretariaDashboard()
    {
        $anoLetivo = AnoLetivo::ativo()->first();
        $stats = $this->dashboardService->secretariaStats($anoLetivo);

        return view('dashboard.secretaria', $stats);
    }

    private function professorDashboard()
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $stats = $this->dashboardService->professorStats($professor, $anoLetivo);

        return view('dashboard.professor', $stats);
    }

    private function alunoDashboard()
    {
        $aluno = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $stats = $this->dashboardService->alunoStats($aluno, $anoLetivo);

        return view('dashboard.aluno', $stats);
    }
}

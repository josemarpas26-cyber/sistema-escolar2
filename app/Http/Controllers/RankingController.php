<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request)
    {
        $stats = $this->dashboardService->rankingStats(
            auth()->user(),
            $request->only(['curso_id', 'turma_id', 'disciplina_id'])
        );

        return view('ranking.index', $stats);
    }
}

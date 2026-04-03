<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Services\EstatisticasAcademicasService;
use Illuminate\Http\Request;

class EstatisticasController extends Controller
{
    public function __construct(
        private readonly EstatisticasAcademicasService $estatisticasAcademicas
    ) {}

    public function index(Request $request)
    {
        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $user = auth()->user();
        $secoes = $this->estatisticasAcademicas->construirSecoes($user, $anoLetivo);

        if ($secoes->isEmpty()) {
            abort(403, 'Perfil sem acesso a estatisticas.');
        }

        return view('estatisticas.index', [
            'anoLetivo' => $anoLetivo,
            'contextos' => $secoes->pluck('tipo')->unique()->values(),
            'secoes' => $secoes,
        ]);
    }
}

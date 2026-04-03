<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\Turma;
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

        // ── Filtros disponíveis para o utilizador ─────────────────────────
        $filtros = $this->construirFiltrosDisponiveis($user, $anoLetivo, $secoes);

        // ── Aplicar filtros da request ────────────────────────────────────
        $turmaFiltroId      = $request->integer('turma_id')      ?: null;
        $disciplinaFiltroId = $request->integer('disciplina_id') ?: null;
        $trimestre          = $request->input('trimestre', 'todos');
        $secaoTipo          = $request->input('secao', null);

        // Filtrar as secoes pelo tipo selecionado
        $secoesFiltradas = $secoes;
        if ($secaoTipo) {
            $secoesFiltradas = $secoes->filter(fn ($s) => $s['tipo'] === $secaoTipo)->values();
        }

        // Aplicar filtro de turma e disciplina dentro das seções
        if ($turmaFiltroId || $disciplinaFiltroId || $trimestre !== 'todos') {
            $secoesFiltradas = $this->aplicarFiltrosNasSecoes(
                $secoesFiltradas, $turmaFiltroId, $disciplinaFiltroId, $trimestre
            );
        }

        return view('estatisticas.index', [
            'anoLetivo'          => $anoLetivo,
            'contextos'          => $secoes->pluck('tipo')->unique()->values(),
            'secoes'             => $secoesFiltradas,
            'secoesOriginais'    => $secoes,
            'filtros'            => $filtros,
            'filtroTurmaId'      => $turmaFiltroId,
            'filtroDisciplinaId' => $disciplinaFiltroId,
            'filtroTrimestre'    => $trimestre,
            'filtroSecaoTipo'    => $secaoTipo,
        ]);
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    private function construirFiltrosDisponiveis($user, $anoLetivo, $secoes): array
    {
        $turmaIds      = collect();
        $disciplinaIds = collect();

        foreach ($secoes as $secao) {
            switch ($secao['tipo']) {
                case 'professor':
                    foreach ($secao['itens'] as $item) {
                        $turmaIds->push($item['turma']->id);
                        $disciplinaIds->push($item['disciplina']->id);
                    }
                    break;

                case 'coord_turma':
                    foreach ($secao['itens'] as $item) {
                        $turmaIds->push($item['turma']->id);
                        foreach ($item['estatisticas'] as $disc) {
                            $disciplinaIds->push($disc['disciplina']->id);
                        }
                    }
                    break;

                case 'coord_curso':
                    foreach ($secao['itens'] as $item) {
                        foreach ($item['turmas'] as $turma) {
                            $turmaIds->push($turma->id);
                        }
                        foreach ($item['estatisticas'] as $disc) {
                            $disciplinaIds->push($disc['disciplina']->id);
                        }
                    }
                    break;

                case 'admin':
                    foreach ($secao['itens'] as $item) {
                        $turmaIds->push($item['turma']->id);
                        foreach ($item['estatisticas'] as $disc) {
                            $disciplinaIds->push($disc['disciplina']->id);
                        }
                    }
                    break;
            }
        }

        $turmas      = Turma::whereIn('id', $turmaIds->unique()->values())
            ->with('curso')
            ->orderBy('classe')
            ->orderBy('nome')
            ->get();

        $disciplinas = Disciplina::whereIn('id', $disciplinaIds->unique()->values())
            ->orderBy('nome')
            ->get();

        return compact('turmas', 'disciplinas');
    }

    private function aplicarFiltrosNasSecoes($secoes, ?int $turmaId, ?int $disciplinaId, string $trimestre)
    {
        return $secoes->map(function ($secao) use ($turmaId, $disciplinaId, $trimestre) {
            $itens = collect($secao['itens']);

            switch ($secao['tipo']) {
                case 'professor':
                    if ($turmaId) {
                        $itens = $itens->filter(fn ($i) => $i['turma']->id === $turmaId);
                    }
                    if ($disciplinaId) {
                        $itens = $itens->filter(fn ($i) => $i['disciplina']->id === $disciplinaId);
                    }
                    if ($trimestre !== 'todos') {
                        $itens = $itens->map(function ($i) use ($trimestre) {
                            $i['trimestres'] = $i['trimestres']->filter(
                                fn ($t) => (string) $t['trimestre'] === $trimestre
                            )->values();
                            return $i;
                        });
                    }
                    break;

                case 'coord_turma':
                case 'admin':
                    if ($turmaId) {
                        $itens = $itens->filter(fn ($i) => $i['turma']->id === $turmaId);
                    }
                    $itens = $itens->map(function ($i) use ($disciplinaId, $trimestre) {
                        $estatisticas = collect($i['estatisticas']);
                        if ($disciplinaId) {
                            $estatisticas = $estatisticas->filter(
                                fn ($e) => $e['disciplina']->id === $disciplinaId
                            );
                        }
                        if ($trimestre !== 'todos') {
                            $estatisticas = $estatisticas->map(function ($e) use ($trimestre) {
                                $e['trimestres'] = $e['trimestres']->filter(
                                    fn ($t) => (string) $t['trimestre'] === $trimestre
                                )->values();
                                return $e;
                            });
                        }
                        $i['estatisticas'] = $estatisticas->values();
                        return $i;
                    });
                    break;

                case 'coord_curso':
                    $itens = $itens->map(function ($i) use ($turmaId, $disciplinaId, $trimestre) {
                        $estatisticas = collect($i['estatisticas']);
                        if ($disciplinaId) {
                            $estatisticas = $estatisticas->filter(
                                fn ($e) => $e['disciplina']->id === $disciplinaId
                            );
                        }
                        if ($trimestre !== 'todos') {
                            $estatisticas = $estatisticas->map(function ($e) use ($trimestre) {
                                $e['trimestres'] = $e['trimestres']->filter(
                                    fn ($t) => (string) $t['trimestre'] === $trimestre
                                )->values();
                                return $e;
                            });
                        }
                        $i['estatisticas'] = $estatisticas->values();
                        if ($turmaId) {
                            $i['turmas'] = $i['turmas']->filter(fn ($t) => $t->id === $turmaId);
                        }
                        return $i;
                    });
                    break;
            }

            $secao['itens'] = $itens->values()->all();
            return $secao;
        })->filter(fn ($s) => count($s['itens']) > 0)->values();
    }
}
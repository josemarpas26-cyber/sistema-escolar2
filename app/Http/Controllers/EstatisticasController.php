<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\Turma;
use App\Models\User;
use App\Services\EstatisticasAcademicasService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
        $alunoFiltroId = $request->integer('aluno_id') ?: null;
        $secoes = $this->estatisticasAcademicas->construirSecoes($user, $anoLetivo, $alunoFiltroId);

        if ($secoes->isEmpty()) {
            abort(403, 'Perfil sem acesso a estatisticas.');
        }

        $filtros = $this->construirFiltrosDisponiveis($user, $anoLetivo, $secoes, $alunoFiltroId);

        $turmaFiltroId = $request->integer('turma_id') ?: null;
        $disciplinaFiltroId = $request->integer('disciplina_id') ?: null;
        $trimestre = $request->input('trimestre', 'todos');
        $secaoTipo = $request->input('secao', null);

        $secoesFiltradas = $secaoTipo
            ? $secoes->filter(fn ($s) => $s['tipo'] === $secaoTipo)->values()
            : $secoes;

        if ($turmaFiltroId || $disciplinaFiltroId || $trimestre !== 'todos') {
            $secoesFiltradas = $this->aplicarFiltrosNasSecoes(
                $secoesFiltradas,
                $turmaFiltroId,
                $disciplinaFiltroId,
                $trimestre
            );
        }

        return view('estatisticas.index', [
            'anoLetivo' => $anoLetivo,
            'contextos' => $secoes->pluck('tipo')->unique()->values(),
            'secoes' => $secoesFiltradas,
            'secoesOriginais' => $secoes,
            'filtros' => $filtros,
            'filtroTurmaId' => $turmaFiltroId,
            'filtroDisciplinaId' => $disciplinaFiltroId,
            'filtroAlunoId' => $alunoFiltroId,
            'filtroTrimestre' => $trimestre,
            'filtroSecaoTipo' => $secaoTipo,
        ]);
    }

    private function construirFiltrosDisponiveis($user, $anoLetivo, $secoes, ?int $alunoId = null): array
    {
        $turmaIds = collect();
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
                case 'admin':
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

                case 'coord_disciplina':
                    foreach ($secao['itens'] as $item) {
                        $disciplinaIds->push($item['disciplina']->id);
                        foreach ($item['turmas'] as $turma) {
                            $turmaIds->push($turma->id);
                        }
                    }
                    break;
            }
        }

        $turmas = Turma::whereIn('id', $turmaIds->unique()->values())
            ->with('curso')
            ->orderBy('classe')
            ->orderBy('nome')
            ->get();

        $disciplinas = Disciplina::whereIn('id', $disciplinaIds->unique()->values())
            ->orderBy('nome')
            ->get();

        $alunos = User::query()
            ->select('users.id', 'users.name')
            ->whereHas('role', fn ($query) => $query->where('name', 'aluno'))
            ->when($alunoId, fn ($query) => $query->where('users.id', $alunoId), function ($query) use ($anoLetivo, $turmaIds, $disciplinaIds) {
                $query->whereIn('users.id', function ($subQuery) use ($anoLetivo, $turmaIds, $disciplinaIds) {
                    $subQuery->select('aluno_id')
                        ->from('notas')
                        ->where('ano_letivo_id', $anoLetivo->id)
                        ->whereIn('turma_id', $turmaIds->unique()->values())
                        ->whereIn('disciplina_id', $disciplinaIds->unique()->values())
                        ->distinct();
                });
            })
            ->orderBy('users.name')
            ->get();

        return compact('turmas', 'disciplinas', 'alunos');
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
                    $itens = $itens->map(function ($item) use ($trimestre) {
                        $item['trimestres'] = $this->filtrarTrimestres(collect($item['trimestres']), $trimestre);
                        $item['resumo'] = $this->resumirTrimestres($item['trimestres']);

                        return $item;
                    });
                    break;

                case 'coord_turma':
                case 'admin':
                    if ($turmaId) {
                        $itens = $itens->filter(fn ($i) => $i['turma']->id === $turmaId);
                    }
                    $itens = $itens->map(function ($item) use ($disciplinaId, $trimestre) {
                        $estatisticas = collect($item['estatisticas']);

                        if ($disciplinaId) {
                            $estatisticas = $estatisticas->filter(
                                fn ($estatistica) => $estatistica['disciplina']->id === $disciplinaId
                            );
                        }

                        $estatisticas = $estatisticas->map(function ($estatistica) use ($trimestre) {
                            $estatistica['trimestres'] = $this->filtrarTrimestres(
                                collect($estatistica['trimestres']),
                                $trimestre
                            );
                            $estatistica['resumo'] = $this->resumirTrimestres($estatistica['trimestres']);

                            return $estatistica;
                        })->values();

                        $item['estatisticas'] = $estatisticas;
                        $item['resumo'] = $this->resumirTrimestres(
                            $estatisticas->flatMap(fn ($estatistica) => $estatistica['trimestres'])->values()
                        );

                        return $item;
                    });
                    break;

                case 'coord_curso':
                    if ($turmaId) {
                        $itens = $itens->filter(
                            fn ($item) => collect($item['turmas'])->contains(fn ($turma) => $turma->id === $turmaId)
                        );
                    }

                    $itens = $itens->map(function ($item) use ($disciplinaId, $trimestre) {
                        $estatisticas = collect($item['estatisticas']);

                        if ($disciplinaId) {
                            $estatisticas = $estatisticas->filter(
                                fn ($estatistica) => $estatistica['disciplina']->id === $disciplinaId
                            );
                        }

                        $estatisticas = $estatisticas->map(function ($estatistica) use ($trimestre) {
                            $estatistica['trimestres'] = $this->filtrarTrimestres(
                                collect($estatistica['trimestres']),
                                $trimestre
                            );
                            $estatistica['resumo'] = $this->resumirTrimestres($estatistica['trimestres']);

                            return $estatistica;
                        })->values();

                        $item['estatisticas'] = $estatisticas;
                        $item['resumo'] = $this->resumirTrimestres(
                            $estatisticas->flatMap(fn ($estatistica) => $estatistica['trimestres'])->values()
                        );

                        return $item;
                    });
                    break;

                case 'coord_disciplina':
                    if ($disciplinaId) {
                        $itens = $itens->filter(fn ($item) => $item['disciplina']->id === $disciplinaId);
                    }

                    if ($turmaId) {
                        $itens = $itens->filter(
                            fn ($item) => collect($item['turmas'])->contains(fn ($turma) => $turma->id === $turmaId)
                        );
                    }

                    $itens = $itens->map(function ($item) use ($turmaId, $trimestre) {
                        $turmas = collect($item['turmas']);

                        if ($turmaId) {
                            $turmas = $turmas->filter(fn ($turma) => $turma->id === $turmaId)->values();
                        }

                        $estatisticas = collect($item['estatisticas'])
                            ->when($turmaId, fn ($collection) => $collection->filter(
                                fn ($estatistica) => $estatistica['turma']->id === $turmaId
                            ))
                            ->map(function ($estatistica) use ($trimestre) {
                                $estatistica['trimestres'] = $this->filtrarTrimestres(
                                    collect($estatistica['trimestres']),
                                    $trimestre
                                );
                                $estatistica['resumo'] = $this->resumirTrimestres($estatistica['trimestres']);

                                return $estatistica;
                            })
                            ->values();

                        $item['turmas'] = $turmas;
                        $item['estatisticas'] = $estatisticas;
                        $item['trimestres'] = $turmaId
                            ? $estatisticas->flatMap(fn ($estatistica) => $estatistica['trimestres'])->values()
                            : $this->filtrarTrimestres(collect($item['trimestres']), $trimestre);
                        $item['resumo'] = $this->resumirTrimestres($item['trimestres']);

                        return $item;
                    });
                    break;
            }

            $secao['itens'] = $itens->values()->all();
            $secao['resumo'] = $this->resumoSecao($secao['tipo'], collect($secao['itens']));

            return $secao;
        })->filter(fn ($secao) => count($secao['itens']) > 0)->values();
    }

    private function filtrarTrimestres(Collection $trimestres, string $trimestre): Collection
    {
        if ($trimestre === 'todos') {
            return $trimestres->values();
        }

        return $trimestres
            ->filter(fn ($item) => (string) $item['trimestre'] === $trimestre)
            ->values();
    }

    private function resumoSecao(string $tipo, Collection $itens): array
    {
        if ($itens->isEmpty()) {
            return $this->resumoVazio();
        }

        return match ($tipo) {
            'professor', 'coord_disciplina' => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => collect($item['trimestres']))->values()
            ),
            default => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => collect($item['estatisticas'] ?? [])
                    ->flatMap(fn ($estatistica) => collect($estatistica['trimestres'])))->values()
            ),
        };
    }

    private function resumirTrimestres(Collection $trimestres): array
    {
        if ($trimestres->isEmpty()) {
            return $this->resumoVazio();
        }

        $totalNotas = $trimestres->sum('total');
        $totalPositivas = $trimestres->sum('positivas');
        $totalNegativas = $trimestres->sum('negativas');
        $somaTotal = (float) $trimestres->sum('soma');

        return [
            'total_notas' => $totalNotas,
            'total_positivas' => $totalPositivas,
            'total_negativas' => $totalNegativas,
            'pct_aprovacao' => $totalNotas > 0 ? round(($totalPositivas / $totalNotas) * 100, 1) : 0,
            'pct_reprovacao' => $totalNotas > 0 ? round(($totalNegativas / $totalNotas) * 100, 1) : 0,
            'media_geral' => $totalNotas > 0 ? round($somaTotal / $totalNotas, 2) : null,
        ];
    }

    private function resumoVazio(): array
    {
        return [
            'total_notas' => 0,
            'total_positivas' => 0,
            'total_negativas' => 0,
            'pct_aprovacao' => 0,
            'pct_reprovacao' => 0,
            'media_geral' => null,
        ];
    }
}

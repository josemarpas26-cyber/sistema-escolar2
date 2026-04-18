<?php

namespace App\Services;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Collection;

class EstatisticasAcademicasService
{
    public function construirSecoes(User $user, AnoLetivo $anoLetivo, ?int $alunoId = null): Collection
    {
        $secoes = collect();

        if ($user->isProfessor()) {
            $secoesProfessor = [
                $this->construirSecaoProfessor($user, $anoLetivo, $alunoId),
                $this->construirSecaoCoordenacaoTurma($user, $anoLetivo, $alunoId),
                $this->construirSecaoCoordenacaoCurso($user, $anoLetivo, $alunoId),
                $this->construirSecaoCoordenacaoDisciplina($user, $anoLetivo, $alunoId),
            ];

            foreach ($secoesProfessor as $secao) {
                if ($secao !== null) {
                    $secoes->push($secao);
                }
            }
        }

        if ($user->isAdmin() || $user->isSecretaria()) {
            $secaoAdministrativa = $this->construirSecaoAdministrativa($anoLetivo, $alunoId);

            if ($secaoAdministrativa !== null) {
                $secoes->push($secaoAdministrativa);
            }
        }

        return $secoes->values();
    }

    public function resumoPauta(Collection $notas): array
    {
        $trimestres = $this->calcularTrimestres($notas);

        return [
            'geral' => $this->resumirTrimestres($trimestres),
            'trimestres' => $trimestres,
            'total_registos' => $notas->count(),
            'finalizadas' => $notas->where('status', 'finalizado')->count(),
            'bloqueadas' => $notas->filter(fn ($nota) => ($nota->bloqueado_t1 ?? false)
                || ($nota->bloqueado_t2 ?? false)
                || ($nota->bloqueado_t3 ?? false))->count(),
            'pendentes' => $notas->whereNull('cfd')->count(),
        ];
    }

    private function construirSecaoProfessor(User $professor, AnoLetivo $anoLetivo, ?int $alunoId = null): ?array
    {
        $atribuicoes = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->unique(fn ($atrib) => $atrib->turma_id.'-'.$atrib->disciplina_id)
            ->sortBy(fn ($atrib) => $this->ordemTurma($atrib->turma).'|'.($atrib->disciplina->nome ?? ''))
            ->values();

        if ($atribuicoes->isEmpty()) {
            return null;
        }

        $notasPorAtribuicao = $this->buscarNotasProfessor($professor, $anoLetivo->id, $alunoId)
            ->groupBy(fn ($nota) => $nota->turma_id.'-'.$nota->disciplina_id);

        $itens = $atribuicoes->map(function ($atrib) use ($notasPorAtribuicao) {
            $chave = $atrib->turma_id.'-'.$atrib->disciplina_id;
            $trimestres = $this->calcularTrimestres($notasPorAtribuicao->get($chave, collect()));

            return [
                'turma' => $atrib->turma,
                'disciplina' => $atrib->disciplina,
                'trimestres' => $trimestres,
                'resumo' => $this->resumirTrimestres($trimestres),
            ];
        })->values();

        return [
            'tipo' => 'professor',
            'titulo' => 'Minhas disciplinas lecionadas',
            'descricao' => 'Cada pauta aparece separada por turma e disciplina, sem misturar atribuicoes do professor.',
            'resumo' => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => $item['trimestres'])->values()
            ),
            'itens' => $itens,
        ];
    }

    private function construirSecaoCoordenacaoTurma(User $professor, AnoLetivo $anoLetivo, ?int $alunoId = null): ?array
    {
        $turmas = Turma::query()
            ->where('coordenador_turma_id', $professor->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplinas', 'curso'])
            ->get()
            ->sortBy(fn ($turma) => $this->ordemTurma($turma))
            ->values();

        if ($turmas->isEmpty()) {
            return null;
        }

        $notasPorTurma = $this->buscarNotasPorEscopo(
            $turmas->flatMap(fn ($turma) => $turma->disciplinas->pluck('id'))->unique()->all(),
            $turmas->pluck('id')->all(),
            $anoLetivo->id,
            $alunoId
        )->groupBy('turma_id');

        $itens = $turmas->map(function (Turma $turma) use ($notasPorTurma) {
            $estatisticas = $this->calcularEstatisticasPorDisciplina(
                $notasPorTurma->get($turma->id, collect())
            );

            return [
                'turma' => $turma,
                'estatisticas' => $estatisticas,
                'resumo' => $this->resumirTrimestres(
                    $estatisticas->flatMap(fn ($disc) => $disc['trimestres'])->values()
                ),
            ];
        })->values();

        return [
            'tipo' => 'coord_turma',
            'titulo' => 'Turmas sob minha coordenacao',
            'descricao' => 'Consolidado da turma por disciplina e trimestre, com o mesmo padrao estatistico da pauta docente.',
            'resumo' => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => $item['estatisticas']->flatMap(fn ($disc) => $disc['trimestres']))->values()
            ),
            'itens' => $itens,
        ];
    }

    private function construirSecaoCoordenacaoCurso(User $professor, AnoLetivo $anoLetivo, ?int $alunoId = null): ?array
    {
        $cursos = Curso::query()
            ->where('coordenador_id', $professor->id)
            ->with([
                'turmas' => fn ($query) => $query
                    ->where('ano_letivo_id', $anoLetivo->id)
                    ->with(['curso', 'disciplinas']),  // ← disciplinas via turma, não via curso
            ])
            ->get()
            ->sortBy('nome')
            ->values();

        if ($cursos->isEmpty()) {
            return null;
        }

        $itens = $cursos->map(function (Curso $curso) use ($anoLetivo, $alunoId) {
            $turmas = $curso->turmas;

            if ($turmas->isEmpty()) {
                return [
                    'curso'        => $curso,
                    'turmas'       => collect(),
                    'estatisticas' => collect(),
                    'resumo'       => $this->resumoVazio(),
                ];
            }

            // Disciplinas vindas das turmas (não do curso diretamente)
            $disciplinaIds = $turmas
                ->flatMap(fn ($turma) => $turma->disciplinas->pluck('id'))
                ->unique()
                ->values()
                ->all();

            $turmaIds = $turmas->pluck('id')->all();

            $notas = $this->buscarNotasPorEscopo($disciplinaIds, $turmaIds, $anoLetivo->id, $alunoId);

            $estatisticas = $this->calcularEstatisticasPorDisciplina($notas);

            return [
                'curso'        => $curso,
                'turmas'       => $turmas,
                'estatisticas' => $estatisticas,
                'resumo'       => $this->resumirTrimestres(
                    $estatisticas->flatMap(fn ($disc) => $disc['trimestres'])->values()
                ),
            ];
        })->values();

        // Remover cursos sem turmas E sem estatísticas
        $itens = $itens->filter(
            fn ($item) => $item['turmas']->isNotEmpty() || $item['estatisticas']->isNotEmpty()
        )->values();

        if ($itens->isEmpty()) {
            return null;
        }

        return [
            'tipo'      => 'coord_curso',
            'titulo'    => 'Cursos sob minha coordenacao',
            'descricao' => 'As notas sao agregadas por disciplina em todas as turmas activas do curso no ano letivo.',
            'resumo'    => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => $item['estatisticas']->flatMap(fn ($disc) => $disc['trimestres']))->values()
            ),
            'itens'     => $itens->all(),
        ];
    }

    private function construirSecaoCoordenacaoDisciplina(User $professor, AnoLetivo $anoLetivo, ?int $alunoId = null): ?array
    {
        $disciplinas = Disciplina::query()
            ->where('coordenador_id', $professor->id)
            ->with([
                'turmas' => fn ($query) => $query
                    ->where('ano_letivo_id', $anoLetivo->id)
                    ->with('curso'),
            ])
            ->orderBy('nome')
            ->get()
            ->values();

        if ($disciplinas->isEmpty()) {
            return null;
        }

        $itens = $disciplinas->map(function (Disciplina $disciplina) use ($anoLetivo, $alunoId) {
            $turmas = $disciplina->turmas
                ->sortBy(fn (Turma $turma) => $this->ordemTurma($turma))
                ->values();

            $notas = $this->buscarNotasPorEscopo(
                [$disciplina->id],
                $turmas->pluck('id')->all(),
                $anoLetivo->id,
                $alunoId
            );

            $notasPorTurma = $notas->groupBy('turma_id');
            $trimestres = $this->calcularTrimestres($notas);

            $estatisticas = $turmas->map(function (Turma $turma) use ($notasPorTurma) {
                $trimestresTurma = $this->calcularTrimestres($notasPorTurma->get($turma->id, collect()));

                return [
                    'turma' => $turma,
                    'trimestres' => $trimestresTurma,
                    'resumo' => $this->resumirTrimestres($trimestresTurma),
                ];
            })->values();

            return [
                'disciplina' => $disciplina,
                'turmas' => $turmas,
                'trimestres' => $trimestres,
                'estatisticas' => $estatisticas,
                'resumo' => $this->resumirTrimestres($trimestres),
            ];
        })->values();

        return [
            'tipo' => 'coord_disciplina',
            'titulo' => 'Disciplinas sob minha coordenacao',
            'descricao' => 'Consolidado anual da disciplina coordenada com detalhe por turma e por trimestre.',
            'resumo' => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => $item['trimestres'])->values()
            ),
            'itens' => $itens,
        ];
    }

    private function construirSecaoAdministrativa(AnoLetivo $anoLetivo, ?int $alunoId = null): ?array
    {
        $turmas = Turma::query()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplinas', 'curso'])
            ->get()
            ->sortBy(fn ($turma) => $this->ordemTurma($turma))
            ->values();

        if ($turmas->isEmpty()) {
            return null;
        }

        $notasPorTurma = $this->buscarNotasPorEscopo(
            Disciplina::ativos()->pluck('id')->all(),
            $turmas->pluck('id')->all(),
            $anoLetivo->id,
            $alunoId
        )->groupBy('turma_id');

        $itens = $turmas->map(function (Turma $turma) use ($notasPorTurma) {
            $estatisticas = $this->calcularEstatisticasPorDisciplina(
                $notasPorTurma->get($turma->id, collect())
            );

            return [
                'turma' => $turma,
                'estatisticas' => $estatisticas,
                'resumo' => $this->resumirTrimestres(
                    $estatisticas->flatMap(fn ($disc) => $disc['trimestres'])->values()
                ),
            ];
        })->values();

        return [
            'tipo' => 'admin',
            'titulo' => 'Visão administrativa',
            'descricao' => 'As notas sao agregadas por disciplina em todas as turmas activas do ano letivo.',
            'resumo' => $this->resumirTrimestres(
                $itens->flatMap(fn ($item) => $item['estatisticas']->flatMap(fn ($disc) => $disc['trimestres']))->values()
            ),
            'itens' => $itens,
        ];
    }

    private function buscarNotasProfessor(User $professor, int $anoLetivoId, ?int $alunoId = null): Collection
    {
        return Nota::query()
            ->select('notas.*')
            ->join('professor_turma_disciplina as atribuicoes', function ($join) use ($professor, $anoLetivoId) {
                $join->on('atribuicoes.turma_id', '=', 'notas.turma_id')
                    ->on('atribuicoes.disciplina_id', '=', 'notas.disciplina_id')
                    ->where('atribuicoes.professor_id', $professor->id)
                    ->where('atribuicoes.ano_letivo_id', $anoLetivoId);
            })
            ->where('notas.ano_letivo_id', $anoLetivoId)
            ->when($alunoId, fn ($query) => $query->where('notas.aluno_id', $alunoId))
            ->with($this->notaRelations())
            ->distinct()
            ->get();
    }

    private function buscarNotasPorEscopo(array $disciplinaIds, array $turmaIds, int $anoLetivoId, ?int $alunoId = null): Collection
    {
        if (empty($disciplinaIds) || empty($turmaIds)) {
            return collect();
        }

        return Nota::query()
            ->whereIn('disciplina_id', array_values(array_unique($disciplinaIds)))
            ->whereIn('turma_id', array_values(array_unique($turmaIds)))
            ->where('ano_letivo_id', $anoLetivoId)
            ->when($alunoId, fn ($query) => $query->where('aluno_id', $alunoId))
            ->with($this->notaRelations())
            ->get();
    }

    private function notaRelations(): array
    {
        return [
            'disciplina:id,nome,codigo',
            'aluno:id,genero',
            'turma:id,nome,classe,curso_id,ano_letivo_id',
            'turma.curso:id,nome',
        ];
    }

    private function calcularEstatisticasPorDisciplina(Collection $notas): Collection
    {
        if ($notas->isEmpty()) {
            return collect();
        }

        return $notas
            ->groupBy('disciplina_id')
            ->sortBy(fn (Collection $notasDisciplina) => $notasDisciplina->first()->disciplina->nome ?? '')
            ->map(function (Collection $notasDisciplina) {
                $trimestres = $this->calcularTrimestres($notasDisciplina);

                return [
                    'disciplina' => $notasDisciplina->first()->disciplina,
                    'trimestres' => $trimestres,
                    'resumo' => $this->resumirTrimestres($trimestres),
                ];
            })
            ->values();
    }

    private function calcularTrimestres(Collection $notas): Collection
    {
        return collect([1, 2, 3])
            ->map(function (int $trimestre) use ($notas) {
                $campo = match ($trimestre) {
                    1 => 'mt1',
                    2 => 'mt2',
                    3 => 'mt3',
                };

                $comNota = $notas->whereNotNull($campo);

                if ($comNota->isEmpty()) {
                    return null;
                }

                $masculino = $comNota->filter(fn ($nota) => $nota->aluno?->genero === 'M');
                $feminino = $comNota->filter(fn ($nota) => $nota->aluno?->genero === 'F');
                $positivas = $comNota->filter(fn ($nota) => (float) $nota->{$campo} >= 10);
                $negativas = $comNota->filter(fn ($nota) => (float) $nota->{$campo} < 10);
                $total = $comNota->count();
                $soma = (float) $comNota->sum($campo);

                return [
                    'trimestre' => $trimestre,
                    'total' => $total,
                    'masculino' => $masculino->count(),
                    'masculino_aprov' => $masculino->filter(fn ($nota) => (float) $nota->{$campo} >= 10)->count(),
                    'feminino' => $feminino->count(),
                    'feminino_aprov' => $feminino->filter(fn ($nota) => (float) $nota->{$campo} >= 10)->count(),
                    'positivas' => $positivas->count(),
                    'negativas' => $negativas->count(),
                    'pct_aprovacao' => $total > 0 ? round(($positivas->count() / $total) * 100, 1) : 0,
                    'pct_reprovacao' => $total > 0 ? round(($negativas->count() / $total) * 100, 1) : 0,
                    'media' => $total > 0 ? round($soma / $total, 2) : null,
                    'soma' => $soma,
                ];
            })
            ->filter()
            ->values();
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

    private function ordemTurma(Turma $turma): string
    {
        return sprintf('%02d-%s', (int) ($turma->classe ?? 0), $turma->nome ?? '');
    }
}

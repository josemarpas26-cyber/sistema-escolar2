<?php

namespace App\Services;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\MetaDisciplina;
use App\Models\Nota;
use App\Models\NotaLog;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL_SECONDS = 300;

    public function adminStats(array $filtrosRanking = []): array
    {
        $stats = Cache::remember('dashboard:admin:stats', self::CACHE_TTL_SECONDS, function () {
            $anoLetivoAtivo = AnoLetivo::ativo()->first();
            $diasRestantes = null;

            if ($anoLetivoAtivo && !$anoLetivoAtivo->encerrado) {
                $diasRestantes = (int) now()->diffInDays($anoLetivoAtivo->data_fim, false);
            }

            return [
                'total_usuarios' => User::count(),
                'total_alunos' => User::alunos()->count(),
                'total_professores' => User::professores()->count(),
                'total_turmas' => Turma::count(),
                'ano_letivo_ativo' => $anoLetivoAtivo,
                'dias_restantes' => $diasRestantes,
                'logs_recentes' => NotaLog::with(['usuario', 'aluno', 'disciplina'])
                    ->latest('data_alteracao')
                    ->take(10)
                    ->get(),
            ];
        });

        return array_merge($stats, [
            'ranking_alunos' => $this->obterRankingAlunos($stats['ano_letivo_ativo'], $filtrosRanking),
            'filtros_ranking' => $this->filtrosRanking($stats['ano_letivo_ativo']),
            'filtros_ativos' => $filtrosRanking,
        ]);
    }

    public function secretariaStats(?AnoLetivo $anoLetivo, array $filtrosRanking = []): array
    {
        return [
            'total_alunos' => User::alunos()->ativos()->count(),
            'total_professores' => User::professores()->ativos()->count(),
            'total_turmas' => Turma::anoAtivo()->count(),
            'ano_letivo' => $anoLetivo,
            'ranking_alunos' => $this->obterRankingAlunos($anoLetivo, $filtrosRanking),
            'filtros_ranking' => $this->filtrosRanking($anoLetivo),
            'filtros_ativos' => $filtrosRanking,
            'turmas_recentes' => Turma::anoAtivo()
                ->with(['curso', 'alunos'])
                ->latest()
                ->take(5)
                ->get(),
            'logs_hoje' => NotaLog::whereDate('data_alteracao', today())->count(),
        ];
    }

    public function professorStats(User $professor, AnoLetivo $anoLetivo, array $filtrosRanking = []): array
    {
        $turmas = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with([
                'turma.curso',
                'turma.alunos' => fn($q) => $q->wherePivot('status', 'matriculado'),
                'disciplina',
            ])
            ->get()
            ->groupBy('turma_id');

        $totalAlunos = $turmas->sum(fn($atribuicoes) => $atribuicoes->first()->turma->alunos->count());

        $atribuicoes = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->get(['turma_id', 'disciplina_id']);

        $notasPendentes = 0;
        if ($atribuicoes->isNotEmpty()) {
            $notasPendentes = Nota::where('ano_letivo_id', $anoLetivo->id)
                ->where(function ($q) use ($atribuicoes) {
                    foreach ($atribuicoes as $a) {
                        $q->orWhere(fn($sub) => $sub
                            ->where('turma_id', $a->turma_id)
                            ->where('disciplina_id', $a->disciplina_id)
                        );
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('mac1')->orWhereNull('pp1')->orWhereNull('pt1')
                        ->orWhereNull('mac2')->orWhereNull('pp2')->orWhereNull('pt2')
                        ->orWhereNull('mac3')->orWhereNull('pp3')->orWhereNull('pg');
                })
                ->count();
        }

        return [
            'total_turmas' => $turmas->count(),
            'total_alunos' => $totalAlunos,
            'notas_pendentes' => $notasPendentes,
            'turmas' => $turmas,
            'ranking_alunos' => $this->obterRankingAlunos($anoLetivo, $filtrosRanking, $professor),
            'filtros_ranking' => $this->filtrosRanking($anoLetivo, $professor),
            'filtros_ativos' => $filtrosRanking,
            'ano_letivo' => $anoLetivo,
        ];
    }

    public function alunoStats(User $aluno, AnoLetivo $anoLetivo, array $filtrosRanking = []): array
    {
        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplina', 'turma'])
            ->get();

        $metasAtivas = MetaDisciplina::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->where('status', 'ativa')
            ->get()
            ->keyBy('disciplina_id');

        $disciplinasComProgresso = $this->montarDisciplinasComProgresso($notas, $metasAtivas);

        $notasComCFD = $notas->whereNotNull('cfd');
        $mediaGeral = $notasComCFD->isNotEmpty()
            ? round($notasComCFD->avg('cfd'), 2)
            : 0;

        $aprovacoes = $notasComCFD->filter(fn($n) => $n->isAprovado())->count();
        $reprovacoes = $notasComCFD->filter(fn($n) => !$n->isAprovado())->count();

        $turmaAtual = $aluno->turmas()
            ->wherePivot('status', 'matriculado')
            ->with(['curso', 'anoLetivo'])
            ->first();

        $rankingAlunos = $this->obterRankingAlunos($anoLetivo, $filtrosRanking, $aluno);
        $posicaoAluno = $rankingAlunos->search(fn($item) => (int) $item->aluno_id === (int) $aluno->id);

        return [
            'turma' => $turmaAtual,
            'notas' => $notas,
            'media_geral' => $mediaGeral,
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'total_disciplinas' => $notas->count(),
            'disciplinas_com_progresso' => $disciplinasComProgresso,
            'ranking_alunos' => $rankingAlunos,
            'filtros_ranking' => $this->filtrosRanking($anoLetivo, $aluno),
            'filtros_ativos' => $filtrosRanking,
            'posicao_turma' => $posicaoAluno === false ? null : $posicaoAluno + 1,
            'ano_letivo' => $anoLetivo,
        ];
    }

    private function filtrosRanking(?AnoLetivo $anoLetivo, ?User $user = null): array
    {
        if (!$anoLetivo) {
            return [
                'cursos' => collect(),
                'turmas' => collect(),
                'disciplinas' => collect(),
            ];
        }

        $turmasQuery = Turma::query()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with('curso:id,nome')
            ->select('id', 'nome', 'curso_id');

        $disciplinasQuery = Disciplina::query()
            ->whereHas('notas', fn($q) => $q->where('ano_letivo_id', $anoLetivo->id))
            ->select('id', 'nome');

        if ($user?->isProfessor()) {
            $atribuicoes = $user->atribuicoes()
                ->where('ano_letivo_id', $anoLetivo->id)
                ->get(['turma_id', 'disciplina_id']);

            $turmasQuery->whereIn('id', $atribuicoes->pluck('turma_id')->unique()->values());
            $disciplinasQuery->whereIn('id', $atribuicoes->pluck('disciplina_id')->unique()->values());
        }

        if ($user?->isAluno()) {
            $turmaId = $user->turmas()
                ->wherePivot('status', 'matriculado')
                ->value('turmas.id');

            $turmasQuery->where('id', $turmaId ?: 0);

            $disciplinasAluno = Nota::where('aluno_id', $user->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->pluck('disciplina_id')
                ->unique()
                ->values();

            $disciplinasQuery->whereIn('id', $disciplinasAluno);
        }

        $turmas = $turmasQuery->orderBy('nome')->get();

        return [
            'cursos' => $turmas->pluck('curso')->filter()->unique('id')->sortBy('nome')->values(),
            'turmas' => $turmas,
            'disciplinas' => $disciplinasQuery->orderBy('nome')->get(),
        ];
    }

    private function obterRankingAlunos(?AnoLetivo $anoLetivo, array $filtros = [], ?User $user = null): Collection
    {
        if (!$anoLetivo) {
            return collect();
        }

        $query = DB::table('notas as n')
            ->join('users as u', 'u.id', '=', 'n.aluno_id')
            ->join('turmas as t', 't.id', '=', 'n.turma_id')
            ->join('cursos as c', 'c.id', '=', 't.curso_id')
            ->join('disciplinas as d', 'd.id', '=', 'n.disciplina_id')
            ->where('n.ano_letivo_id', $anoLetivo->id)
            ->whereExists(function ($roleQuery) {
                $roleQuery->select(DB::raw(1))
                    ->from('roles as r')
                    ->whereColumn('r.id', 'u.role_id')
                    ->where('r.name', 'aluno');
            })
            ->whereNotNull('n.cfd');

        if (!empty($filtros['curso_id'])) {
            $query->where('t.curso_id', (int) $filtros['curso_id']);
        }

        if (!empty($filtros['turma_id'])) {
            $query->where('n.turma_id', (int) $filtros['turma_id']);
        }

        if (!empty($filtros['disciplina_id'])) {
            $query->where('n.disciplina_id', (int) $filtros['disciplina_id']);
        }

        if (!empty($filtros['q'])) {
            $query->where('u.name', 'like', '%' . trim($filtros['q']) . '%');
        }

        if ($user?->isProfessor()) {
            $atribuicoes = $user->atribuicoes()
                ->where('ano_letivo_id', $anoLetivo->id)
                ->get(['turma_id', 'disciplina_id']);

            if ($atribuicoes->isEmpty()) {
                return collect();
            }

            $query->where(function ($scope) use ($atribuicoes) {
                foreach ($atribuicoes as $atribuicao) {
                    $scope->orWhere(function ($pair) use ($atribuicao) {
                        $pair->where('n.turma_id', $atribuicao->turma_id)
                            ->where('n.disciplina_id', $atribuicao->disciplina_id);
                    });
                }
            });
        }

        if ($user?->isAluno()) {
            $turmaId = $user->turmas()
                ->wherePivot('status', 'matriculado')
                ->value('turmas.id');

            $query->where('n.turma_id', $turmaId ?: 0);
        }

        return $query
            ->select(
                'u.id as aluno_id',
                'u.name as aluno_nome',
                't.id as turma_id',
                't.nome as turma_nome',
                'c.nome as curso_nome',
                DB::raw('ROUND(AVG(n.cfd), 2) as media_geral'),
                DB::raw('COUNT(n.id) as total_notas')
            )
            ->groupBy('u.id', 'u.name', 't.id', 't.nome', 'c.nome')
            ->orderByDesc('media_geral')
            ->orderBy('u.name')
            ->limit(20)
            ->get()
            ->values();
    }

    private function montarDisciplinasComProgresso(Collection $notas, Collection $metasAtivas): Collection
    {
        return $notas->map(function (Nota $nota) use ($metasAtivas) {
            $meta = $metasAtivas->get($nota->disciplina_id);
            $notaAtual = $nota->cfd ?? $nota->mt3 ?? $nota->mt2 ?? $nota->mt1;

            if (!$meta || $notaAtual === null || (float) $meta->meta_nota <= 0) {
                return [
                    'nota' => $nota,
                    'meta' => $meta,
                    'nota_atual' => $notaAtual,
                    'progresso' => 0,
                    'diferenca' => null,
                ];
            }

            $progresso = min(100, round(((float) $notaAtual / (float) $meta->meta_nota) * 100));
            $diferenca = round((float) $meta->meta_nota - (float) $notaAtual, 2);

            return [
                'nota' => $nota,
                'meta' => $meta,
                'nota_atual' => $notaAtual,
                'progresso' => $progresso,
                'diferenca' => $diferenca,
            ];
        });
    }
}

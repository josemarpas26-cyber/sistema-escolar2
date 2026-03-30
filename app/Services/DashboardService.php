<?php

namespace App\Services;

use App\Models\AnoLetivo;
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

    public function adminStats(): array
    {
        return Cache::remember('dashboard:admin:stats', self::CACHE_TTL_SECONDS, function () {
            $anoLetivoAtivo = AnoLetivo::ativo()->first();
            $diasRestantes = null;

            if ($anoLetivoAtivo && !$anoLetivoAtivo->encerrado) {
                $diasRestantes = (int) now()->diffInDays($anoLetivoAtivo->data_fim, false);
            }

            $rankingProfessores = $this->obterRankingProfessores($anoLetivoAtivo);

            return [
                'total_usuarios' => User::count(),
                'total_alunos' => User::alunos()->count(),
                'total_professores' => User::professores()->count(),
                'total_turmas' => Turma::count(),
                'ano_letivo_ativo' => $anoLetivoAtivo,
                'dias_restantes' => $diasRestantes,
                'ranking_professores' => $rankingProfessores,
                'logs_recentes' => NotaLog::with(['usuario', 'aluno', 'disciplina'])
                    ->latest('data_alteracao')
                    ->take(10)
                    ->get(),
            ];
        });
    }

    public function secretariaStats(?AnoLetivo $anoLetivo): array
    {
        return [
            'total_alunos' => User::alunos()->ativos()->count(),
            'total_professores' => User::professores()->ativos()->count(),
            'total_turmas' => Turma::anoAtivo()->count(),
            'ano_letivo' => $anoLetivo,
            'ranking_professores' => $this->obterRankingProfessores($anoLetivo),
            'turmas_recentes' => Turma::anoAtivo()
                ->with(['curso', 'alunos'])
                ->latest()
                ->take(5)
                ->get(),
            'logs_hoje' => NotaLog::whereDate('data_alteracao', today())->count(),
        ];
    }

    public function professorStats(User $professor, AnoLetivo $anoLetivo): array
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
            'ranking_turmas' => $this->obterRankingTurmasProfessor($professor, $anoLetivo),
            'ano_letivo' => $anoLetivo,
        ];
    }

    public function alunoStats(User $aluno, AnoLetivo $anoLetivo): array
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

        $rankingTurma = $this->obterRankingAlunosTurma($turmaAtual?->id, $anoLetivo->id);
        $posicaoAluno = $rankingTurma->search(fn($item) => (int) $item->aluno_id === (int) $aluno->id);

        return [
            'turma' => $turmaAtual,
            'notas' => $notas,
            'media_geral' => $mediaGeral,
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'total_disciplinas' => $notas->count(),
            'disciplinas_com_progresso' => $disciplinasComProgresso,
            'ranking_turma' => $rankingTurma->take(5),
            'posicao_turma' => $posicaoAluno === false ? null : $posicaoAluno + 1,
            'ano_letivo' => $anoLetivo,
        ];
    }

    private function obterRankingProfessores(?AnoLetivo $anoLetivo, int $limite = 5): Collection
    {
        if (!$anoLetivo) {
            return collect();
        }

        return DB::table('notas as n')
            ->join('professor_turma_disciplina as ptd', function ($join) {
                $join->on('ptd.turma_id', '=', 'n.turma_id')
                    ->on('ptd.disciplina_id', '=', 'n.disciplina_id')
                    ->on('ptd.ano_letivo_id', '=', 'n.ano_letivo_id');
            })
            ->join('users as u', 'u.id', '=', 'ptd.professor_id')
            ->where('n.ano_letivo_id', $anoLetivo->id)
            ->whereNotNull('n.cfd')
            ->select(
                'u.id',
                'u.name',
                DB::raw('ROUND(AVG(n.cfd), 2) as media_geral'),
                DB::raw('COUNT(DISTINCT CONCAT(n.turma_id, "-", n.disciplina_id)) as total_pautas')
            )
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('media_geral')
            ->limit($limite)
            ->get();
    }

    private function obterRankingTurmasProfessor(User $professor, AnoLetivo $anoLetivo, int $limite = 5): Collection
    {
        return DB::table('notas as n')
            ->join('turmas as t', 't.id', '=', 'n.turma_id')
            ->join('professor_turma_disciplina as ptd', function ($join) {
                $join->on('ptd.turma_id', '=', 'n.turma_id')
                    ->on('ptd.disciplina_id', '=', 'n.disciplina_id')
                    ->on('ptd.ano_letivo_id', '=', 'n.ano_letivo_id');
            })
            ->where('ptd.professor_id', $professor->id)
            ->where('n.ano_letivo_id', $anoLetivo->id)
            ->whereNotNull('n.cfd')
            ->select(
                't.id',
                't.nome',
                't.classe',
                DB::raw('ROUND(AVG(n.cfd), 2) as media_geral'),
                DB::raw('COUNT(DISTINCT n.aluno_id) as total_alunos')
            )
            ->groupBy('t.id', 't.nome', 't.classe')
            ->orderByDesc('media_geral')
            ->limit($limite)
            ->get();
    }

    private function obterRankingAlunosTurma(?int $turmaId, int $anoLetivoId): Collection
    {
        if (!$turmaId) {
            return collect();
        }

        return DB::table('notas as n')
            ->join('users as u', 'u.id', '=', 'n.aluno_id')
            ->where('n.turma_id', $turmaId)
            ->where('n.ano_letivo_id', $anoLetivoId)
            ->whereNotNull('n.cfd')
            ->select(
                'u.id as aluno_id',
                'u.name as aluno_nome',
                DB::raw('ROUND(AVG(n.cfd), 2) as media_geral'),
                DB::raw('COUNT(n.id) as total_notas')
            )
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('media_geral')
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

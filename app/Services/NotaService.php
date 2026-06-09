<?php

namespace App\Services;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\HistoricoAcademico;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotaService
{
    /**
     * Relações que o model Nota exige para recalcular().
     * Centralizado aqui para não divergir entre chamadas.
     */
    private const RELACOES_RECALCULO = ['aluno.turmas', 'anoLetivo.configuracaoAvaliacao.provas', 'turma.curso', 'disciplina', 'turma'];

    public function criarNotasParaTurma(Turma $turma, Disciplina $disciplina): int
    {
        $alunos = $turma->alunos()->wherePivotIn('status', ['matriculado', 'recurso'])->get();
        $contador = 0;

        foreach ($alunos as $aluno) {
            $nota = Nota::firstOrCreate(
                [
                    'aluno_id'      => $aluno->id,
                    'turma_id'      => $turma->id,
                    'disciplina_id' => $disciplina->id,
                    'ano_letivo_id' => $turma->ano_letivo_id,
                ],
                ['status' => 'em_lancamento']
            );

            if ($nota->wasRecentlyCreated) {
                $contador++;
            }
        }

        return $contador;
    }

    public function importarCAAnterior(User $aluno, Disciplina $disciplina, string $classeBuscada): ?float
    {
        if ((int) $classeBuscada < 10 || (int) $classeBuscada > 12) {
            return null;
        }

        $notaAnterior = Nota::where('aluno_id', $aluno->id)
            ->where('disciplina_id', $disciplina->id)
            ->whereHas('turma', fn ($query) => $query->where('classe', $classeBuscada))
            ->whereNotNull('ca')
            ->orderByDesc('ano_letivo_id')
            ->orderByDesc('id')
            ->first();

        if ($notaAnterior?->ca !== null) {
            return (float) $notaAnterior->ca;
        }

        $historico = HistoricoAcademico::query()
            ->where('aluno_id', $aluno->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('classe', (string) $classeBuscada)
            ->whereNotNull('classificacao_final')
            ->orderByDesc('data_conclusao')
            ->orderByDesc('ano_letivo_id')
            ->orderByDesc('id')
            ->first();

        return $historico?->classificacao_final !== null
            ? (float) $historico->classificacao_final
            : null;
    }

    public function recalcularNota(Nota $nota, bool $preencherCAsAnteriores = true): void
    {
        $nota->loadMissing(self::RELACOES_RECALCULO);

        if ($preencherCAsAnteriores) {
            $this->preencherCAsAnteriores($nota);
        }

        $nota->recalcular();
    }

    public function importarCAsParaTurma(Turma $turma, Disciplina $disciplina, bool $permitirFinalizado = false): array
    {
        if ($turma->classe == '10') {
            return [
                'sucesso'    => 0,
                'erro'       => 0,
                'bloqueadas' => 0,
                'mensagem'   => 'Nao ha CAs para importar na 10ª classe.',
            ];
        }

        $alunos = $turma->alunos()->wherePivotIn('status', ['matriculado', 'recurso'])->get();
        $sucesso = 0;
        $erro = 0;
        $bloqueadas = 0;

        foreach ($alunos as $aluno) {
            $nota = Nota::where('aluno_id', $aluno->id)
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $turma->ano_letivo_id)
                ->with(self::RELACOES_RECALCULO)
                ->first();

            if (!$nota) {
                $erro++;
                continue;
            }

            if ($nota->status === 'finalizado' && !$permitirFinalizado) {
                $bloqueadas++;
                continue;
            }

            if (!$this->preencherCAsAnteriores($nota)) {
                $erro++;
                continue;
            }

            $this->recalcularNota($nota, false);
            $nota->save();
            $sucesso++;
        }

        return [
            'sucesso'    => $sucesso,
            'erro'       => $erro,
            'bloqueadas' => $bloqueadas,
            'mensagem'   => "{$sucesso} CAs importados, {$erro} com dados insuficientes e {$bloqueadas} bloqueados por finalizacao.",
        ];
    }

    public function calcularMediaTurma(Turma $turma, Disciplina $disciplina): array
    {
        $notas = Nota::where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->whereNotNull('cfd')
            ->get();

        if ($notas->isEmpty()) {
            return [
                'media'      => null,
                'aprovados'  => 0,
                'reprovados' => 0,
                'total'      => 0,
            ];
        }

        $aprovados = $notas->filter(fn ($nota) => $nota->isAprovado())->count();

        return [
            'media'           => round($notas->avg('cfd_efetiva'), 2),
            'aprovados'       => $aprovados,
            'reprovados'      => $notas->count() - $aprovados,
            'total'           => $notas->count(),
            'taxa_aprovacao'  => round(($aprovados / $notas->count()) * 100, 2),
        ];
    }

    public function verificarCompletudeTrimestre(
        Turma $turma,
        Disciplina $disciplina,
        int $trimestre,
        ?int $alunoId = null
    ): array {
        $notas = $this->queryNotasDaPauta($turma, $disciplina, $alunoId)->get();

        return $this->resumoCompletude($notas, function (Nota $nota) use ($trimestre) {
            if (! $nota->trimestreEstaDisponivel($trimestre)) {
                return true;
            }

            return match ($trimestre) {
                1 => $nota->mac1 !== null && $nota->pp1 !== null && $nota->pt1 !== null && $nota->mt1 !== null,
                2 => $nota->mac2 !== null && $nota->pp2 !== null && $nota->pt2 !== null && $nota->mt2 !== null,
                3 => $nota->mac3 !== null
                    && $nota->pp3 !== null
                    && match ((int) $nota->turma->classe) {
                        10, 11 => $nota->pt3 !== null,
                        12 => $nota->pg !== null,
                        default => true,
                    }
                    && $nota->mt3 !== null
                    && $nota->cf !== null
                    && $nota->ca !== null,
                default => false,
            };
        });
    }

    public function verificarCompletudeFinalizacao(
        Turma $turma,
        Disciplina $disciplina,
        ?int $alunoId = null
    ): array {
        $notas = $this->queryNotasDaPauta($turma, $disciplina, $alunoId)->get();

        return $this->resumoCompletude($notas, function (Nota $nota) {
            $primeiroTrimestreCompleto = ! $nota->trimestreEstaDisponivel(1)
                || $nota->mt1 !== null;

            $segundoTrimestreCompleto = ! $nota->trimestreEstaDisponivel(2)
                || ($nota->mt2 !== null && $nota->mft2 !== null);

            return $primeiroTrimestreCompleto
                && $segundoTrimestreCompleto
                && $nota->mt3 !== null
                && $nota->cf !== null
                && $nota->ca !== null
                && $nota->cfd !== null;
        });
    }

    public function estatisticasAluno(User $aluno, ?AnoLetivo $anoLetivo = null): array
    {
        if (!$anoLetivo) {
            $anoLetivo = AnoLetivo::ativo()->first();
        }

        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with('disciplina')
            ->get();

        $notasComCFD = $notas->filter(fn (Nota $nota) => $nota->cfd_efetiva !== null);

        if ($notasComCFD->isEmpty()) {
            return [
                'media_geral'       => null,
                'aprovacoes'        => 0,
                'reprovacoes'       => 0,
                'total_disciplinas' => $notas->count(),
                'melhor_nota'       => null,
                'pior_nota'         => null,
            ];
        }

        $aprovados  = $notasComCFD->filter(fn ($nota) => $nota->isAprovado());
        $melhorNota = $notasComCFD->sortByDesc('cfd_efetiva')->first();
        $piorNota   = $notasComCFD->sortBy('cfd_efetiva')->first();

        return [
            'media_geral'       => round($notasComCFD->avg('cfd_efetiva'), 2),
            'aprovacoes'        => $aprovados->count(),
            'reprovacoes'       => $notasComCFD->count() - $aprovados->count(),
            'total_disciplinas' => $notas->count(),
            'melhor_nota'       => [
                'disciplina' => $melhorNota->disciplina->nome,
                'nota'       => $melhorNota->cfd_efetiva,
            ],
            'pior_nota' => [
                'disciplina' => $piorNota->disciplina->nome,
                'nota'       => $piorNota->cfd_efetiva,
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Privados                                                           */
    /* ------------------------------------------------------------------ */

    private function queryNotasDaPauta(Turma $turma, Disciplina $disciplina, ?int $alunoId = null): Builder
    {
        return Nota::query()
            ->where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->when($alunoId, fn (Builder $query, $id) => $query->where('aluno_id', $id));
    }

    private function preencherCAsAnteriores(Nota $nota): bool
    {
        $classeAtual = (int) $nota->turma->classe;

        if ($classeAtual >= 11 && $nota->disciplina->leciona_10 && $nota->ca_10 === null) {
            $nota->ca_10 = $this->importarCAAnterior($nota->aluno, $nota->disciplina, '10');
        }

        if ($classeAtual >= 12 && $nota->disciplina->leciona_11 && $nota->ca_11 === null) {
            $nota->ca_11 = $this->importarCAAnterior($nota->aluno, $nota->disciplina, '11');
        }

        if ($classeAtual >= 13 && $nota->disciplina->leciona_12 && $nota->ca_12 === null) {
            $nota->ca_12 = $this->importarCAAnterior($nota->aluno, $nota->disciplina, '12');
        }

        if ($classeAtual >= 11 && $nota->disciplina->leciona_10 && $nota->ca_10 === null) {
            return false;
        }

        if ($classeAtual >= 12 && $nota->disciplina->leciona_11 && $nota->ca_11 === null) {
            return false;
        }

        if ($classeAtual >= 13 && $nota->disciplina->leciona_12 && $nota->ca_12 === null) {
            return false;
        }

        return true;
    }

    private function resumoCompletude(Collection $notas, callable $regra): array
    {
        $total     = $notas->count();
        $completas = $notas->filter($regra)->count();

        return [
            'total'       => $total,
            'completas'   => $completas,
            'incompletas' => $total - $completas,
            'percentual'  => $total > 0 ? round(($completas / $total) * 100, 2) : 0,
        ];
    }
}

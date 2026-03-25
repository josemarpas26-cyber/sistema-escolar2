<?php

namespace App\Services;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotaService
{
    public function criarNotasParaTurma(Turma $turma, Disciplina $disciplina): int
    {
        $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();
        $contador = 0;

        foreach ($alunos as $aluno) {
            $nota = Nota::firstOrCreate(
                [
                    'aluno_id' => $aluno->id,
                    'turma_id' => $turma->id,
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
        if ((int) $classeBuscada < 10 || (int) $classeBuscada > 11) {
            return null;
        }

        $notaAnterior = Nota::where('aluno_id', $aluno->id)
            ->where('disciplina_id', $disciplina->id)
            ->whereHas('turma', fn ($query) => $query->where('classe', $classeBuscada))
            ->whereNotNull('ca')
            ->first();

        return $notaAnterior?->ca;
    }

    public function importarCAsParaTurma(Turma $turma, Disciplina $disciplina, bool $permitirFinalizado = false): array
    {
        if ($turma->classe == '10') {
            return [
                'sucesso' => 0,
                'erro' => 0,
                'bloqueadas' => 0,
                'mensagem' => 'Nao ha CAs para importar na 10a classe.',
            ];
        }

        $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();
        $sucesso = 0;
        $erro = 0;
        $bloqueadas = 0;

        foreach ($alunos as $aluno) {
            $nota = Nota::where('aluno_id', $aluno->id)
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $turma->ano_letivo_id)
                ->first();

            if (!$nota) {
                $erro++;
                continue;
            }

            if ($nota->status === 'finalizado' && !$permitirFinalizado) {
                $bloqueadas++;
                continue;
            }

            if ($turma->classe == '11') {
                $ca10 = $this->importarCAAnterior($aluno, $disciplina, '10');

                if ($ca10 === null) {
                    $erro++;
                    continue;
                }

                $nota->update(['ca_10' => $ca10]);
                $nota->recalcular();
                $nota->save();
                $sucesso++;
                continue;
            }

            if ($turma->classe == '12') {
                $ca10 = $this->importarCAAnterior($aluno, $disciplina, '10');
                $ca11 = $this->importarCAAnterior($aluno, $disciplina, '11');

                if ($ca10 === null || $ca11 === null) {
                    $erro++;
                    continue;
                }

                $nota->update([
                    'ca_10' => $ca10,
                    'ca_11' => $ca11,
                ]);
                $nota->recalcular();
                $nota->save();
                $sucesso++;
                continue;
            }

            $erro++;
        }

        return [
            'sucesso' => $sucesso,
            'erro' => $erro,
            'bloqueadas' => $bloqueadas,
            'mensagem' => "{$sucesso} CAs importados, {$erro} com dados insuficientes e {$bloqueadas} bloqueados por finalizacao.",
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
                'media' => null,
                'aprovados' => 0,
                'reprovados' => 0,
                'total' => 0,
            ];
        }

        $aprovados = $notas->filter(fn ($nota) => $nota->isAprovado())->count();

        return [
            'media' => round($notas->avg('cfd'), 2),
            'aprovados' => $aprovados,
            'reprovados' => $notas->count() - $aprovados,
            'total' => $notas->count(),
            'taxa_aprovacao' => round(($aprovados / $notas->count()) * 100, 2),
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
            return match ($trimestre) {
                1 => $nota->mac1 !== null && $nota->pp1 !== null && $nota->pt1 !== null && $nota->mt1 !== null,
                2 => $nota->mac2 !== null && $nota->pp2 !== null && $nota->pt2 !== null && $nota->mt2 !== null,
                3 => $nota->mac3 !== null
                    && $nota->pp3 !== null
                    && $nota->pg !== null
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
            return $nota->mt1 !== null
                && $nota->mt2 !== null
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

        $notasComCFD = $notas->whereNotNull('cfd');

        if ($notasComCFD->isEmpty()) {
            return [
                'media_geral' => null,
                'aprovacoes' => 0,
                'reprovacoes' => 0,
                'total_disciplinas' => $notas->count(),
                'melhor_nota' => null,
                'pior_nota' => null,
            ];
        }

        $aprovados = $notasComCFD->filter(fn ($nota) => $nota->isAprovado());
        $melhorNota = $notasComCFD->sortByDesc('cfd')->first();
        $piorNota = $notasComCFD->sortBy('cfd')->first();

        return [
            'media_geral' => round($notasComCFD->avg('cfd'), 2),
            'aprovacoes' => $aprovados->count(),
            'reprovacoes' => $notasComCFD->count() - $aprovados->count(),
            'total_disciplinas' => $notas->count(),
            'melhor_nota' => [
                'disciplina' => $melhorNota->disciplina->nome,
                'nota' => $melhorNota->cfd,
            ],
            'pior_nota' => [
                'disciplina' => $piorNota->disciplina->nome,
                'nota' => $piorNota->cfd,
            ],
        ];
    }

    private function queryNotasDaPauta(Turma $turma, Disciplina $disciplina, ?int $alunoId = null): Builder
    {
        return Nota::query()
            ->where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->when($alunoId, fn (Builder $query, $id) => $query->where('aluno_id', $id));
    }

    private function resumoCompletude(Collection $notas, callable $regra): array
    {
        $total = $notas->count();
        $completas = $notas->filter($regra)->count();

        return [
            'total' => $total,
            'completas' => $completas,
            'incompletas' => $total - $completas,
            'percentual' => $total > 0 ? round(($completas / $total) * 100, 2) : 0,
        ];
    }
}

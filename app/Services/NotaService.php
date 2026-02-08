<?php

namespace App\Services;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\Disciplina;
use App\Models\AnoLetivo;
use App\Models\User;

class NotaService
{
    /**
     * Criar notas para todos os alunos de uma turma/disciplina
     */
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

    /**
     * Importar CA de classe anterior para um aluno
     */
    public function importarCAAnterior(User $aluno, Disciplina $disciplina, string $classeAtual): ?float
    {
        $classeAnterior = (string) ((int) $classeAtual - 1);

        if ($classeAnterior < 10) {
            return null;
        }

        $notaAnterior = Nota::where('aluno_id', $aluno->id)
            ->where('disciplina_id', $disciplina->id)
            ->whereHas('turma', fn($q) => $q->where('classe', $classeAnterior))
            ->whereNotNull('ca')
            ->first();

        return $notaAnterior?->ca;
    }

    /**
     * Importar CAs para todos os alunos de uma turma
     */
    public function importarCAsParaTurma(Turma $turma, Disciplina $disciplina): array
    {
        if ($turma->classe == '10') {
            return ['sucesso' => 0, 'erro' => 0, 'mensagem' => 'Não há CAs para importar na 10ª classe'];
        }

        $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();
        $sucesso = 0;
        $erro = 0;

        foreach ($alunos as $aluno) {
            $ca = $this->importarCAAnterior($aluno, $disciplina, $turma->classe);

            if ($ca === null) {
                $erro++;
                continue;
            }

            $nota = Nota::where('aluno_id', $aluno->id)
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $turma->ano_letivo_id)
                ->first();

            if (!$nota) {
                $erro++;
                continue;
            }

            if ($turma->classe == '11') {
                $nota->update(['ca_10' => $ca]);
            } elseif ($turma->classe == '12') {
                // Buscar também CA da 10ª
                $ca10 = $this->importarCAAnterior($aluno, $disciplina, '11');
                $nota->update([
                    'ca_10' => $ca10,
                    'ca_11' => $ca,
                ]);
            }

            $nota->recalcular();
            $nota->save();
            $sucesso++;
        }

        return [
            'sucesso' => $sucesso,
            'erro' => $erro,
            'mensagem' => "{$sucesso} CAs importados com sucesso, {$erro} erros."
        ];
    }

    /**
     * Calcular média da turma em uma disciplina
     */
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

        $aprovados = $notas->filter(fn($n) => $n->isAprovado())->count();
        
        return [
            'media' => round($notas->avg('cfd'), 2),
            'aprovados' => $aprovados,
            'reprovados' => $notas->count() - $aprovados,
            'total' => $notas->count(),
            'taxa_aprovacao' => round(($aprovados / $notas->count()) * 100, 2),
        ];
    }

    /**
     * Verificar se todas as notas de um trimestre foram lançadas
     */
    public function verificarCompletudeTrimestr(Turma $turma, Disciplina $disciplina, int $trimestre): array
    {
        $notas = Nota::where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->get();

        $total = $notas->count();
        $completas = 0;

        foreach ($notas as $nota) {
            $completo = match($trimestre) {
                1 => $nota->mac1 !== null && $nota->pp1 !== null && $nota->pt1 !== null,
                2 => $nota->mac2 !== null && $nota->pp2 !== null && $nota->pt2 !== null,
                3 => $nota->mac3 !== null && $nota->pp3 !== null && $nota->pg !== null,
                default => false,
            };

            if ($completo) {
                $completas++;
            }
        }

        return [
            'total' => $total,
            'completas' => $completas,
            'incompletas' => $total - $completas,
            'percentual' => $total > 0 ? round(($completas / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Gerar estatísticas gerais de um aluno
     */
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

        $aprovados = $notasComCFD->filter(fn($n) => $n->isAprovado());
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
}
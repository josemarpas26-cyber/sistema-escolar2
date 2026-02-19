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

    /**
     * Importar CA de uma classe específica para um aluno.
     *
     * BUG CORRIGIDO: O parâmetro anterior era "classeAtual" e subtraía 1
     * internamente, tornando a chamada semanticamente confusa e propensa a
     * erros. Agora recebe directamente a classe cujo CA se quer buscar.
     *
     * @param  string $classeBuscada  A classe cujo CA se pretende importar (ex: '10', '11')
     */
    public function importarCAAnterior(User $aluno, Disciplina $disciplina, string $classeBuscada): ?float
    {
        // BUG CORRIGIDO: antes comparava classeAnterior < 10 após subtrair 1,
        // agora validamos directamente a classe pedida.
        if ((int) $classeBuscada < 10 || (int) $classeBuscada > 11) {
            return null;
        }

        $notaAnterior = Nota::where('aluno_id',      $aluno->id)
            ->where('disciplina_id', $disciplina->id)
            ->whereHas('turma', fn($q) => $q->where('classe', $classeBuscada))
            ->whereNotNull('ca')
            ->first();

        return $notaAnterior?->ca;
    }

    /**
     * Importar CAs para todos os alunos de uma turma.
     *
     * Regra:
     *   11ª classe → preenche ca_10 (CA da 10ª)
     *   12ª classe → preenche ca_10 (CA da 10ª) e ca_11 (CA da 11ª)
     *
     * BUG CORRIGIDO: as chamadas a importarCAAnterior() passavam a classeAtual
     * em vez da classe cujo CA se queria buscar, tornando a lógica invertida e
     * difícil de rastrear.
     */
    public function importarCAsParaTurma(Turma $turma, Disciplina $disciplina, bool $permitirFinalizado = false): array
    {
        if ($turma->classe == '10') {
            return [
                'sucesso'   => 0,
                'erro'      => 0,
                'bloqueadas'=> 0,
                'mensagem'  => 'Não há CAs para importar na 10ª classe.',
            ];
        }

        $alunos     = $turma->alunos()->wherePivot('status', 'matriculado')->get();
        $sucesso    = 0;
        $erro       = 0;
        $bloqueadas = 0;

        foreach ($alunos as $aluno) {
            $nota = Nota::where('aluno_id',      $aluno->id)
                ->where('turma_id',      $turma->id)
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

            // --- 11ª classe: importar apenas CA da 10ª ---
            if ($turma->classe == '11') {
                // BUG CORRIGIDO: antes passava '11' (classeAtual), que internamente
                // subtraía 1 e buscava classe='10' — funcionava por acidente mas era
                // semanticamente errado. Agora passa '10' directamente.
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

            // --- 12ª classe: importar CA da 10ª e CA da 11ª ---
            if ($turma->classe == '12') {
                // BUG CORRIGIDO: antes passava '11' para obter ca_10 e '12' para
                // obter ca_11, o que só funcionava por coincidência da subtracção
                // interna. Agora as classes pedidas são explícitas e correctas.
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
            'sucesso'   => $sucesso,
            'erro'      => $erro,
            'bloqueadas'=> $bloqueadas,
            'mensagem'  => "{$sucesso} CAs importados, {$erro} com dados insuficientes e {$bloqueadas} bloqueados por finalização.",
        ];
    }

    /**
     * Calcular média da turma em uma disciplina
     */
    public function calcularMediaTurma(Turma $turma, Disciplina $disciplina): array
    {
        $notas = Nota::where('turma_id',     $turma->id)
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

        $aprovados = $notas->filter(fn($n) => $n->isAprovado())->count();

        return [
            'media'           => round($notas->avg('cfd'), 2),
            'aprovados'       => $aprovados,
            'reprovados'      => $notas->count() - $aprovados,
            'total'           => $notas->count(),
            'taxa_aprovacao'  => round(($aprovados / $notas->count()) * 100, 2),
        ];
    }

    /**
     * Verificar se todas as notas de um trimestre foram lançadas.
     *
     * BUG CORRIGIDO 1: nome do método tinha typo ("Trimestr" em vez de "Trimestre").
     * BUG CORRIGIDO 2: o 3º trimestre verificava `pg` (Prova Global) como parte
     *   do lançamento trimestral, mas `pg` é um campo opcional lançado separadamente
     *   após o 3º trimestre. A verificação correcta é mac3 + pp3 + mt3 (calculado).
     */
    public function verificarCompletudeTrimestre(Turma $turma, Disciplina $disciplina, int $trimestre): array
    {
        $notas = Nota::where('turma_id',     $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->get();

        $total    = $notas->count();
        $completas = 0;

        foreach ($notas as $nota) {
            $completo = match ($trimestre) {
                1 => $nota->mac1 !== null && $nota->pp1 !== null && $nota->pt1 !== null,
                2 => $nota->mac2 !== null && $nota->pp2 !== null && $nota->pt2 !== null,
                // BUG CORRIGIDO: era `pg` — agora verifica mt3 (nota calculada do 3º trim)
                3 => $nota->mac3 !== null && $nota->pp3 !== null && $nota->mt3 !== null,
                default => false,
            };

            if ($completo) {
                $completas++;
            }
        }

        return [
            'total'      => $total,
            'completas'  => $completas,
            'incompletas'=> $total - $completas,
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

        $notas = Nota::where('aluno_id',     $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with('disciplina')
            ->get();

        $notasComCFD = $notas->whereNotNull('cfd');

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

        $aprovados  = $notasComCFD->filter(fn($n) => $n->isAprovado());
        $melhorNota = $notasComCFD->sortByDesc('cfd')->first();
        $piorNota   = $notasComCFD->sortBy('cfd')->first();

        return [
            'media_geral'       => round($notasComCFD->avg('cfd'), 2),
            'aprovacoes'        => $aprovados->count(),
            'reprovacoes'       => $notasComCFD->count() - $aprovados->count(),
            'total_disciplinas' => $notas->count(),
            'melhor_nota'       => [
                'disciplina' => $melhorNota->disciplina->nome,
                'nota'       => $melhorNota->cfd,
            ],
            'pior_nota'         => [
                'disciplina' => $piorNota->disciplina->nome,
                'nota'       => $piorNota->cfd,
            ],
        ];
    }
}
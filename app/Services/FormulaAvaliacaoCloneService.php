<?php

namespace App\Services;

use App\Models\AnoLetivo;
use App\Models\FormulaAvaliacao;

class FormulaAvaliacaoCloneService
{
    public function clonar(AnoLetivo $origem, AnoLetivo $destino, ?int $autorId = null): void
    {
        $formulas = FormulaAvaliacao::with(['versoes', 'avaliacoes'])
            ->where('ano_letivo_id', $origem->id)
            ->get();

        foreach ($formulas as $formulaOrigem) {
            $nova = FormulaAvaliacao::create([
                'ano_letivo_id' => $destino->id,
                'nome' => $formulaOrigem->nome,
                'componentes' => $formulaOrigem->componentes,
                'regras' => $formulaOrigem->regras,
                'ativa' => $formulaOrigem->ativa,
            ]);

            $nova->versoes()->create([
                'versao' => 1,
                'componentes' => $formulaOrigem->componentes,
                'regras' => $formulaOrigem->regras,
                'motivo' => "Clonada do ano letivo {$origem->nome}",
                'criado_por' => $autorId,
            ]);

            foreach ($formulaOrigem->avaliacoes as $avaliacao) {
                $nova->avaliacoes()->create([
                    'ano_letivo_id' => $destino->id,
                    'disciplina_id' => $avaliacao->disciplina_id,
                    'nome' => $avaliacao->nome,
                    'tipo' => $avaliacao->tipo,
                    'peso' => $avaliacao->peso,
                    'excecoes' => $avaliacao->excecoes,
                ]);
            }
        }
    }
}

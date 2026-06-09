<?php

namespace Tests\Unit;

use App\Models\ConfiguracaoAvaliacao;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\ProvaAvaliacao;
use App\Models\Turma;
use Tests\TestCase;

class NotaRecalculoDinamicoTest extends TestCase
{
    public function test_recalcula_com_modelo_padrao_ipikk(): void
    {
        $nota = new Nota([
            'ano_letivo_id' => 1,
            'mac1' => 12,
            'pp1' => 9,
            'pt1' => 15,
            'mac2' => 10,
            'pp2' => 14,
            'pt2' => 12,
            'mac3' => 13,
            'pp3' => 11,
            'pt3' => 10,
            'pg' => 10,
        ]);

        $nota->setRelation('turma', new Turma(['classe' => 10]));
        $nota->setRelation('disciplina', new Disciplina(['leciona_10' => true, 'leciona_11' => false, 'leciona_12' => false]));

        $config = $this->configuracao([
            1 => [
                ['codigo' => 'mac1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt1', 'peso' => 1, 'ativo' => true],
            ],
            2 => [
                ['codigo' => 'mac2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt2', 'peso' => 1, 'ativo' => true],
            ],
            3 => [
                ['codigo' => 'mac3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt3', 'peso' => 1, 'ativo' => true],
            ],
        ], 40);

        $nota->recalcular($config);

        $this->assertEquals(12.00, (float) $nota->mt1);
        $this->assertEquals(12.00, (float) $nota->mt2);
        $this->assertEquals(12.00, (float) $nota->mft2);
        $this->assertEquals(12.00, (float) $nota->mt3);
        $this->assertEquals(12.00, (float) $nota->cf);
        $this->assertEquals(12.00, (float) $nota->ca);
        $this->assertNull($nota->pg);
    }

    public function test_recalcula_com_pesos_customizados_e_prova_inativa(): void
    {
        $nota = new Nota([
            'ano_letivo_id' => 1,
            'mac1' => 10,
            'pp1' => 20,
            'pt1' => 0,
            'mac2' => 12,
            'pp2' => 18,
            'pt2' => 16,
            'mac3' => 15,
            'pp3' => 17,
            'pt3' => 14,
            'pg' => 14,
        ]);

        $nota->setRelation('turma', new Turma(['classe' => 10]));
        $nota->setRelation('disciplina', new Disciplina(['leciona_10' => true, 'leciona_11' => false, 'leciona_12' => false]));

        $config = $this->configuracao([
            1 => [
                ['codigo' => 'mac1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp1', 'peso' => 3, 'ativo' => true],
                ['codigo' => 'pt1', 'peso' => 1, 'ativo' => false],
            ],
            2 => [
                ['codigo' => 'mac2', 'peso' => 2, 'ativo' => true],
                ['codigo' => 'pp2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt2', 'peso' => 1, 'ativo' => true],
            ],
            3 => [
                ['codigo' => 'mac3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt3', 'peso' => 1, 'ativo' => true],
            ],
        ], 30);

        $nota->recalcular($config);

        $this->assertEquals(17.50, (float) $nota->mt1);
        $this->assertEquals(14.50, (float) $nota->mt2);
        $this->assertEquals(16.00, (float) $nota->mft2);
        $this->assertEquals(16.00, (float) $nota->mt3);
        $this->assertEquals(16.00, (float) $nota->cf);
        $this->assertEquals(16.00, (float) $nota->ca);
        $this->assertNull($nota->pg);
    }

    public function test_recalcula_decima_segunda_com_prova_global(): void
    {
        $nota = new Nota([
            'ano_letivo_id' => 1,
            'mac1' => 12,
            'pp1' => 12,
            'pt1' => 12,
            'mac2' => 12,
            'pp2' => 12,
            'pt2' => 12,
            'mac3' => 12,
            'pp3' => 12,
            'pt3' => 18,
            'pg' => 10,
            'ca_10' => 12,
            'ca_11' => 12,
        ]);

        $nota->setRelation('turma', new Turma(['classe' => 12]));
        $nota->setRelation('disciplina', new Disciplina(['leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true]));

        $config = $this->configuracao([
            1 => [
                ['codigo' => 'mac1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt1', 'peso' => 1, 'ativo' => true],
            ],
            2 => [
                ['codigo' => 'mac2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt2', 'peso' => 1, 'ativo' => true],
            ],
            3 => [
                ['codigo' => 'mac3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt3', 'peso' => 1, 'ativo' => true],
            ],
        ], 40);

        $nota->recalcular($config);

        $this->assertEquals(12.00, (float) $nota->cf);
        $this->assertEquals(10.80, (float) $nota->ca);
        $this->assertNull($nota->pt3);
    }

    public function test_recalcula_decima_terceira_sem_pg_e_com_ca_da_decima_segunda(): void
    {
        $nota = new Nota([
            'ano_letivo_id' => 1,
            'mac1' => 15,
            'pp1' => 15,
            'pt1' => 15,
            'mac2' => 15,
            'pp2' => 15,
            'pt2' => 15,
            'mac3' => 16,
            'pp3' => 14,
            'ca_12' => 15,
        ]);

        $nota->setRelation('turma', new Turma(['classe' => 13]));
        $nota->setRelation('disciplina', new Disciplina([
            'leciona_10' => false,
            'leciona_11' => false,
            'leciona_12' => true,
            'leciona_13' => true,
        ]));

        $config = $this->configuracao([
            1 => [
                ['codigo' => 'mac1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp1', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt1', 'peso' => 1, 'ativo' => true],
            ],
            2 => [
                ['codigo' => 'mac2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp2', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pt2', 'peso' => 1, 'ativo' => true],
            ],
            3 => [
                ['codigo' => 'mac3', 'peso' => 1, 'ativo' => true],
                ['codigo' => 'pp3', 'peso' => 1, 'ativo' => true],
            ],
        ], 40);

        $nota->recalcular($config);

        $this->assertEquals(15.00, (float) $nota->mft2);
        $this->assertEquals(15.00, (float) $nota->mt3);
        $this->assertEquals(15.00, (float) $nota->cf);
        $this->assertEquals(15.00, (float) $nota->ca);
        $this->assertEquals(15.00, (float) $nota->cfd);
    }

    private function configuracao(array $provasPorPeriodo, float $pesoPg): ConfiguracaoAvaliacao
    {
        $config = new ConfiguracaoAvaliacao([
            'ano_letivo_id' => 1,
            'peso_pg' => $pesoPg,
            'nota_minima_aprovacao' => 10,
        ]);

        $provas = collect();

        foreach ($provasPorPeriodo as $periodo => $provasPeriodo) {
            foreach ($provasPeriodo as $index => $prova) {
                $provas->push(new ProvaAvaliacao([
                    'periodo' => $periodo,
                    'nome' => strtoupper($prova['codigo']),
                    'codigo' => $prova['codigo'],
                    'peso' => $prova['peso'],
                    'ativo' => $prova['ativo'],
                    'ordem' => $index + 1,
                ]));
            }
        }

        $config->setRelation('provas', $provas);

        return $config;
    }
}

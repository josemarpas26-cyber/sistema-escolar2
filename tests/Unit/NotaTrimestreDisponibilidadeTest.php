<?php

namespace Tests\Unit;

use App\Models\AnoLetivo;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use Tests\TestCase;

class NotaTrimestreDisponibilidadeTest extends TestCase
{
    public function test_matricula_no_inicio_do_terceiro_trimestre_define_disponibilidade_corretamente(): void
    {
        $anoLetivo = new AnoLetivo([
            'data_inicio' => '2026-04-20',
            'data_fim' => '2026-04-26',
        ]);

        $turma = new Turma(['id' => 10]);
        $turma->id = 10;
        $turma->setRelation('pivot', (object) ['data_matricula' => '2026-04-25']);

        $aluno = new User();
        $aluno->setRelation('turmas', collect([$turma]));

        $nota = new Nota(['turma_id' => 10]);
        $nota->setRelation('aluno', $aluno);
        $nota->setRelation('anoLetivo', $anoLetivo);

        $this->assertSame(3, $nota->trimestreInicialDisponivel());
        $this->assertFalse($nota->trimestreEstaDisponivel(2));
        $this->assertTrue($nota->trimestreEstaDisponivel(3));
    }
}

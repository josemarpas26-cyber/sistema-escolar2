<?php

namespace Tests\Feature;

use App\Exports\BoletimExport;
use App\Models\AnoLetivo;
use App\Models\ClassificacaoEnsinoMedio;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use Tests\TestCase;

class BoletimExportDecimaTerceiraTest extends TestCase
{
    public function test_exportacao_individual_da_decima_terceira_usa_campos_finais_especificos(): void
    {
        $aluno = User::factory()->make(['name' => 'Aluno 13']);
        $curso = new Curso(['nome' => 'Informática']);
        $anoLetivo = new AnoLetivo(['nome' => '2025/2026']);
        $turma = new Turma(['classe' => '13']);
        $turma->setRelation('curso', $curso);
        $turma->setRelation('anoLetivo', $anoLetivo);
        $turma->nome_completo = 'INF13A';

        $disciplina = new Disciplina(['nome' => 'Projecto Tecnológico']);
        $nota = new Nota(['cfd' => 16]);
        $nota->setRelation('disciplina', $disciplina);

        $classificacao = new ClassificacaoEnsinoMedio([
            'ecs' => 12,
            'pap' => 18,
            'observacoes' => 'Apto para defesa final.',
        ]);

        $export = new BoletimExport(
            $aluno,
            $turma,
            collect([$nota]),
            0,
            'final',
            [
                'classificacao' => $classificacao,
                'pc' => 16,
                'media_final' => 16,
                'resultado' => 'Aprovado',
            ]
        );

        $this->assertSame(
            ['Disciplina', 'CFD', 'PC', 'E.C.S', 'PAP', 'Média Final (MF)', 'Resultado', 'Observações'],
            $export->headings()
        );

        $linhas = $export->collection()->values();
        $linhaDisciplina = $linhas[7];

        $this->assertSame('Projecto Tecnológico', $linhaDisciplina[0]);
        $this->assertSame('16.00', $linhaDisciplina[1]);
        $this->assertSame('16.00', $linhaDisciplina[2]);
        $this->assertSame('12.00', $linhaDisciplina[3]);
        $this->assertSame('18.00', $linhaDisciplina[4]);
        $this->assertSame('16.00', $linhaDisciplina[5]);
        $this->assertSame('Aprovado', $linhaDisciplina[6]);
        $this->assertSame('Apto para defesa final.', $linhaDisciplina[7]);
    }
}

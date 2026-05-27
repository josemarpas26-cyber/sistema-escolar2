<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\ClassificacaoEnsinoMedio;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use Tests\TestCase;

class RelatorioBoletimDecimaTerceiraPdfTest extends TestCase
{
    public function test_boletim_pdf_da_decima_terceira_renderiza_campos_finais_especificos(): void
    {
        $aluno = User::factory()->make([
            'name' => 'Aluno 13 PDF',
            'numero_processo' => '2025301',
            'genero' => 'M',
        ]);

        $curso = new Curso(['nome' => 'Informática']);
        $turma = new Turma(['nome' => 'A', 'classe' => '13']);
        $turma->setRelation('curso', $curso);

        $anoLetivo = new AnoLetivo(['nome' => '2025/2026']);
        $disciplina = new Disciplina(['nome' => 'Projecto Tecnológico', 'codigo' => 'PTEC']);
        $nota = new Nota(['cfd' => 16]);
        $nota->setRelation('disciplina', $disciplina);

        $classificacao = new ClassificacaoEnsinoMedio([
            'ecs' => 12,
            'pap' => 18,
            'observacoes' => 'Apto para defesa final.',
        ]);

        $html = view('relatorios.pdf.boletim', [
            'aluno' => $aluno,
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'notas' => collect([$nota]),
            'mediaGeral' => 0,
            'aprovacoes' => 0,
            'reprovacoes' => 0,
            'trimestre' => 'final',
            'classificacaoEnsinoMedioResumo' => [
                'classificacao' => $classificacao,
                'pc' => 16,
                'media_final' => 16,
                'resultado' => 'Aprovado',
            ],
        ])->render();

        $this->assertStringContainsString('Projecto Tecnológico', $html);
        $this->assertStringContainsString('E. C. S', $html);
        $this->assertStringContainsString('Média Final (MF)', $html);
        $this->assertStringContainsString('Apto para defesa final.', $html);
        $this->assertStringContainsString('Aprovado', $html);
    }
}

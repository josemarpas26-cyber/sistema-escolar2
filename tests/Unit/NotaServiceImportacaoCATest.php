<?php

namespace Tests\Unit;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\HistoricoAcademico;
use App\Models\Nota;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use App\Services\NotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaServiceImportacaoCATest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_ca_do_historico_quando_nao_ha_nota_anterior(): void
    {
        [$aluno, $disciplina, $turma10, $ano2025] = $this->baseAcademica();

        HistoricoAcademico::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma10->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $ano2025->id,
            'classe' => '10',
            'classificacao_final' => 14.75,
            'resultado' => 'aprovado',
            'data_conclusao' => '2025-12-20',
        ]);

        $servico = app(NotaService::class);
        $ca = $servico->importarCAAnterior($aluno, $disciplina, '10');

        $this->assertSame(14.75, $ca);
    }

    public function test_prioriza_ca_da_nota_interna_em_vez_do_historico(): void
    {
        [$aluno, $disciplina, $turma10, $ano2025] = $this->baseAcademica();

        HistoricoAcademico::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma10->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $ano2025->id,
            'classe' => '10',
            'classificacao_final' => 12.0,
            'resultado' => 'aprovado',
            'data_conclusao' => '2025-12-20',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma10->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $ano2025->id,
            'ca' => 16.25,
            'status' => 'finalizado',
        ]);

        $servico = app(NotaService::class);
        $ca = $servico->importarCAAnterior($aluno, $disciplina, '10');

        $this->assertSame(16.25, $ca);
    }

    private function baseAcademica(): array
    {
        $roleAluno = Role::create([
            'name' => 'aluno',
            'display_name' => 'Aluno',
        ]);

        $aluno = User::factory()->create([
            'role_id' => $roleAluno->id,
        ]);

        $ano2025 = AnoLetivo::create([
            'nome' => '2025',
            'data_inicio' => '2025-02-01',
            'data_fim' => '2025-12-20',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $curso = Curso::create([
            'nome' => 'Ciencias',
            'codigo' => 'CIE',
            'ativo' => true,
        ]);

        $turma10 = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $ano2025->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplina = Disciplina::create([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'ativo' => true,
        ]);

        return [$aluno, $disciplina, $turma10, $ano2025];
    }
}

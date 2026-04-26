<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use App\Services\EstadoMatriculaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadoMatriculaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_define_concluido_quando_aluno_aprovado_em_todas_as_disciplinas(): void
    {
        [$turma, $aluno, $disciplinas, $ano] = $this->criarCenarioBase();

        foreach ($disciplinas as $disciplina) {
            Nota::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
                'ano_letivo_id' => $ano->id,
                'cfd' => 14,
            ]);
        }

        app(EstadoMatriculaService::class)->sincronizarAlunoNaTurma($turma->id, $aluno->id);

        $this->assertDatabaseHas('turma_aluno', [
            'turma_id' => $turma->id,
            'aluno_id' => $aluno->id,
            'status' => 'concluido',
        ]);
    }

    public function test_mantem_matriculado_quando_faltam_notas_ou_ha_reprovacao(): void
    {
        [$turma, $aluno, $disciplinas, $ano] = $this->criarCenarioBase();

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinas[0]->id,
            'ano_letivo_id' => $ano->id,
            'cfd' => 8,
        ]);

        app(EstadoMatriculaService::class)->sincronizarAlunoNaTurma($turma->id, $aluno->id);

        $this->assertDatabaseHas('turma_aluno', [
            'turma_id' => $turma->id,
            'aluno_id' => $aluno->id,
            'status' => 'matriculado',
        ]);
    }

    public function test_define_concluido_quando_aluno_tem_duas_negativas_nao_terminais_permitidas(): void
    {
        [$turma, $aluno, $disciplinas, $ano] = $this->criarCenarioBase();

        $disciplinaExtra = Disciplina::create([
            'nome' => 'Quimica',
            'codigo' => 'QUI',
        ]);

        $turma->disciplinas()->attach($disciplinaExtra->id);
        $disciplinas[] = $disciplinaExtra;

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinas[0]->id,
            'ano_letivo_id' => $ano->id,
            'cfd' => 8,
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinas[1]->id,
            'ano_letivo_id' => $ano->id,
            'cfd' => 8,
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinas[2]->id,
            'ano_letivo_id' => $ano->id,
            'cfd' => 14,
        ]);

        app(EstadoMatriculaService::class)->sincronizarAlunoNaTurma($turma->id, $aluno->id);

        $this->assertDatabaseHas('turma_aluno', [
            'turma_id' => $turma->id,
            'aluno_id' => $aluno->id,
            'status' => 'concluido',
        ]);
    }

    public function test_nao_sobrescreve_status_manual_transferido_ou_desistente(): void
    {
        [$turma, $aluno] = $this->criarCenarioBase();

        $turma->alunos()->updateExistingPivot($aluno->id, ['status' => 'desistente']);

        app(EstadoMatriculaService::class)->sincronizarAlunoNaTurma($turma->id, $aluno->id);

        $this->assertDatabaseHas('turma_aluno', [
            'turma_id' => $turma->id,
            'aluno_id' => $aluno->id,
            'status' => 'desistente',
        ]);
    }

    private function criarCenarioBase(): array
    {
        $roleAluno = Role::create([
            'name' => 'aluno',
            'display_name' => 'Aluno',
        ]);

        $aluno = User::factory()->create([
            'role_id' => $roleAluno->id,
        ]);

        $ano = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
        ]);

        $curso = Curso::create([
            'nome' => 'Ciencias',
            'codigo' => 'CFB',
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $ano->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplinas = [
            Disciplina::create(['nome' => 'Matematica', 'codigo' => 'MAT']),
            Disciplina::create(['nome' => 'Fisica', 'codigo' => 'FIS']),
        ];

        $turma->disciplinas()->attach(array_map(fn ($d) => $d->id, $disciplinas));

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => now()->toDateString(),
            'status' => 'matriculado',
        ]);

        return [$turma, $aluno, $disciplinas, $ano];
    }
}

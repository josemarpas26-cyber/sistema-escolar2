<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Permission;
use App\Models\ProfessorTurmaDisciplina;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstatisticasAcademicasTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_que_acumula_coordenacoes_ve_todas_as_secoes(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $professor = User::factory()->create(['role_id' => $professorRole->id]);

        ['anoLetivo' => $anoLetivo, 'curso' => $curso, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica([
            'curso' => ['coordenador_id' => $professor->id],
            'turma' => ['coordenador_turma_id' => $professor->id],
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('estatisticas.index'));

        $response->assertOk();
        $response->assertSeeText('Minhas disciplinas lecionadas');
        $response->assertSeeText('Turmas sob minha coordenacao');
        $response->assertSeeText('Cursos sob minha coordenacao');
    }

    public function test_estatisticas_do_professor_separam_mesma_disciplina_por_turma(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno1 = User::factory()->create(['role_id' => $alunoRole->id]);
        $aluno2 = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'curso' => $curso, 'turma' => $turmaA, 'disciplina' => $disciplina] = $this->createEstruturaAcademica([
            'turma' => ['nome' => 'A'],
        ]);

        $turmaB = Turma::create([
            'nome' => 'B',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => null,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $turmaB->disciplinas()->attach($disciplina->id);

        $turmaA->alunos()->attach($aluno1->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        $turmaB->alunos()->attach($aluno2->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::insert([
            [
                'professor_id' => $professor->id,
                'turma_id' => $turmaA->id,
                'disciplina_id' => $disciplina->id,
                'ano_letivo_id' => $anoLetivo->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'professor_id' => $professor->id,
                'turma_id' => $turmaB->id,
                'disciplina_id' => $disciplina->id,
                'ano_letivo_id' => $anoLetivo->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Nota::create([
            'aluno_id' => $aluno1->id,
            'turma_id' => $turmaA->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 14,
            'status' => 'em_lancamento',
        ]);

        Nota::create([
            'aluno_id' => $aluno2->id,
            'turma_id' => $turmaB->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 9,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('estatisticas.index'));

        $response->assertOk();

        $html = $response->getContent();

        preg_match_all('/Matematica\s*[—-]\s*MAT/u', $html, $matches);

        $this->assertSame(2, count($matches[0]));
    }

    public function test_resumo_da_pauta_aparece_na_tela_de_notas_do_professor(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica();

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 15,
            'mt2' => 12,
            'mt3' => 14,
            'cfd' => 13,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('notas.professor-index', [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
            ]));

        $response->assertOk();
        $response->assertSeeText('Resumo da Pauta');
        $response->assertSeeText('1º Trimestre');
        $response->assertSeeText('Preenchimento');
    }

    private function createEstruturaAcademica(array $overrides = []): array
    {
        $anoLetivo = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $coordenador = User::factory()->create();

        $curso = Curso::create(array_merge([
            'nome' => 'Curso Teste',
            'codigo' => 'CT',
            'coordenador_id' => $coordenador->id,
            'ativo' => true,
        ], $overrides['curso'] ?? []));

        $turma = Turma::create(array_merge([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => $coordenador->id,
            'capacidade' => 40,
            'ativo' => true,
        ], $overrides['turma'] ?? []));

        $disciplina = Disciplina::create(array_merge([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'descricao' => 'Disciplina de teste',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ], $overrides['disciplina'] ?? []));

        $turma->disciplinas()->attach($disciplina->id);
        $curso->disciplinas()->attach($disciplina->id, ['ano_terminal' => 12]);

        return compact('anoLetivo', 'curso', 'turma', 'disciplina');
    }

    private function createRoleWithPermissions(string $name, array $permissionNames): Role
    {
        $role = Role::create([
            'name' => $name,
            'display_name' => ucfirst($name),
            'description' => "Role {$name} para testes",
        ]);

        $permissionIds = collect($permissionNames)
            ->map(function (string $permissionName) {
                return Permission::firstOrCreate(
                    ['name' => $permissionName],
                    [
                        'display_name' => $permissionName,
                        'description' => "Permissao {$permissionName} para testes",
                    ]
                )->id;
            })
            ->all();

        $role->permissions()->sync($permissionIds);

        return $role;
    }
}

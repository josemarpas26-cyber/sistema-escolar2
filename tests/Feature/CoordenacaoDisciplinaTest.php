<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordenacaoDisciplinaTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_nao_pode_coordenar_mais_de_uma_disciplina(): void
    {
        $gestorRole = $this->createRoleWithPermissions('gestor', ['disciplinas.create']);
        $professorRole = $this->createRoleWithPermissions('professor', []);

        $gestor = User::factory()->create([
            'role_id' => $gestorRole->id,
            'ativo' => true,
        ]);

        $professor = User::factory()->create([
            'role_id' => $professorRole->id,
            'ativo' => true,
        ]);

        Disciplina::create([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'descricao' => 'Primeira disciplina',
            'coordenador_id' => $professor->id,
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($gestor)
            ->from(route('disciplinas.create'))
            ->post(route('disciplinas.store'), [
                'nome' => 'Fisica',
                'codigo' => 'FIS',
                'descricao' => 'Segunda disciplina',
                'coordenador_id' => $professor->id,
                'leciona_10' => '1',
                'ativo' => '1',
            ]);

        $response->assertRedirect(route('disciplinas.create'));
        $response->assertSessionHasErrors(['coordenador_id']);
    }

    public function test_coordenador_de_disciplina_ve_secao_estatistica_especifica(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create([
            'role_id' => $professorRole->id,
            'ativo' => true,
        ]);

        $aluno = User::factory()->create([
            'role_id' => $alunoRole->id,
            'ativo' => true,
        ]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica([
            'disciplina' => ['coordenador_id' => $professor->id],
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 14,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('estatisticas.index'));

        $response->assertOk();
        $response->assertSeeText('Disciplinas sob minha coordenacao');
        $response->assertSeeText('Disciplina Base');
        $response->assertSeeText('Detalhe por turma');
    }

    public function test_pauta_geral_do_coordenador_de_disciplina_fica_restrita_a_sua_disciplina(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['relatorios.pautas']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create([
            'role_id' => $professorRole->id,
            'ativo' => true,
        ]);

        $aluno = User::factory()->create([
            'role_id' => $alunoRole->id,
            'ativo' => true,
        ]);

        ['anoLetivo' => $anoLetivo, 'curso' => $curso, 'turma' => $turma] = $this->createEstruturaAcademica();

        $matematica = Disciplina::create([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'descricao' => 'Disciplina coordenada',
            'coordenador_id' => $professor->id,
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $fisica = Disciplina::create([
            'nome' => 'Fisica',
            'codigo' => 'FIS',
            'descricao' => 'Outra disciplina',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $turma->disciplinas()->sync([$matematica->id, $fisica->id]);
        $curso->disciplinas()->sync([
            $matematica->id => ['ano_terminal' => 12],
            $fisica->id => ['ano_terminal' => 12],
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $matematica->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 14,
            'status' => 'em_lancamento',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $fisica->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 9,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('relatorios.pauta-geral', [
                'turma' => $turma,
                'ano_letivo_id' => $anoLetivo->id,
            ]));

        $response->assertOk();
        $response->assertSeeText('MAT');
        $response->assertDontSeeText('FIS');
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

        $coordenador = User::factory()->create(['ativo' => true]);

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
            'nome' => 'Disciplina Base',
            'codigo' => 'DBA',
            'descricao' => 'Disciplina base',
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

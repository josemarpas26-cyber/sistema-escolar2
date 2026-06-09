<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAlunosListTest extends TestCase
{
    use RefreshDatabase;

    public function test_aluno_em_recurso_continua_a_aparecer_com_a_turma_na_listagem_de_alunos(): void
    {
        $secretariaRole = $this->createRoleWithPermissions('secretaria', ['users.view']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $secretaria = User::factory()->create(['role_id' => $secretariaRole->id]);
        $aluno = User::factory()->create([
            'name' => 'Aluno Em Recurso',
            'role_id' => $alunoRole->id,
        ]);

        $turma = $this->createTurma();
        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'recurso',
        ]);

        $this
            ->actingAs($secretaria)
            ->get(route('users.alunos'))
            ->assertOk()
            ->assertSee('Aluno Em Recurso')
            ->assertSee($turma->nome_completo)
            ->assertDontSee('Sem turma');
    }

    private function createTurma(): Turma
    {
        $anoLetivo = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $curso = Curso::create([
            'nome' => 'Curso Teste',
            'codigo' => 'CT',
            'ativo' => true,
        ]);

        return Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'capacidade' => 40,
            'turno' => 'M',
            'ativo' => true,
        ]);
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

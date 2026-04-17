<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FocusModeOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_matricula_em_massa_utiliza_upsert_para_alunos_selecionados(): void
    {
        $admin = $this->createAdminComPermissoes(['users.edit']);
        $alunoRole = $this->createRole('aluno');
        $turma = $this->createTurma();

        $alunos = User::factory()->count(2)->create([
            'role_id' => $alunoRole->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('focus.matricular-alunos'), [
                'user_ids' => $alunos->pluck('id')->all(),
                'turma_id' => $turma->id,
            ]);

        $response->assertRedirect();

        foreach ($alunos as $aluno) {
            $this->assertDatabaseHas('turma_aluno', [
                'turma_id' => $turma->id,
                'aluno_id' => $aluno->id,
                'status' => 'matriculado',
            ]);
        }
    }

    public function test_atualizacao_em_lote_altera_status_dos_utilizadores(): void
    {
        $admin = $this->createAdminComPermissoes(['users.edit']);
        $alunoRole = $this->createRole('aluno');
        $alunos = User::factory()->count(3)->create([
            'role_id' => $alunoRole->id,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('focus.atualizar-status'), [
                'user_ids' => $alunos->pluck('id')->all(),
                'ativo' => 0,
            ]);

        $response->assertRedirect();

        foreach ($alunos as $aluno) {
            $this->assertDatabaseHas('users', [
                'id' => $aluno->id,
                'ativo' => false,
            ]);
        }
    }

    public function test_importacao_em_massa_cria_alunos_a_partir_de_csv(): void
    {
        $admin = $this->createAdminComPermissoes(['users.create']);
        $this->createRole('aluno');

        $csv = implode("\n", [
            'name,numero_processo,bi,data_nascimento,email',
            'Aluno Um,2026001,BI0001,2010-01-10,aluno1@example.test',
            'Aluno Dois,2026002,BI0002,2011-05-12,',
        ]);

        $file = UploadedFile::fake()->createWithContent('alunos.csv', $csv);

        $response = $this
            ->actingAs($admin)
            ->post(route('focus.importar-alunos'), [
                'ficheiro' => $file,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'numero_processo' => '2026001',
            'name' => 'Aluno Um',
        ]);

        $this->assertDatabaseHas('users', [
            'numero_processo' => '2026002',
            'name' => 'Aluno Dois',
        ]);
    }

    private function createAdminComPermissoes(array $permissions): User
    {
        $adminRole = $this->createRole('admin');

        $permissionIds = collect($permissions)
            ->map(fn (string $permission) => Permission::firstOrCreate([
                'name' => $permission,
            ], [
                'display_name' => $permission,
                'description' => "Permissão {$permission}",
            ])->id)
            ->all();

        $adminRole->permissions()->sync($permissionIds);

        return User::factory()->create([
            'role_id' => $adminRole->id,
        ]);
    }

    private function createRole(string $name): Role
    {
        return Role::firstOrCreate([
            'name' => $name,
        ], [
            'display_name' => ucfirst($name),
            'description' => "Perfil {$name}",
        ]);
    }

    private function createTurma(): Turma
    {
        $ano = AnoLetivo::create([
            'nome' => '2026/2027',
            'data_inicio' => '2026-09-01',
            'data_fim' => '2027-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $curso = Curso::create([
            'nome' => 'Ciências',
            'codigo' => 'CIE'.random_int(10, 99),
            'descricao' => 'Curso de testes',
            'ativo' => true,
        ]);

        return Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $ano->id,
            'capacidade' => 40,
            'turno' => 'M',
            'ativo' => true,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CredenciaisAcessoNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserPasswordGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_aluno_com_senha_automatica_recebe_numero_processo_como_senha(): void
    {
        $admin = $this->createUsuarioComPermissaoUsersCreate();
        $alunoRole = $this->createRole('aluno');

        $response = $this
            ->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Aluno Teste',
                'email' => null,
                'role_id' => $alunoRole->id,
                'auto_password' => '1',
                'bi' => '123456789LA123',
                'data_nascimento' => '2010-05-01',
                'numero_processo' => '20261234',
                'genero' => 'M',
            ]);

        $response->assertRedirect();

        $aluno = User::where('numero_processo', '20261234')->firstOrFail();

        $this->assertTrue(Hash::check('20261234', $aluno->password));
    }

    public function test_outros_utilizadores_com_senha_automatica_recebem_email_com_credenciais(): void
    {
        Notification::fake();

        $admin = $this->createUsuarioComPermissaoUsersCreate();
        $professorRole = $this->createRole('professor');

        $response = $this
            ->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Professor Teste',
                'email' => 'professor@example.com',
                'role_id' => $professorRole->id,
                'auto_password' => '1',
                'bi' => '987654321LA987',
                'data_nascimento' => '1990-10-10',
                'numero_processo' => 'PR2026001',
                'genero' => 'M',
            ]);

        $response->assertRedirect();

        $professor = User::where('email', 'professor@example.com')->firstOrFail();

        Notification::assertSentTo($professor, CredenciaisAcessoNotification::class);
        Notification::assertSentTo($professor, VerifyEmail::class);
    }

    private function createUsuarioComPermissaoUsersCreate(): User
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Administrador',
        ]);

        $permission = Permission::firstOrCreate(
            ['name' => 'users.create'],
            ['display_name' => 'users.create', 'description' => 'Permissão de criação de usuários']
        );

        $adminRole->permissions()->sync([$permission->id]);

        return User::factory()->create([
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);
    }

    private function createRole(string $name): Role
    {
        return Role::create([
            'name' => $name,
            'display_name' => ucfirst($name),
            'description' => "Perfil {$name}",
        ]);
    }
}

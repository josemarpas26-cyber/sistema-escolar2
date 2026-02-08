<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // === CRIAR PERMISSÕES ===
        $permissions = [
            // Gestão de usuários
            ['name' => 'users.create', 'display_name' => 'Criar usuários'],
            ['name' => 'users.edit', 'display_name' => 'Editar usuários'],
            ['name' => 'users.delete', 'display_name' => 'Excluir usuários'],
            ['name' => 'users.view', 'display_name' => 'Visualizar usuários'],
            
            // Gestão de cursos
            ['name' => 'cursos.create', 'display_name' => 'Criar cursos'],
            ['name' => 'cursos.edit', 'display_name' => 'Editar cursos'],
            ['name' => 'cursos.delete', 'display_name' => 'Excluir cursos'],
            ['name' => 'cursos.view', 'display_name' => 'Visualizar cursos'],
            
            // Gestão de disciplinas
            ['name' => 'disciplinas.create', 'display_name' => 'Criar disciplinas'],
            ['name' => 'disciplinas.edit', 'display_name' => 'Editar disciplinas'],
            ['name' => 'disciplinas.delete', 'display_name' => 'Excluir disciplinas'],
            ['name' => 'disciplinas.view', 'display_name' => 'Visualizar disciplinas'],
            
            // Gestão de turmas
            ['name' => 'turmas.create', 'display_name' => 'Criar turmas'],
            ['name' => 'turmas.edit', 'display_name' => 'Editar turmas'],
            ['name' => 'turmas.delete', 'display_name' => 'Excluir turmas'],
            ['name' => 'turmas.view', 'display_name' => 'Visualizar turmas'],
            ['name' => 'turmas.promote', 'display_name' => 'Promover turmas'],
            
            // Gestão de notas
            ['name' => 'notas.lancar', 'display_name' => 'Lançar notas'],
            ['name' => 'notas.editar', 'display_name' => 'Editar notas'],
            ['name' => 'notas.view_own', 'display_name' => 'Ver próprias notas'],
            ['name' => 'notas.view_turma', 'display_name' => 'Ver notas da turma'],
            ['name' => 'notas.view_curso', 'display_name' => 'Ver notas do curso'],
            ['name' => 'notas.view_all', 'display_name' => 'Ver todas notas'],
            
            // Gestão de anos letivos
            ['name' => 'anos.create', 'display_name' => 'Criar ano letivo'],
            ['name' => 'anos.encerrar', 'display_name' => 'Encerrar ano letivo'],
            
            // Relatórios
            ['name' => 'relatorios.boletins', 'display_name' => 'Exportar boletins'],
            ['name' => 'relatorios.pautas', 'display_name' => 'Gerar pautas'],
            ['name' => 'relatorios.historico', 'display_name' => 'Ver histórico académico'],
            
            // Sistema
            ['name' => 'system.backup', 'display_name' => 'Backup do sistema'],
            ['name' => 'logs.view', 'display_name' => 'Ver logs de alterações'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // === CRIAR ROLES ===

        // 1. Administrador
        $admin = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrador', 'description' => 'Acesso total ao sistema']
        );
        $admin->permissions()->sync(Permission::all()); // Todas as permissões

        // 2. Secretaria
        $secretaria = Role::firstOrCreate(
            ['name' => 'secretaria'],
            ['display_name' => 'Secretaria', 'description' => 'Gestão académica e administrativa']
        );
        $secretaria->permissions()->sync(Permission::whereIn('name', [
            'users.create', 'users.edit', 'users.view',
            'cursos.create', 'cursos.edit', 'cursos.view',
            'disciplinas.create', 'disciplinas.edit', 'disciplinas.view',
            'turmas.create', 'turmas.edit', 'turmas.view', 'turmas.promote',
            'notas.editar', 'notas.view_all',
            'relatorios.boletins', 'relatorios.pautas', 'relatorios.historico',
            'logs.view',
        ])->pluck('id'));

        // 3. Professor
        $professor = Role::firstOrCreate(
            ['name' => 'professor'],
            ['display_name' => 'Professor', 'description' => 'Leciona disciplinas e lança notas']
        );
        $professor->permissions()->sync(Permission::whereIn('name', [
            'notas.lancar', 'notas.view_turma',
            'turmas.view',
            'disciplinas.view',
        ])->pluck('id'));

        // 4. Aluno
        $aluno = Role::firstOrCreate(
            ['name' => 'aluno'],
            ['display_name' => 'Aluno', 'description' => 'Consulta suas notas e histórico']
        );
        $aluno->permissions()->sync(Permission::whereIn('name', [
            'notas.view_own',
            'relatorios.historico',
        ])->pluck('id'));

        $this->command->info('✅ Roles e Permissions criados com sucesso!');
    }
}
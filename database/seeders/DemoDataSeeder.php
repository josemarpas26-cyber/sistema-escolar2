<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Criando dados de demonstração...');

        // === CRIAR USUÁRIOS ===
        $roles = Role::all()->keyBy('name');

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@escola.ao'],
            [
                'name' => 'Administrador do Sistema',
                'password' => Hash::make('password'),
                'role_id' => $roles['admin']->id,
                'ativo' => true,
            ]
        );

        // Secretaria
        $secretaria = User::firstOrCreate(
            ['email' => 'secretaria@escola.ao'],
            [
                'name' => 'Maria Silva',
                'password' => Hash::make('password'),
                'role_id' => $roles['secretaria']->id,
                'telefone' => '923456789',
                'ativo' => true,
            ]
        );

        // Professores
        $prof1 = User::firstOrCreate(
            ['email' => 'prof.matematica@escola.ao'],
            [
                'name' => 'João Fernandes',
                'password' => Hash::make('password'),
                'role_id' => $roles['professor']->id,
                'bi' => '006789012LA045',
                'telefone' => '923111222',
                'genero' => 'M',
                'ativo' => true,
            ]
        );

        $prof2 = User::firstOrCreate(
            ['email' => 'prof.fisica@escola.ao'],
            [
                'name' => 'Ana Costa',
                'password' => Hash::make('password'),
                'role_id' => $roles['professor']->id,
                'bi' => '006789013LA045',
                'telefone' => '923222333',
                'genero' => 'F',
                'ativo' => true,
            ]
        );

        // Alunos
        $aluno1 = User::firstOrCreate(
            ['email' => 'aluno1@escola.ao'],
            [
                'name' => 'Pedro Santos',
                'password' => Hash::make('password'),
                'role_id' => $roles['aluno']->id,
                'numero_processo' => '2024001',
                'bi' => '006789014LA045',
                'data_nascimento' => '2008-05-15',
                'genero' => 'M',
                'telefone' => '923333444',
                'nome_encarregado' => 'José Santos',
                'contacto_encarregado' => '923555666',
                'ativo' => true,
            ]
        );

        $aluno2 = User::firstOrCreate(
            ['email' => 'aluno2@escola.ao'],
            [
                'name' => 'Catarina Lopes',
                'password' => Hash::make('password'),
                'role_id' => $roles['aluno']->id,
                'numero_processo' => '2024002',
                'bi' => '006789015LA045',
                'data_nascimento' => '2008-08-22',
                'genero' => 'F',
                'telefone' => '923444555',
                'nome_encarregado' => 'Teresa Lopes',
                'contacto_encarregado' => '923666777',
                'ativo' => true,
            ]
        );

        $this->command->info('✅ Usuários criados');

        // === CRIAR ANO LETIVO ===
        $anoLetivo = AnoLetivo::firstOrCreate(
            ['nome' => '2024/2025'],
            [
                'data_inicio' => '2024-09-01',
                'data_fim' => '2025-06-30',
                'ativo' => true,
                'encerrado' => false,
            ]
        );

        $this->command->info('✅ Ano letivo criado');

        // === CRIAR CURSOS ===
        $cursos = [
            ['nome' => 'Ciências Físicas e Biológicas', 'codigo' => 'CFB', 'coordenador_id' => $prof1->id],
            ['nome' => 'Ciências Económicas e Jurídicas', 'codigo' => 'CEJ', 'coordenador_id' => $prof2->id],
            ['nome' => 'Ciências Humanas', 'codigo' => 'CH', 'coordenador_id' => null],
        ];

        foreach ($cursos as $cursoData) {
            Curso::firstOrCreate(
                ['codigo' => $cursoData['codigo']],
                $cursoData
            );
        }

        $this->command->info('✅ Cursos criados');

        // === CRIAR DISCIPLINAS ===
        $disciplinas = [
            ['nome' => 'Matemática', 'codigo' => 'MAT', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Física', 'codigo' => 'FIS', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Química', 'codigo' => 'QUI', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Biologia', 'codigo' => 'BIO', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Língua Portuguesa', 'codigo' => 'LP', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Inglês', 'codigo' => 'ING', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'História', 'codigo' => 'HIS', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Geografia', 'codigo' => 'GEO', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'Educação Física', 'codigo' => 'EF', 'leciona_10' => true, 'leciona_11' => true, 'leciona_12' => true],
            ['nome' => 'TIC', 'codigo' => 'TIC', 'leciona_10' => true, 'leciona_11' => false, 'leciona_12' => false, 'disciplina_terminal' => true],
        ];

        foreach ($disciplinas as $discData) {
            Disciplina::firstOrCreate(
                ['codigo' => $discData['codigo']],
                $discData
            );
        }

        $this->command->info('✅ Disciplinas criadas');

        $this->command->info('🎉 Dados de demonstração criados com sucesso!');
        $this->command->info('');
        $this->command->info('📧 Credenciais de acesso:');
        $this->command->info('Admin: admin@escola.ao / password');
        $this->command->info('Secretaria: secretaria@escola.ao / password');
        $this->command->info('Professor: prof.matematica@escola.ao / password');
        $this->command->info('Aluno: aluno1@escola.ao / password');
    }
}
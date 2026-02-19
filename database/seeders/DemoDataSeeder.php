<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Turma;
use App\Models\Nota;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Criando dados de demonstração...');

        // =============================================
        // 1. USUÁRIOS
        // =============================================
        $roles = Role::all()->keyBy('name');

        // --- Admin ---
        $admin = User::firstOrCreate(
            ['email' => 'admin@escola.ao'],
            [
                'name'     => 'Administrador do Sistema',
                'password' => Hash::make('password'),
                'role_id'  => $roles['admin']->id,
                'ativo'    => true,
            ]
        );

        // --- Secretaria ---
        User::firstOrCreate(
            ['email' => 'secretaria@escola.ao'],
            [
                'name'     => 'Fátima Cardoso',
                'password' => Hash::make('password'),
                'role_id'  => $roles['secretaria']->id,
                'telefone' => '923456789',
                'genero'   => 'F',
                'ativo'    => true,
            ]
        );

        // --- Professores ---
        $professores_data = [
            ['name' => 'João Fernandes',      'email' => 'prof.mat@escola.ao',  'bi' => '006000001LA041', 'telefone' => '923101010', 'genero' => 'M'],
            ['name' => 'Ana Paula Costa',     'email' => 'prof.fis@escola.ao',  'bi' => '006000002LA041', 'telefone' => '923202020', 'genero' => 'F'],
            ['name' => 'Carlos Mendonça',     'email' => 'prof.qui@escola.ao',  'bi' => '006000003LA041', 'telefone' => '923303030', 'genero' => 'M'],
            ['name' => 'Luísa Neto',          'email' => 'prof.bio@escola.ao',  'bi' => '006000004LA041', 'telefone' => '923404040', 'genero' => 'F'],
            ['name' => 'António Sebastião',   'email' => 'prof.port@escola.ao', 'bi' => '006000005LA041', 'telefone' => '923505050', 'genero' => 'M'],
            ['name' => 'Esperança Teixeira',  'email' => 'prof.ing@escola.ao',  'bi' => '006000006LA041', 'telefone' => '923606060', 'genero' => 'F'],
            ['name' => 'Manuel Gonçalves',    'email' => 'prof.hist@escola.ao', 'bi' => '006000007LA041', 'telefone' => '923707070', 'genero' => 'M'],
            ['name' => 'Graça Domingos',      'email' => 'prof.geo@escola.ao',  'bi' => '006000008LA041', 'telefone' => '923808080', 'genero' => 'F'],
            ['name' => 'Nelson Afonso',       'email' => 'prof.ef@escola.ao',   'bi' => '006000009LA041', 'telefone' => '923909090', 'genero' => 'M'],
            ['name' => 'Sandra Loureiro',     'email' => 'prof.tic@escola.ao',  'bi' => '006000010LA041', 'telefone' => '923010101', 'genero' => 'F'],
        ];

        $professores = [];
        foreach ($professores_data as $i => $pd) {
            $professores[] = User::firstOrCreate(
                ['email' => $pd['email']],
                array_merge($pd, [
                    'password' => Hash::make('password'),
                    'role_id'  => $roles['professor']->id,
                    'ativo'    => true,
                ])
            );
        }

        $this->command->info('✅ Utilizadores de staff criados (' . count($professores) . ' professores)');

        // --- Alunos (30 alunos realistas angolanos) ---
        $alunos_data = [
            // Masculinos
            ['name' => 'Adilson Mateus Ngueira',       'genero' => 'M', 'nasc' => '2007-03-12', 'enc' => 'Mateus Ngueira',       'tel_enc' => '923111001'],
            ['name' => 'Bruno Catumbela Sousa',         'genero' => 'M', 'nasc' => '2007-07-24', 'enc' => 'Catumbela Sousa',      'tel_enc' => '923111002'],
            ['name' => 'Celestino Faria Pinto',         'genero' => 'M', 'nasc' => '2006-11-05', 'enc' => 'Faria Pinto',         'tel_enc' => '923111003'],
            ['name' => 'Daniel Augusto Tchimuanga',     'genero' => 'M', 'nasc' => '2007-01-18', 'enc' => 'Augusto Tchimuanga',  'tel_enc' => '923111004'],
            ['name' => 'Eduardo Nkosi Brito',           'genero' => 'M', 'nasc' => '2007-09-30', 'enc' => 'Nkosi Brito',         'tel_enc' => '923111005'],
            ['name' => 'Feliciano Mota Andrade',        'genero' => 'M', 'nasc' => '2006-05-14', 'enc' => 'Mota Andrade',        'tel_enc' => '923111006'],
            ['name' => 'Gilberto Landu Silva',          'genero' => 'M', 'nasc' => '2007-08-22', 'enc' => 'Landu Silva',         'tel_enc' => '923111007'],
            ['name' => 'Hélder Mavinga Costa',          'genero' => 'M', 'nasc' => '2006-12-03', 'enc' => 'Mavinga Costa',       'tel_enc' => '923111008'],
            ['name' => 'Ivo Tchissola Neves',           'genero' => 'M', 'nasc' => '2007-04-27', 'enc' => 'Tchissola Neves',     'tel_enc' => '923111009'],
            ['name' => 'Jonas Kamalata Ferreira',       'genero' => 'M', 'nasc' => '2007-06-15', 'enc' => 'Kamalata Ferreira',   'tel_enc' => '923111010'],
            ['name' => 'Kizua Mbangala Lopes',          'genero' => 'M', 'nasc' => '2006-02-08', 'enc' => 'Mbangala Lopes',      'tel_enc' => '923111011'],
            ['name' => 'Luís Benguela Marques',         'genero' => 'M', 'nasc' => '2007-10-19', 'enc' => 'Benguela Marques',    'tel_enc' => '923111012'],
            ['name' => 'Mário Cahungo Pereira',         'genero' => 'M', 'nasc' => '2006-07-31', 'enc' => 'Cahungo Pereira',     'tel_enc' => '923111013'],
            ['name' => 'Nuno Wangela Rodrigues',        'genero' => 'M', 'nasc' => '2007-03-25', 'enc' => 'Wangela Rodrigues',   'tel_enc' => '923111014'],
            ['name' => 'Orlando Tchivita Santos',       'genero' => 'M', 'nasc' => '2007-05-11', 'enc' => 'Tchivita Santos',     'tel_enc' => '923111015'],
            // Femininas
            ['name' => 'Ana Beatriz Tchilembe',         'genero' => 'F', 'nasc' => '2007-02-14', 'enc' => 'Carlos Tchilembe',    'tel_enc' => '923222001'],
            ['name' => 'Beatriz Nzinga Almeida',        'genero' => 'F', 'nasc' => '2006-08-07', 'enc' => 'Rosa Almeida',        'tel_enc' => '923222002'],
            ['name' => 'Catarina Mukondo Dias',         'genero' => 'F', 'nasc' => '2007-11-20', 'enc' => 'Mukondo Dias',        'tel_enc' => '923222003'],
            ['name' => 'Diana Kambinda Gomes',          'genero' => 'F', 'nasc' => '2007-01-09', 'enc' => 'Kambinda Gomes',      'tel_enc' => '923222004'],
            ['name' => 'Esperança Salomé Vieira',       'genero' => 'F', 'nasc' => '2006-06-28', 'enc' => 'Salomé Vieira',       'tel_enc' => '923222005'],
            ['name' => 'Filipa Ndunduma Cardoso',       'genero' => 'F', 'nasc' => '2007-09-16', 'enc' => 'Ndunduma Cardoso',    'tel_enc' => '923222006'],
            ['name' => 'Graça Kavindu Teixeira',        'genero' => 'F', 'nasc' => '2006-03-04', 'enc' => 'Kavindu Teixeira',    'tel_enc' => '923222007'],
            ['name' => 'Helena Tchitundo Baptista',     'genero' => 'F', 'nasc' => '2007-07-13', 'enc' => 'Tchitundo Baptista',  'tel_enc' => '923222008'],
            ['name' => 'Inês Luemba Fonseca',           'genero' => 'F', 'nasc' => '2007-04-02', 'enc' => 'Luemba Fonseca',      'tel_enc' => '923222009'],
            ['name' => 'Joana Kibala Monteiro',         'genero' => 'F', 'nasc' => '2006-10-23', 'enc' => 'Kibala Monteiro',     'tel_enc' => '923222010'],
            ['name' => 'Kátia Semba Lourenço',          'genero' => 'F', 'nasc' => '2007-08-05', 'enc' => 'Semba Lourenço',      'tel_enc' => '923222011'],
            ['name' => 'Laura Ndembo Correia',          'genero' => 'F', 'nasc' => '2006-12-17', 'enc' => 'Ndembo Correia',      'tel_enc' => '923222012'],
            ['name' => 'Maria Catota Nascimento',       'genero' => 'F', 'nasc' => '2007-05-29', 'enc' => 'Catota Nascimento',   'tel_enc' => '923222013'],
            ['name' => 'Natália Cubal Ferreira',        'genero' => 'F', 'nasc' => '2006-01-21', 'enc' => 'Cubal Ferreira',      'tel_enc' => '923222014'],
            ['name' => 'Olívia Tchissungo Pires',       'genero' => 'F', 'nasc' => '2007-06-08', 'enc' => 'Tchissungo Pires',    'tel_enc' => '923222015'],
        ];

        $alunos = [];
        foreach ($alunos_data as $i => $ad) {
            $num = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $bi  = '00700' . str_pad($i + 1, 5, '0', STR_PAD_LEFT) . 'LA041';
            $aluno = User::firstOrCreate(
                ['email' => 'aluno' . ($i + 1) . '@escola.ao'],
                [
                    'name'                  => $ad['name'],
                    'password'              => Hash::make('password'),
                    'role_id'               => $roles['aluno']->id,
                    'numero_processo'       => '2024' . $num,
                    'bi'                    => $bi,
                    'data_nascimento'       => $ad['nasc'],
                    'genero'                => $ad['genero'],
                    'telefone'              => '924' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                    'nome_encarregado'      => $ad['enc'],
                    'contacto_encarregado'  => $ad['tel_enc'],
                    'ativo'                 => true,
                ]
            );
            $alunos[] = $aluno;
        }

        $this->command->info('✅ ' . count($alunos) . ' alunos criados');

        // =============================================
        // 2. ANO LETIVO
        // =============================================
        $anoLetivo = AnoLetivo::firstOrCreate(
            ['nome' => '2025/2026'],
            [
                'data_inicio' => '2025-09-02',
                'data_fim'    => '2026-07-11',
                'ativo'       => true,
                'encerrado'   => false,
            ]
        );
        $this->command->info('✅ Ano letivo criado');

        // =============================================
        // 3. CURSOS
        // =============================================
        $cfb = Curso::firstOrCreate(
            ['codigo' => 'CFB'],
            ['nome' => 'Ciências Físicas e Biológicas',   'coordenador_id' => $professores[0]->id, 'ativo' => true]
        );
        $cej = Curso::firstOrCreate(
            ['codigo' => 'CEJ'],
            ['nome' => 'Ciências Económicas e Jurídicas', 'coordenador_id' => $professores[4]->id, 'ativo' => true]
        );
        $ch = Curso::firstOrCreate(
            ['codigo' => 'CH'],
            ['nome' => 'Ciências Humanas',                'coordenador_id' => $professores[6]->id, 'ativo' => true]
        );
        $this->command->info('✅ 3 cursos criados (CFB, CEJ, CH)');

        // =============================================
        // 4. DISCIPLINAS
        // =============================================
        $disc_data = [
            ['nome' => 'Matemática',        'codigo' => 'MAT', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Física',            'codigo' => 'FIS', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Química',           'codigo' => 'QUI', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Biologia',          'codigo' => 'BIO', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Língua Portuguesa', 'codigo' => 'LP',  'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Inglês',            'codigo' => 'ING', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'História',          'codigo' => 'HIS', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Geografia',         'codigo' => 'GEO', 'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Educação Física',   'codigo' => 'EF',  'l10' => true,  'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'TIC',               'codigo' => 'TIC', 'l10' => true,  'l11' => false, 'l12' => false, 'terminal' => true],
            ['nome' => 'Filosofia',         'codigo' => 'FIL', 'l10' => false, 'l11' => true,  'l12' => true,  'terminal' => false],
            ['nome' => 'Empreendedorismo',  'codigo' => 'EMP', 'l10' => false, 'l11' => false, 'l12' => true,  'terminal' => true],
        ];

        $disciplinas = [];
        foreach ($disc_data as $dd) {
            $disciplinas[$dd['codigo']] = Disciplina::firstOrCreate(
                ['codigo' => $dd['codigo']],
                [
                    'nome'                => $dd['nome'],
                    'leciona_10'          => $dd['l10'],
                    'leciona_11'          => $dd['l11'],
                    'leciona_12'          => $dd['l12'],
                    'disciplina_terminal' => $dd['terminal'],
                    'ativo'               => true,
                ]
            );
        }
        $this->command->info('✅ ' . count($disciplinas) . ' disciplinas criadas');

        // =============================================
        // 5. TURMAS
        // =============================================

        // Mapeamento professor -> disciplina (índice no array $professores)
        // 0=MAT, 1=FIS, 2=QUI, 3=BIO, 4=LP, 5=ING, 6=HIS, 7=GEO, 8=EF, 9=TIC

        // ---- Turma 10ª A - CFB (15 primeiros alunos) ----
        $turma10A = Turma::firstOrCreate(
            ['nome' => 'A', 'classe' => '10', 'curso_id' => $cfb->id, 'ano_letivo_id' => $anoLetivo->id],
            [
                'coordenador_turma_id' => $professores[0]->id,
                'capacidade'           => 40,
                'ativo'                => true,
            ]
        );

        // Disciplinas da 10ª A
        $discs10 = ['MAT','FIS','QUI','BIO','LP','ING','HIS','GEO','EF','TIC'];
        $turma10A->disciplinas()->syncWithoutDetaching(
            collect($discs10)->map(fn($c) => $disciplinas[$c]->id)->toArray()
        );

        // Atribuições de professores para 10ª A
        $prof_disc_10A = [
            ['prof' => 0, 'disc' => 'MAT'],
            ['prof' => 1, 'disc' => 'FIS'],
            ['prof' => 2, 'disc' => 'QUI'],
            ['prof' => 3, 'disc' => 'BIO'],
            ['prof' => 4, 'disc' => 'LP'],
            ['prof' => 5, 'disc' => 'ING'],
            ['prof' => 6, 'disc' => 'HIS'],
            ['prof' => 7, 'disc' => 'GEO'],
            ['prof' => 8, 'disc' => 'EF'],
            ['prof' => 9, 'disc' => 'TIC'],
        ];
        $this->atribuirProfessores($turma10A, $prof_disc_10A, $professores, $disciplinas, $anoLetivo->id);

        // Matricular os 15 primeiros alunos na 10ª A
        foreach (array_slice($alunos, 0, 15) as $aluno) {
            $this->matricularAluno($turma10A, $aluno, '2024-09-02');
        }

        // ---- Turma 10ª B - CEJ (15 últimos alunos) ----
        $turma10B = Turma::firstOrCreate(
            ['nome' => 'B', 'classe' => '10', 'curso_id' => $cej->id, 'ano_letivo_id' => $anoLetivo->id],
            [
                'coordenador_turma_id' => $professores[4]->id,
                'capacidade'           => 40,
                'ativo'                => true,
            ]
        );

        $turma10B->disciplinas()->syncWithoutDetaching(
            collect($discs10)->map(fn($c) => $disciplinas[$c]->id)->toArray()
        );

        $this->atribuirProfessores($turma10B, $prof_disc_10A, $professores, $disciplinas, $anoLetivo->id);

        foreach (array_slice($alunos, 15, 15) as $aluno) {
            $this->matricularAluno($turma10B, $aluno, '2024-09-02');
        }

        $this->command->info('✅ 2 turmas criadas (10ªA-CFB, 10ªB-CEJ) com alunos matriculados');

        // =============================================
        // 6. NOTAS (1º e 2º trimestre lançados)
        // =============================================
        $this->command->info('📝 Lançando notas...');

        $turmasComNotas = [
            ['turma' => $turma10A, 'alunos' => array_slice($alunos, 0, 15)],
            ['turma' => $turma10B, 'alunos' => array_slice($alunos, 15, 15)],
        ];

        foreach ($turmasComNotas as $tc) {
            $turma    = $tc['turma'];
            $discsIds = $turma->disciplinas()->pluck('disciplinas.id')->toArray();

            foreach ($tc['alunos'] as $aluno) {
                foreach ($discsIds as $discId) {
                    $this->criarNota($aluno->id, $turma->id, $discId, $anoLetivo->id);
                }
            }
        }

        $totalNotas = Nota::count();
        $this->command->info("✅ {$totalNotas} registos de notas criados (1º e 2º trimestres)");

        // =============================================
        // RESUMO FINAL
        // =============================================
        $this->command->info('');
        $this->command->info('🎉 Dados de demonstração criados com sucesso!');
        $this->command->info('');
        $this->command->info('📧 Credenciais de acesso (password: "password" para todos):');
        $this->command->info('   Admin       → admin@escola.ao');
        $this->command->info('   Secretaria  → secretaria@escola.ao');
        $this->command->info('   Professor   → prof.mat@escola.ao  (e outros prof.xxx@escola.ao)');
        $this->command->info('   Aluno       → aluno1@escola.ao  até  aluno30@escola.ao');
        $this->command->info('');
        $this->command->info('📊 Resumo:');
        $this->command->info('   ' . User::count() . ' utilizadores | ' . Turma::count() . ' turmas | ' . Nota::count() . ' notas');
    }

    // =============================================
    // HELPERS PRIVADOS
    // =============================================

    /**
     * Atribui professores a disciplinas numa turma (sem duplicar)
     */
    private function atribuirProfessores(Turma $turma, array $atribuicoes, array $professores, array $disciplinas, int $anoLetivoId): void
    {
        foreach ($atribuicoes as $at) {
            $profId = $professores[$at['prof']]->id;
            $discId = $disciplinas[$at['disc']]->id;

            $existe = DB::table('professor_turma_disciplina')
                ->where('professor_id',  $profId)
                ->where('turma_id',      $turma->id)
                ->where('disciplina_id', $discId)
                ->where('ano_letivo_id', $anoLetivoId)
                ->exists();

            if (!$existe) {
                DB::table('professor_turma_disciplina')->insert([
                    'professor_id'  => $profId,
                    'turma_id'      => $turma->id,
                    'disciplina_id' => $discId,
                    'ano_letivo_id' => $anoLetivoId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    /**
     * Matricula um aluno numa turma (sem duplicar)
     */
    private function matricularAluno(Turma $turma, User $aluno, string $dataMatricula): void
    {
        $existe = DB::table('turma_aluno')
            ->where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->exists();

        if (!$existe) {
            DB::table('turma_aluno')->insert([
                'turma_id'       => $turma->id,
                'aluno_id'       => $aluno->id,
                'data_matricula' => $dataMatricula,
                'status'         => 'matriculado',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * Cria notas com valores realistas para 1º e 2º trimestres.
     * Usa intervalo com ligeira variação para parecer natural.
     */
    private function criarNota(int $alunoId, int $turmaId, int $discId, int $anoLetivoId): void
    {
        $existe = Nota::where('aluno_id',     $alunoId)
                      ->where('turma_id',     $turmaId)
                      ->where('disciplina_id',$discId)
                      ->where('ano_letivo_id',$anoLetivoId)
                      ->exists();

        if ($existe) return;

        // Gerar notas com distribuição realista (maioria entre 10-16, alguns <10, poucos >17)
        $base = $this->notaAleatoria();

        // 1º Trimestre
        $mac1 = $this->variar($base, 2);
        $pp1  = $this->variar($base, 3);
        $pt1  = $this->variar($base, 4);
        $mt1  = round(($mac1 * 0.3 + $pp1 * 0.3 + $pt1 * 0.4), 2);

        // 2º Trimestre
        $mac2 = $this->variar($base, 2);
        $pp2  = $this->variar($base, 3);
        $pt2  = $this->variar($base, 4);
        $mt2  = round(($mac2 * 0.3 + $pp2 * 0.3 + $pt2 * 0.4), 2);

        // MFT2 = média entre MT1 e MT2
        $mft2 = round(($mt1 + $mt2) / 2, 2);

        Nota::create([
            'aluno_id'     => $alunoId,
            'turma_id'     => $turmaId,
            'disciplina_id'=> $discId,
            'ano_letivo_id'=> $anoLetivoId,

            // 1º Trimestre
            'mac1' => $mac1,
            'pp1'  => $pp1,
            'pt1'  => $pt1,
            'mt1'  => $mt1,

            // 2º Trimestre
            'mac2' => $mac2,
            'pp2'  => $pp2,
            'pt2'  => $pt2,
            'mt2'  => $mt2,
            'mft2' => $mft2,

            // 3º Trimestre ainda não lançado
            'mac3' => null,
            'pp3'  => null,
            'mt3'  => null,
            'cf'   => null,
            'pg'   => null,
            'ca'   => null,
            'cfd'  => null,

            'status' => 'em_lancamento',
        ]);
    }

    /**
     * Gera uma nota base com distribuição realista:
     *   ~15% chance de nota fraca (6–9)
     *   ~60% chance de nota média (10–14)
     *   ~25% chance de nota boa  (15–20)
     */
    private function notaAleatoria(): float
    {
        $rand = rand(1, 100);
        if ($rand <= 15) {
            return round(rand(60, 95) / 10, 1); // 6.0 – 9.5
        } elseif ($rand <= 75) {
            return round(rand(100, 145) / 10, 1); // 10.0 – 14.5
        } else {
            return round(rand(150, 200) / 10, 1); // 15.0 – 20.0
        }
    }

    /**
     * Varia uma nota base de forma aleatória dentro de um intervalo,
     * garantindo que o resultado fique entre 0 e 20.
     */
    private function variar(float $base, float $delta): float
    {
        $variacao = (rand(-100, 100) / 100) * $delta;
        $nota     = round($base + $variacao, 2);
        return max(0, min(20, $nota));
    }
}
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AvaliacaoContinua;
use App\Models\AreaFormacao;
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
        // 2. ANOS LETIVOS
        // =============================================
        $anosLetivos = [
            '2023/2024' => $this->criarAnoLetivoDemo('2023/2024', '2023-04-20', '2024-12-19', false, true),
            '2024/2025' => $this->criarAnoLetivoDemo('2024/2025', '2024-04-20', '2025-12-19', false, true),
            '2025/2026' => $this->criarAnoLetivoDemo('2025/2026', '2025-04-20', '2026-12-19', true, false),
        ];
        $this->command->info('✅ 3 anos letivos criados (2023/2024, 2024/2025 e 2025/2026)');

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

        $areaCiencias = AreaFormacao::firstOrCreate(
            ['nome' => 'Ciencias'],
            [
                'descricao' => 'Cursos da area de ciencias gerais.',
                'ativo' => true,
            ]
        );

        foreach ([$cfb, $cej, $ch] as $curso) {
            if (! $curso->area_formacao_id) {
                $curso->update(['area_formacao_id' => $areaCiencias->id]);
            }
        }

        // =============================================
        // 4. DISCIPLINAS
        // =============================================
        $disc_data = [
            ['nome' => 'Matemática',        'codigo' => 'MAT', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Física',            'codigo' => 'FIS', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Química',           'codigo' => 'QUI', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Biologia',          'codigo' => 'BIO', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Língua Portuguesa', 'codigo' => 'LP',  'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Inglês',            'codigo' => 'ING', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'História',          'codigo' => 'HIS', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Geografia',         'codigo' => 'GEO', 'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Educação Física',   'codigo' => 'EF',  'l10' => true,  'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'TIC',               'codigo' => 'TIC', 'l10' => true,  'l11' => false, 'l12' => false, 'l13' => false, 'ano_terminal' => 10],
            ['nome' => 'Filosofia',         'codigo' => 'FIL', 'l10' => false, 'l11' => true,  'l12' => true,  'l13' => true,  'ano_terminal' => null],
            ['nome' => 'Empreendedorismo',  'codigo' => 'EMP', 'l10' => false, 'l11' => false, 'l12' => true,  'l13' => true,  'ano_terminal' => 12],
        ];

        $disciplinas = [];
        $terminaisPorDisciplina = [];

        foreach ($disc_data as $dd) {
            $disciplinas[$dd['codigo']] = Disciplina::updateOrCreate(
                ['codigo' => $dd['codigo']],
                [
                    'nome'                => $dd['nome'],
                    'leciona_10'          => $dd['l10'],
                    'leciona_11'          => $dd['l11'],
                    'leciona_12'          => $dd['l12'],
                    'leciona_13'          => $dd['l13'],
                    'disciplina_terminal' => $dd['ano_terminal'] !== null,
                    'ativo'               => true,
                ]
            );
            $terminaisPorDisciplina[$dd['codigo']] = $dd['ano_terminal'];
        }
        $this->sincronizarDisciplinasDosCursos([$cfb, $cej, $ch], $disciplinas, $terminaisPorDisciplina);
        $this->command->info('✅ ' . count($disciplinas) . ' disciplinas criadas/atualizadas');

        // =============================================
        // 5. TURMAS COM PROGRESSÃO LETIVA
        // =============================================

        // Mapeamento professor -> disciplina (índice no array $professores)
        // 0=MAT, 1=FIS, 2=QUI, 3=BIO, 4=LP, 5=ING, 6=HIS, 7=GEO, 8=EF, 9=TIC/FIL/EMP
        $professorPorDisciplina = [
            'MAT' => 0,
            'FIS' => 1,
            'QUI' => 2,
            'BIO' => 3,
            'LP'  => 4,
            'ING' => 5,
            'HIS' => 6,
            'GEO' => 7,
            'EF'  => 8,
            'TIC' => 9,
            'FIL' => 6,
            'EMP' => 4,
        ];

        $cursos = [
            ['modelo' => $cfb, 'alunos' => array_slice($alunos, 0, 10),  'coordenador' => 0],
            ['modelo' => $cej, 'alunos' => array_slice($alunos, 10, 10), 'coordenador' => 4],
            ['modelo' => $ch,  'alunos' => array_slice($alunos, 20, 10), 'coordenador' => 6],
        ];

        $planosProgressao = [
            // Coorte A: atualmente na 12ª; tem 10ª em 2023/2024 e 11ª em 2024/2025.
            ['nome' => 'A', 'alunos_inicio' => 0, 'alunos_total' => 4, 'percurso' => ['2023/2024' => '10', '2024/2025' => '11', '2025/2026' => '12']],
            // Coorte B: atualmente na 11ª; tem 10ª em 2024/2025.
            ['nome' => 'B', 'alunos_inicio' => 4, 'alunos_total' => 3, 'percurso' => ['2024/2025' => '10', '2025/2026' => '11']],
            // Coorte C: atualmente na 10ª; não precisa de turma anterior.
            ['nome' => 'C', 'alunos_inicio' => 7, 'alunos_total' => 3, 'percurso' => ['2025/2026' => '10']],
        ];

        $turmasComNotas = [];

        foreach ($cursos as $cursoConfig) {
            foreach ($planosProgressao as $plano) {
                $alunosDaCoorte = array_slice($cursoConfig['alunos'], $plano['alunos_inicio'], $plano['alunos_total']);

                foreach ($plano['percurso'] as $nomeAnoLetivo => $classe) {
                    $anoDaTurma = $anosLetivos[$nomeAnoLetivo];
                    $turma = $this->criarTurmaDemo(
                        $plano['nome'],
                        $classe,
                        $cursoConfig['modelo'],
                        $anoDaTurma,
                        $professores[$cursoConfig['coordenador']]
                    );

                    $codigosDisciplinas = $this->codigosDisciplinasPorClasse($classe, $disciplinas);
                    $turma->disciplinas()->syncWithoutDetaching(
                        collect($codigosDisciplinas)->map(fn ($codigo) => $disciplinas[$codigo]->id)->toArray()
                    );

                    $atribuicoes = collect($codigosDisciplinas)
                        ->map(fn ($codigo) => ['prof' => $professorPorDisciplina[$codigo], 'disc' => $codigo])
                        ->all();
                    $this->atribuirProfessores($turma, $atribuicoes, $professores, $disciplinas, $anoDaTurma->id);

                    foreach ($alunosDaCoorte as $aluno) {
                        $this->matricularAluno($turma, $aluno, $anoDaTurma->data_inicio->toDateString());
                    }

                    $turmasComNotas[] = ['turma' => $turma, 'alunos' => $alunosDaCoorte];
                }
            }
        }

        $this->command->info('✅ ' . count($turmasComNotas) . ' turmas criadas com progressão 10ª→11ª→12ª e alunos matriculados');

        // =============================================
        // 6. NOTAS (1º, 2º e 3º trimestres lançados)
        // =============================================
        $this->command->info('📝 Lançando notas...');

        foreach ($turmasComNotas as $tc) {
            $turma = $tc['turma'];
            $disciplinasDaTurma = $turma->disciplinas()->get();

            foreach ($tc['alunos'] as $aluno) {
                foreach ($disciplinasDaTurma as $disciplina) {
                    $this->criarNota($aluno->id, $turma, $disciplina);
                }
            }
        }

        $totalNotas = Nota::count();
        $this->command->info("✅ {$totalNotas} registos de notas criados (1º, 2º e 3º trimestres)");

        $this->seedAvaliacoesContinuasDemo();
        $this->command->info('✅ Avaliações contínuas de demonstração criadas');

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
     * Cria ou atualiza um ano letivo de demonstração, preservando o estado esperado da linha temporal.
     */
    private function criarAnoLetivoDemo(string $nome, string $dataInicio, string $dataFim, bool $ativo, bool $encerrado): AnoLetivo
    {
        $anoLetivo = AnoLetivo::updateOrCreate(
            ['nome' => $nome],
            [
                'data_inicio' => $dataInicio,
                'data_fim'    => $dataFim,
                'ativo'       => $ativo,
                'encerrado'   => $encerrado,
            ]
        );

        return $anoLetivo->fresh();
    }

    /**
     * Cria uma turma de demonstração sem repetir o mesmo nome completo em diferentes anos.
     */
    private function criarTurmaDemo(string $nome, string $classe, Curso $curso, AnoLetivo $anoLetivo, User $coordenador): Turma
    {
        return Turma::firstOrCreate(
            [
                'nome' => $nome,
                'classe' => $classe,
                'curso_id' => $curso->id,
                'ano_letivo_id' => $anoLetivo->id,
                'turno' => 'M',
            ],
            [
                'coordenador_turma_id' => $coordenador->id,
                'capacidade' => 40,
                'ativo' => true,
            ]
        );
    }


    /**
     * Associa as disciplinas aos cursos de demo e informa a classe terminal.
     */
    private function sincronizarDisciplinasDosCursos(array $cursos, array $disciplinas, array $terminaisPorDisciplina): void
    {
        foreach ($cursos as $curso) {
            $syncData = [];

            foreach ($disciplinas as $codigo => $disciplina) {
                $syncData[$disciplina->id] = [
                    'ano_terminal' => $terminaisPorDisciplina[$codigo],
                ];
            }

            $curso->disciplinas()->syncWithoutDetaching($syncData);
        }
    }

    /**
     * Retorna apenas disciplinas lecionadas na classe informada.
     */
    private function codigosDisciplinasPorClasse(string $classe, array $disciplinas): array
    {
        return collect($disciplinas)
            ->filter(fn (Disciplina $disciplina) => $disciplina->isLecionadaEm($classe))
            ->keys()
            ->values()
            ->all();
    }

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
     * Cria notas com valores realistas para uma turma.
     * Para 11ª e 12ª classes, importa as CA das classes anteriores já semeadas.
     */
    private function criarNota(int $alunoId, Turma $turma, Disciplina $disciplina): void
    {
        $existe = Nota::where('aluno_id', $alunoId)
            ->where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->exists();

        if ($existe) {
            return;
        }

        // Gerar notas com distribuição realista (maioria entre 10-16, alguns <10, poucos >17)
        $base = $this->notaAleatoria();

        // 1º Trimestre
        $mac1 = $this->variar($base, 2);
        $pp1 = $this->variar($base, 3);
        $pt1 = $this->variar($base, 4);
        $mt1 = round(($mac1 * 0.3 + $pp1 * 0.3 + $pt1 * 0.4), 2);

        // 2º Trimestre
        $mac2 = $this->variar($base, 2);
        $pp2 = $this->variar($base, 3);
        $pt2 = $this->variar($base, 4);
        $mt2 = round(($mac2 * 0.3 + $pp2 * 0.3 + $pt2 * 0.4), 2);

        // MFT2 = média entre MT1 e MT2
        $mft2 = round(($mt1 + $mt2) / 2, 2);

        // 3º Trimestre + finais
        $mac3 = $this->variar($base, 2);
        $pp3 = $this->variar($base, 3);
        $pg = $this->variar($base, 4);
        $mt3 = round(($mac3 + $pp3) / 2, 2);
        $cf = round(($mft2 + $mt3) / 2, 2);
        $ca = round((0.6 * $cf) + (0.4 * $pg), 2);

        $classeAtual = (int) $turma->classe;
        $ca10 = $classeAtual >= 11 && $disciplina->leciona_10
            ? $this->caAnterior($alunoId, $disciplina->id, '10')
            : null;
        $ca11 = $classeAtual >= 12 && $disciplina->leciona_11
            ? $this->caAnterior($alunoId, $disciplina->id, '11')
            : null;
        $cfd = $this->calcularCfdDemo($disciplina, $classeAtual, $ca, $ca10, $ca11);

        Nota::create([
            'aluno_id' => $alunoId,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $turma->ano_letivo_id,

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

            // 3º Trimestre + finais
            'mac3' => $mac3,
            'pp3'  => $pp3,
            'mt3'  => $mt3,
            'cf'   => $cf,
            'pg'   => $pg,
            'ca'   => $ca,
            'ca_10' => $ca10,
            'ca_11' => $ca11,
            'cfd'  => $cfd,

            'status' => 'em_lancamento',
        ]);
    }

    /**
     * Busca a CA de uma disciplina numa classe anterior do mesmo aluno.
     */
    private function caAnterior(int $alunoId, int $disciplinaId, string $classe): ?float
    {
        $notaAnterior = Nota::where('aluno_id', $alunoId)
            ->where('disciplina_id', $disciplinaId)
            ->whereHas('turma', fn ($query) => $query->where('classe', $classe))
            ->whereNotNull('ca')
            ->orderByDesc('ano_letivo_id')
            ->orderByDesc('id')
            ->first();

        return $notaAnterior?->ca !== null ? (float) $notaAnterior->ca : null;
    }

    /**
     * Calcula a CFD seguindo a mesma regra das CA disponíveis por classe.
     */
    private function calcularCfdDemo(Disciplina $disciplina, int $classeAtual, float $ca, ?float $ca10, ?float $ca11): ?float
    {
        $classificacoes = [];

        if ($disciplina->leciona_10 && $classeAtual >= 10) {
            if ($classeAtual === 10) {
                $classificacoes[] = $ca;
            } elseif ($ca10 !== null) {
                $classificacoes[] = $ca10;
            } else {
                return null;
            }
        }

        if ($disciplina->leciona_11 && $classeAtual >= 11) {
            if ($classeAtual === 11) {
                $classificacoes[] = $ca;
            } elseif ($ca11 !== null) {
                $classificacoes[] = $ca11;
            } else {
                return null;
            }
        }

        if ($disciplina->leciona_12 && $classeAtual >= 12) {
            $classificacoes[] = $ca;
        }

        if (empty($classificacoes)) {
            $classificacoes[] = $ca;
        }

        return round(array_sum($classificacoes) / count($classificacoes), 2);
    }


    private function seedAvaliacoesContinuasDemo(): void
    {
        $professorPadrao = User::whereHas('role', fn ($q) => $q->where('name', 'professor'))->first();

        if (! $professorPadrao) {
            return;
        }

        Nota::query()->limit(150)->get()->each(function (Nota $nota) use ($professorPadrao) {
            foreach ([1, 2, 3] as $trimestre) {
                $campoMac = 'mac'.$trimestre;
                $mac = $nota->{$campoMac};

                if ($mac === null) {
                    continue;
                }

                $jaExiste = AvaliacaoContinua::where('nota_id', $nota->id)
                    ->where('trimestre', $trimestre)
                    ->exists();

                if ($jaExiste) {
                    continue;
                }

                $valor1 = max(0, min(20, round(((float) $mac) - 0.5, 2)));
                $valor2 = max(0, min(20, round(((float) $mac) + 0.5, 2)));

                AvaliacaoContinua::create([
                    'nota_id' => $nota->id,
                    'professor_id' => $professorPadrao->id,
                    'trimestre' => $trimestre,
                    'descricao' => "AC {$trimestre}.1",
                    'valor' => $valor1,
                    'data_avaliacao' => now()->subDays(15),
                ]);

                AvaliacaoContinua::create([
                    'nota_id' => $nota->id,
                    'professor_id' => $professorPadrao->id,
                    'trimestre' => $trimestre,
                    'descricao' => "AC {$trimestre}.2",
                    'valor' => $valor2,
                    'data_avaliacao' => now()->subDays(5),
                ]);
            }
        });
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

<?php

namespace Database\Seeders;

use App\Models\AnoLetivo;
use App\Models\AreaFormacao;
use App\Models\AvaliacaoContinua;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────
        // ROLES
        // ─────────────────────────────────────────
        $roles = Role::all()->keyBy('name');

        // ─────────────────────────────────────────
        // UTILIZADORES DE SISTEMA
        // ─────────────────────────────────────────
        User::firstOrCreate(['email' => 'admin@escola.ao'], [
            'name'     => 'Administrador do Sistema',
            'password' => Hash::make('password'),
            'role_id'  => $roles['admin']->id,
            'ativo'    => true,
        ]);

        User::firstOrCreate(['email' => 'secretaria@escola.ao'], [
            'name'     => 'Fátima Cardoso',
            'password' => Hash::make('password'),
            'role_id'  => $roles['secretaria']->id,
            'telefone' => '923456789',
            'genero'   => 'F',
            'ativo'    => true,
        ]);

        // ─────────────────────────────────────────
        // DISCIPLINAS DA COMPONENTE GERAL (comuns a todos os cursos)

$nomesProfessores = [
    'João Manuel',
    'Paulo Pedro',
    'Carlos António',
    'Domingos José',
    'Mateus Francisco',
    'Pedro Armando',
    'Adriano Manuel',
    'José Luís',
    'António Miguel',
    'Fernando Sapalo',
    'Cristóvão Manuel',
    'Samuel Bernardo',
    'Manuel Joaquim',
    'Alberto Pascoal',
    'Rui António',
    'Ana Paula',
    'Maria de Fátima',
    'Helena Domingos',
    'Sandra Manuel',
    'Carla António',
    'Paula Bento',
    'Sónia Mateus',
    'Isabel Pascoal',
    'Lurdes Francisco',
    'Joana Pedro',
    'Cristina Manuel',
];

$nomesAlunos = [
    'Afonso Manuel',
    'Albino António',
    'Amélia Domingos',
    'Ana Paula',
    'André Francisco',
    'António Mateus',
    'Armindo Pedro',
    'Belmiro José',
    'Bruno Manuel',
    'Carlos Alberto',
    'Celma Pedro',
    'Cristina Manuel',
    'Daniel António',
    'David Domingos',
    'Edson Pedro',
    'Elisa Manuel',
    'Emília Francisco',
    'Fernando José',
    'Filipe Manuel',
    'Francisca António',
    'Gabriel Pedro',
    'Helena Domingos',
    'Henrique Manuel',
    'Inês Mateus',
    'Isaura Pedro',
    'João Manuel',
    'Joana António',
    'Joaquim Domingos',
    'José Pedro',
    'Lázaro Manuel',
    'Leonor Francisco',
    'Luís António',
    'Manuel Bernardo',
    'Maria da Conceição',
    'Mário Domingos',
    'Mateus Pedro',
    'Natália Manuel',
    'Nelson António',
    'Osvaldo Francisco',
    'Patrícia Domingos',
    'Paulo Manuel',
    'Rafael António',
    'Rosa Pedro',
    'Samuel Domingos',
    'Sandra Manuel',
    'Sérgio Pedro',
    'Teresa António',
    'Valdemar José',
    'Victor Manuel',
    'Yuri António',
];

$nomesEncarregados = [
    'José Manuel',
    'Maria José',
    'Pedro António',
    'Ana Domingos',
    'Carlos Mateus',
    'Helena Francisco',
    'Paulo Pedro',
    'Rosa Manuel',
    'Domingos António',
    'Lurdes Pascoal',
    'Joaquim Manuel',
    'Cristina Pedro',
    'Fernando António',
    'Joana Mateus',
    'Adriano Francisco',
];
        // ─────────────────────────────────────────
        $disciplinasGerais = [
            ['MAT', 'Matemática',           'M'],
            ['FIS', 'Física',                'F'],
            ['LP',  'Língua Portuguesa',     'M'],
            ['ING', 'Inglês',                'F'],
            ['HIS', 'História',              'M'],
            ['EF',  'Educação Física',       'F'],
        ];

        // ─────────────────────────────────────────
        // DISCIPLINAS DA COMPONENTE TÉCNICA, POR CURSO
        // ─────────────────────────────────────────
        $disciplinasTecnicasPorCurso = [
            'OCC' => [
                ['OCC_DT',  'Desenho Técnico',                 'M'],
                ['OCC_TCC', 'Tecnologia da Construção Civil',  'M'],
                ['OCC_MC',  'Materiais de Construção',         'F'],
                ['OCC_TOP', 'Topografia',                      'M'],
            ],
            'DP' => [
                ['DP_DT',    'Desenho Técnico',                  'M'],
                ['DP_GD',    'Geometria Descritiva',              'F'],
                ['DP_CAD',   'Desenho Assistido por Computador',  'M'],
                ['DP_CONST', 'Construções',                       'F'],
            ],
            'FC' => [
                ['FC_TF', 'Tecnologia do Frio',            'M'],
                ['FC_EE', 'Electricidade e Electrónica',   'F'],
                ['FC_IC', 'Instalações de Climatização',   'M'],
                ['FC_MA', 'Mecânica Aplicada',              'F'],
            ],
            'GSI' => [
                ['GSI_PROG', 'Programação',                'M'],
                ['GSI_BD',   'Base de Dados',                'F'],
                ['GSI_RC',   'Redes de Computadores',        'M'],
                ['GSI_SO',   'Sistemas Operativos',          'F'],
            ],
            'INF' => [
                // INF partilha Programação e Sistemas Operativos com o GSI
                ['GSI_PROG', 'Programação',                'M'],
                ['GSI_SO',   'Sistemas Operativos',          'F'],
                ['INF_AI',   'Aplicações Informáticas',      'M'],
                ['INF_MM',   'Multimédia',                    'F'],
            ],
        ];

        // ─────────────────────────────────────────
        // PROFESSORES (gerais + técnicos, sem duplicar códigos partilhados)
        // ─────────────────────────────────────────
        $todasDisciplinas = $disciplinasGerais;
        foreach ($disciplinasTecnicasPorCurso as $tecnicas) {
            foreach ($tecnicas as $d) {
                $todasDisciplinas[$d[0]] = $d; // chave = código, remove duplicados automaticamente
            }
        }
        $todasDisciplinas = array_values($todasDisciplinas);

        $professores = [];
        $i = 0;
        foreach ($todasDisciplinas as [$cod, $nome, $genero]) {
        $i++;

        $nomeProfessor = $nomesProfessores[($i - 1) % count($nomesProfessores)];

        $email = 'prof.' . strtolower(str_replace('_', '', $cod)) . '@escola.ao';

        $professores[$cod] = User::firstOrCreate(['email' => $email], [
            'name' => $nomeProfessor,
                'password' => Hash::make('password'),
                'role_id'  => $roles['professor']->id,
                'bi'       => '006' . str_pad((string) $i, 9, '0', STR_PAD_LEFT) . 'LA041',
                'telefone' => '923' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'genero'   => $genero,
                'ativo'    => true,
            ]);
        }

        // ─────────────────────────────────────────
        // ANO LECTIVO (único, activo, não encerrado, a meio do 2º trimestre)
        // ─────────────────────────────────────────
        AnoLetivo::query()->update(['ativo' => false]);

        $anoLetivo = AnoLetivo::updateOrCreate(
            ['nome' => '2025/2026'],
            [
                'data_inicio' => '2025-09-01',
                'data_fim'    => '2026-07-31',
                'encerrado'   => false,
                'ativo'       => true,
            ]
        );

        // ─────────────────────────────────────────
        // ÁREA DE FORMAÇÃO
        // ─────────────────────────────────────────
        $area = AreaFormacao::firstOrCreate(['nome' => 'Informática e Construção Civil'], [
            'descricao' => 'Cursos técnico-profissionais do politécnico.',
            'ativo'     => true,
        ]);

        // ─────────────────────────────────────────
        // CURSOS
        // ─────────────────────────────────────────
        // 'turmas' => lista de classes em que o curso terá turma própria
        $cursosData = [
            'OCC' => ['nome' => 'Obras de Construção Civil',      'turmas' => [10]],
            'DP'  => ['nome' => 'Desenhador Projectista',          'turmas' => [10]],
            'FC'  => ['nome' => 'Frio e Climatização',              'turmas' => [10]],
            'GSI' => ['nome' => 'Gestão de Sistemas Informáticos', 'turmas' => [10, 11]],
            'INF' => ['nome' => 'Informática',                      'turmas' => [10]],
        ];

        $cursos = [];
        foreach ($cursosData as $cod => $info) {
            $cursos[$cod] = Curso::firstOrCreate(['codigo' => $cod], [
                'nome'             => $info['nome'],
                'area_formacao_id' => $area->id,
                'coordenador_id'   => $professores['MAT']->id,
                'ativo'            => true,
            ]);
            $cursos[$cod]->update(['area_formacao_id' => $area->id]);
        }

        // ─────────────────────────────────────────
        // CRIAR DISCIPLINAS (gerais + técnicas) E ASSOCIAR AO CURSO
        // ─────────────────────────────────────────
        $disciplinas = []; // código => model Disciplina

        // Disciplinas gerais — leccionadas em todas as classes/cursos
        foreach ($disciplinasGerais as [$cod, $nome, $genero]) {
            $disciplinas[$cod] = Disciplina::updateOrCreate(['codigo' => $cod], [
                'nome'                => $nome,
                'leciona_10'          => 1,
                'leciona_11'          => 1,
                'leciona_12'          => 1,
                'leciona_13'          => 0,
                'disciplina_terminal' => true,
                'ativo'               => true,
            ]);
        }

        // Disciplinas técnicas — por curso
        foreach ($disciplinasTecnicasPorCurso as $cursoCod => $tecnicas) {
            foreach ($tecnicas as [$cod, $nome, $genero]) {
                if (!isset($disciplinas[$cod])) {
                    $disciplinas[$cod] = Disciplina::updateOrCreate(['codigo' => $cod], [
                        'nome'                => $nome,
                        'leciona_10'          => 1,
                        'leciona_11'          => 1,
                        'leciona_12'          => 1,
                        'leciona_13'          => 0,
                        'disciplina_terminal' => true,
                        'ativo'               => true,
                    ]);
                }
            }
        }

        // Lista de disciplinas (códigos) por curso = gerais + técnicas do curso
        $discsPorCurso = [];
        foreach ($cursosData as $cursoCod => $info) {
            $codsGerais   = array_map(fn ($d) => $d[0], $disciplinasGerais);
            $codsTecnicas = array_map(fn ($d) => $d[0], $disciplinasTecnicasPorCurso[$cursoCod]);
            $discsPorCurso[$cursoCod] = array_values(array_unique(array_merge($codsGerais, $codsTecnicas)));

            foreach ($discsPorCurso[$cursoCod] as $cod) {
                $cursos[$cursoCod]->disciplinas()->syncWithoutDetaching([
                    $disciplinas[$cod]->id => ['ano_terminal' => 12],
                ]);
            }
        }

        // ─────────────────────────────────────────
        // TURMAS, ALUNOS, PROFESSOR_TURMA_DISCIPLINA, MATRÍCULAS E NOTAS
        // ─────────────────────────────────────────
        $alunoSeq = 1;

        foreach ($cursosData as $cursoCod => $info) {
            foreach ($info['turmas'] as $classe) {

                // Turma
                $turma = Turma::firstOrCreate([
                    'nome'          => 'A',
                    'classe'        => (string) $classe,
                    'curso_id'      => $cursos[$cursoCod]->id,
                    'ano_letivo_id' => $anoLetivo->id,
                ], [
                    'coordenador_turma_id' => $professores['MAT']->id,
                    'capacidade'           => 40,
                    'turno'                => 'M',
                    'ativo'                => true,
                ]);

                // Disciplinas da turma = disciplinas do curso
                $discIds = array_map(fn ($cod) => $disciplinas[$cod]->id, $discsPorCurso[$cursoCod]);
                $turma->disciplinas()->sync($discIds);

                // Professores ↔ turma ↔ disciplina
                DB::table('professor_turma_disciplina')
                    ->where('turma_id', $turma->id)
                    ->where('ano_letivo_id', $anoLetivo->id)
                    ->whereNotIn('disciplina_id', $discIds)
                    ->delete();

                foreach ($discsPorCurso[$cursoCod] as $cod) {
                    DB::table('professor_turma_disciplina')->updateOrInsert(
                        [
                            'professor_id'  => $professores[$cod]->id,
                            'turma_id'      => $turma->id,
                            'disciplina_id' => $disciplinas[$cod]->id,
                            'ano_letivo_id' => $anoLetivo->id,
                        ],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                // Alunos da turma — 10 por turma
                $alunosDaTurma = [];
                for ($n = 1; $n <= 10; $n++) {
                    $nomeAluno = $nomesAlunos[($alunoSeq - 1) % count($nomesAlunos)];
                    $encarregado = $nomesEncarregados[($alunoSeq - 1) % count($nomesEncarregados)];
                    $aluno = User::firstOrCreate(['email' => "aluno{$alunoSeq}@escola.ao"], [
                        'name' => $nomeAluno,
                        'password'             => Hash::make('password'),
                        'role_id'              => $roles['aluno']->id,
                        'numero_processo'      => '2025' . str_pad((string) $alunoSeq, 3, '0', STR_PAD_LEFT),
                        'bi'                   => '007' . str_pad((string) $alunoSeq, 9, '0', STR_PAD_LEFT) . 'LA041',
                        'data_nascimento'      => '2008-01-01',
                        'genero'               => $alunoSeq % 2 === 0 ? 'F' : 'M',
                        'telefone'             => '924' . str_pad((string) $alunoSeq, 6, '0', STR_PAD_LEFT),
                        'nome_encarregado' => $encarregado,
                        'contacto_encarregado' => '923' . str_pad((string) (300000 + $alunoSeq), 6, '0', STR_PAD_LEFT),
                        'ativo'                => true,
                    ]);
                    $alunosDaTurma[] = $aluno;
                    $alunoSeq++;
                }

                // ─────────────────────────────────
                // MATRÍCULAS + NOTAS (ano lectivo parado no 2º trimestre)
                // ─────────────────────────────────
                foreach ($alunosDaTurma as $idx => $aluno) {

                    DB::table('turma_aluno')->updateOrInsert(
                        ['turma_id' => $turma->id, 'aluno_id' => $aluno->id],
                        [
                            'data_matricula' => '2025-09-01',
                            'status'         => 'matriculado', // ainda em curso, não há aprovados/reprovados a meio do ano
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]
                    );

                    // Base de nota variando ligeiramente por aluno
                    $base = 11.5 + (($idx % 5) - 2);

                    foreach ($discsPorCurso[$cursoCod] as $cod) {
                        $discId = $disciplinas[$cod]->id;
                        $mac    = (float) max(0, min(20, round($base, 2)));

                        $nota = Nota::updateOrCreate(
                            [
                                'aluno_id'      => $aluno->id,
                                'turma_id'      => $turma->id,
                                'disciplina_id' => $discId,
                                'ano_letivo_id' => $anoLetivo->id,
                            ],
                            [
                                // 1.º trimestre — concluído
                                'mac1' => $mac,
                                'pp1'  => $mac,
                                'pt1'  => $mac,
                                'mt1'  => $mac,
                                // 2.º trimestre — em curso/concluído até onde já avaliado
                                'mac2' => $mac,
                                'pp2'  => $mac,
                                'pt2'  => $mac,
                                'mt2'  => $mac,
                                'mft2' => $mac,
                                // 3.º trimestre — ainda não iniciado
                                'mac3' => null,
                                'pp3'  => null,
                                'pt3'  => null,
                                'pg'   => null,
                                'mt3'  => null,
                                // Classificações finais — ainda não apuradas
                                'cf'           => null,
                                'ca'           => null,
                                'cfd'          => null,
                                'nota_recurso' => null,
                                'status'       => 'em_lancamento',
                            ]
                        );

                        // ─────────────────────────────────
                        // AVALIAÇÕES CONTÍNUAS (2 por trimestre, só 1.º e 2.º)
                        // ─────────────────────────────────
                        foreach ([1, 2] as $tri) {
                            $macTri = (float) $nota->{'mac' . $tri};

                            AvaliacaoContinua::updateOrCreate(
                                [
                                    'nota_id'   => $nota->id,
                                    'trimestre' => $tri,
                                    'descricao' => "AC {$tri}.1",
                                ],
                                [
                                    'professor_id'   => $professores[$cod]->id,
                                    'valor'          => max(0, min(20, round($macTri - 0.5, 2))),
                                    'data_avaliacao' => now()->subDays(20),
                                ]
                            );

                            AvaliacaoContinua::updateOrCreate(
                                [
                                    'nota_id'   => $nota->id,
                                    'trimestre' => $tri,
                                    'descricao' => "AC {$tri}.2",
                                ],
                                [
                                    'professor_id'   => $professores[$cod]->id,
                                    'valor'          => max(0, min(20, round($macTri + 0.5, 2))),
                                    'data_avaliacao' => now()->subDays(10),
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
}
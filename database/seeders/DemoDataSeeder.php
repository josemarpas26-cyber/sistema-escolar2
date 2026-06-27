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
        // PROFESSORES
        // ─────────────────────────────────────────
        $profDados = [
            ['Matemática',   'prof.mat@escola.ao',  'M'],
            ['Física',       'prof.fis@escola.ao',  'F'],
            ['Química',      'prof.qui@escola.ao',  'M'],
            ['Biologia',     'prof.bio@escola.ao',  'F'],
            ['Português',    'prof.port@escola.ao', 'M'],
            ['Inglês',       'prof.ing@escola.ao',  'F'],
            ['História',     'prof.hist@escola.ao', 'M'],
            ['Geografia',    'prof.geo@escola.ao',  'F'],
            ['Educação Física', 'prof.ef@escola.ao','M'],
            ['TIC',          'prof.tic@escola.ao',  'F'],
        ];

        $professores = [];
        foreach ($profDados as $i => [$nome, $email, $genero]) {
            $professores[] = User::firstOrCreate(['email' => $email], [
                'name'     => "Prof. {$nome}",
                'password' => Hash::make('password'),
                'role_id'  => $roles['professor']->id,
                'bi'       => '006' . str_pad((string) ($i + 1), 9, '0', STR_PAD_LEFT) . 'LA041',
                'telefone' => '923' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'genero'   => $genero,
                'ativo'    => true,
            ]);
        }

        // ─────────────────────────────────────────
        // ALUNOS  (30 alunos para a turma 10-A)
        // ─────────────────────────────────────────
        $alunos = [];
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = User::firstOrCreate(['email' => "aluno{$i}@escola.ao"], [
                'name'               => "Aluno {$i}",
                'password'           => Hash::make('password'),
                'role_id'            => $roles['aluno']->id,
                'numero_processo'    => '2024' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'bi'                 => '007' . str_pad((string) $i, 9, '0', STR_PAD_LEFT) . 'LA041',
                'data_nascimento'    => '2008-01-01',
                'genero'             => $i % 2 === 0 ? 'F' : 'M',
                'telefone'           => '924' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'nome_encarregado'   => "Encarregado {$i}",
                'contacto_encarregado' => '923' . str_pad((string) (300000 + $i), 6, '0', STR_PAD_LEFT),
                'ativo'              => true,
            ]);
        }

        // ─────────────────────────────────────────
        // ANO LECTIVO  (único, activo, não encerrado)
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
        // ÁREA DE FORMAÇÃO + CURSO
        // ─────────────────────────────────────────
        $area = AreaFormacao::firstOrCreate(['nome' => 'Informática de gestão'], [
            'descricao' => 'Cursos da área de ciências gerais.',
            'ativo'     => true,
        ]);

        $cfb = Curso::firstOrCreate(['codigo' => 'GSI'], [
            'nome'              => 'Gestão de sistemas informáticos',
            'area_formacao_id'  => $area->id,
            'coordenador_id'    => $professores[0]->id,
            'ativo'             => true,
        ]);

        $cfb->update(['area_formacao_id' => $area->id]);

        // ─────────────────────────────────────────
        // DISCIPLINAS DA 10ª CLASSE
        // ─────────────────────────────────────────
        $discData10 = [
            //  código  nome                   l10 l11 l12 l13 terminal  prof_idx
            ['MAT',  'Matemática',              1,  1,  1,  0,  true,   0],
            ['FIS',  'Física',                  1,  1,  1,  0,  true,   1],
            ['QUI',  'Química',                 1,  1,  1,  0,  true,   2],
            ['BIO',  'Biologia',                1,  1,  1,  0,  true,   3],
            ['LP',   'Língua Portuguesa',       1,  1,  1,  0,  true,   4],
            ['ING',  'Inglês',                  1,  1,  1,  0,  true,   5],
            ['HIS',  'História',                1,  1,  1,  0,  true,   6],
            ['GEO',  'Geografia',               1,  1,  1,  0,  true,   7],
            ['EF',   'Educação Física',         1,  1,  1,  0,  true,   8],
            ['TIC',  'TIC',                     1,  0,  0,  0,  true,   9],
        ];

        $disciplinas = [];
        $profMap     = [];

        foreach ($discData10 as [$cod, $nome, $l10, $l11, $l12, $l13, $terminal, $profIdx]) {
            $disciplinas[$cod] = Disciplina::updateOrCreate(['codigo' => $cod], [
                'nome'                 => $nome,
                'leciona_10'           => $l10,
                'leciona_11'           => $l11,
                'leciona_12'           => $l12,
                'leciona_13'           => $l13,
                'disciplina_terminal'  => $terminal,
                'ativo'                => true,
            ]);
            $profMap[$cod] = $profIdx;
        }

        // Associar disciplinas ao curso
        $anoTerminalPorDisc = [
            'MAT' => 12, 'FIS' => 12, 'QUI' => 12, 'BIO' => 12,
            'LP'  => 12, 'ING' => 12, 'HIS' => 12, 'GEO' => 12,
            'EF'  => 12, 'TIC' => 10,
        ];

        foreach ($disciplinas as $cod => $disc) {
            $cfb->disciplinas()->syncWithoutDetaching([
                $disc->id => ['ano_terminal' => $anoTerminalPorDisc[$cod] ?? null],
            ]);
        }

        // ─────────────────────────────────────────
        // TURMA  10-A
        // ─────────────────────────────────────────
        $turma = Turma::firstOrCreate([
            'nome'          => 'A',
            'classe'        => '10',
            'curso_id'      => $cfb->id,
            'ano_letivo_id' => $anoLetivo->id,
        ], [
            'coordenador_turma_id' => $professores[0]->id,
            'capacidade'           => 40,
            'turno'                => 'M',
            'ativo'                => true,
        ]);

        // Disciplinas na turma
        $discIds = array_map(fn ($d) => $d->id, array_values($disciplinas));
        $turma->disciplinas()->sync($discIds);

        // Professores na turma
        DB::table('professor_turma_disciplina')
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->whereNotIn('disciplina_id', $discIds)
            ->delete();

        foreach ($disciplinas as $cod => $disc) {
            DB::table('professor_turma_disciplina')->updateOrInsert(
                [
                    'professor_id'  => $professores[$profMap[$cod]]->id,
                    'turma_id'      => $turma->id,
                    'disciplina_id' => $disc->id,
                    'ano_letivo_id' => $anoLetivo->id,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // ─────────────────────────────────────────
        // MATRÍCULAS + NOTAS DOS ALUNOS
        // ─────────────────────────────────────────
        foreach ($alunos as $idx => $aluno) {

            // Status variado para ter dados realistas
            $status = match (true) {
                in_array($idx, [1, 9], true)  => 'reprovado',
                in_array($idx, [3, 12], true) => 'recurso',
                $idx === 5                    => 'aprovado',
                default                       => 'matriculado',
            };

            DB::table('turma_aluno')->updateOrInsert(
                ['turma_id' => $turma->id, 'aluno_id' => $aluno->id],
                [
                    'data_matricula' => '2024-09-01',
                    'status'         => $status,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );

            // Base de nota por perfil do aluno
            $base = match (true) {
                in_array($idx, [1, 9], true)  => 8.2,   // reprovados
                in_array($idx, [3, 12], true) => 9.0,   // recurso
                default                       => 11.5 + (($idx % 5) - 2),
            };

            foreach ($discIds as $discId) {

                $mac  = (float) max(0, min(20, round($base,        2)));
                $pt3  = (float) max(0, min(20, round($base,        2)));
                $cf   = (float) max(0, min(20, round($base,        2)));
                $cfd  = (float) max(0, min(20, round($base,        2)));

                // Nota de recurso apenas para os alunos em recurso
                $notaRecurso = match ($idx) {
                    3  => 11.2,
                    12 => 9.3,
                    default => null,
                };

                $nota = Nota::updateOrCreate(
                    [
                        'aluno_id'      => $aluno->id,
                        'turma_id'      => $turma->id,
                        'disciplina_id' => $discId,
                        'ano_letivo_id' => $anoLetivo->id,
                    ],
                    [
                        // 1.º trimestre
                        'mac1' => $mac,
                        'pp1'  => $mac,
                        'pt1'  => $mac,
                        'mt1'  => $mac,
                        // 2.º trimestre
                        'mac2' => $mac,
                        'pp2'  => $mac,
                        'pt2'  => $mac,
                        'mt2'  => $mac,
                        'mft2' => $mac,
                        // 3.º trimestre
                        'mac3' => $mac,
                        'pp3'  => $mac,
                        'pt3'  => $pt3,
                        'pg'   => null,
                        'mt3'  => $mac,
                        // Classificações finais
                        'cf'          => $cf,
                        'ca'          => $cf,
                        'cfd'         => $cfd,
                        'nota_recurso'=> $notaRecurso,
                        'status'      => 'finalizado',
                    ]
                );

                // ─────────────────────────────────
                // AVALIAÇÕES CONTÍNUAS (2 por trimestre)
                // ─────────────────────────────────
                foreach ([1, 2, 3] as $tri) {
                    $macTri = (float) $nota->{'mac' . $tri};

                    AvaliacaoContinua::updateOrCreate(
                        [
                            'nota_id'   => $nota->id,
                            'trimestre' => $tri,
                            'descricao' => "AC {$tri}.1",
                        ],
                        [
                            'professor_id'   => $professores[$profMap[
                                array_search($discId, array_map(fn ($d) => $d->id, $disciplinas))
                                    ?: array_key_first($profMap)
                            ] ?? $professores[0]->id]->id
                                ?? $professores[0]->id,
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
                            'professor_id'   => $professores[0]->id,
                            'valor'          => max(0, min(20, round($macTri + 0.5, 2))),
                            'data_avaliacao' => now()->subDays(10),
                        ]
                    );
                }
            }
        }
    }
}
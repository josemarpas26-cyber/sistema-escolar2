<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\NotaLog;
use App\Models\Permission;
use App\Models\ProfessorTurmaDisciplina;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaPautaTest extends TestCase
{
    use RefreshDatabase;

    public function test_lancar_terceiro_trimestre_calcula_cfd_automaticamente(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica([
            'classe' => '10',
            'disciplina' => [
                'leciona_10' => true,
                'leciona_11' => true,
                'leciona_12' => true,
            ],
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mac1' => 12,
            'pp1' => 12,
            'pt1' => 12,
            'mac2' => 15,
            'pp2' => 15,
            'pt2' => 15,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($professor)
            ->post(route('notas.lancarTrimestre', 3), [
                'notas' => [
                    [
                        'id' => $nota->id,
                        'mac3' => 14,
                        'pp3' => 16,
                        'pg' => 18,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $nota->refresh();

        $this->assertEquals(12.00, (float) $nota->mt1);
        $this->assertEquals(15.00, (float) $nota->mt2);
        $this->assertEquals(13.50, (float) $nota->mft2);
        $this->assertEquals(15.00, (float) $nota->mt3);
        $this->assertEquals(14.25, (float) $nota->cf);
        $this->assertEquals(15.75, (float) $nota->ca);
        $this->assertEquals(15.75, (float) $nota->cfd);
    }

    public function test_aluno_matriculado_no_segundo_trimestre_calcula_media_sem_primeiro_trimestre(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica([
            'classe' => '10',
            'disciplina' => [
                'leciona_10' => true,
                'leciona_11' => true,
                'leciona_12' => true,
            ],
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2026-01-15',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mac2' => 15,
            'pp2' => 15,
            'pt2' => 15,
            'status' => 'em_lancamento',
        ]);

        $this
            ->actingAs($professor)
            ->post(route('notas.lancarTrimestre', 3), [
                'notas' => [
                    [
                        'id' => $nota->id,
                        'mac3' => 14,
                        'pp3' => 16,
                        'pg' => 18,
                    ],
                ],
            ])
            ->assertRedirect();

        $nota->refresh();

        $this->assertNull($nota->mt1);
        $this->assertEquals(15.00, (float) $nota->mt2);
        $this->assertEquals(15.00, (float) $nota->mft2);
        $this->assertEquals(15.00, (float) $nota->mt3);
        $this->assertEquals(15.00, (float) $nota->cf);
        $this->assertEquals(16.20, (float) $nota->ca);
        $this->assertEquals(16.20, (float) $nota->cfd);
    }

    public function test_matricula_regular_nao_ignora_primeiro_trimestre_automaticamente(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica([
            'classe' => '10',
            'disciplina' => [
                'leciona_10' => true,
                'leciona_11' => true,
                'leciona_12' => true,
            ],
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mac2' => 15,
            'pp2' => 15,
            'pt2' => 15,
            'status' => 'em_lancamento',
        ]);

        $this
            ->actingAs($professor)
            ->post(route('notas.lancarTrimestre', 3), [
                'notas' => [
                    [
                        'id' => $nota->id,
                        'mac3' => 14,
                        'pp3' => 16,
                        'pg' => 18,
                    ],
                ],
            ])
            ->assertRedirect();

        $nota->refresh();

        $this->assertNull($nota->mt1);
        $this->assertEquals(15.00, (float) $nota->mt2);
        $this->assertNull($nota->mft2);
        $this->assertEquals(15.00, (float) $nota->mt3);
        $this->assertNull($nota->cf);
        $this->assertNull($nota->ca);
        $this->assertNull($nota->cfd);
    }

    public function test_finalizar_e_reabrir_respeitam_aluno_e_trimestre(): void
    {
        $secretariaRole = $this->createRoleWithPermissions('secretaria', ['notas.editar', 'notas.reabrir']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $secretaria = User::factory()->create(['role_id' => $secretariaRole->id]);
        $aluno1 = User::factory()->create(['role_id' => $alunoRole->id]);
        $aluno2 = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica();

        $nota1 = Nota::create([
            'aluno_id' => $aluno1->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
        ]);

        $nota2 = Nota::create([
            'aluno_id' => $aluno2->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
        ]);

        $this
            ->actingAs($secretaria)
            ->post(route('notas.finalizar'), [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
                'trimestre' => '1',
                'aluno_id' => $aluno1->id,
            ])
            ->assertRedirect();

        $nota1->refresh();
        $nota2->refresh();

        $this->assertTrue($nota1->bloqueado_t1);
        $this->assertFalse($nota2->bloqueado_t1);
        $this->assertSame('em_lancamento', $nota1->status);
        $this->assertSame('em_lancamento', $nota2->status);

        $nota1->update([
            'status' => 'finalizado',
            'bloqueado_t1' => true,
            'bloqueado_t2' => true,
            'bloqueado_t3' => true,
        ]);

        $nota2->update([
            'status' => 'finalizado',
            'bloqueado_t1' => true,
            'bloqueado_t2' => true,
            'bloqueado_t3' => true,
        ]);

        $this
            ->actingAs($secretaria)
            ->post(route('notas.reabrir'), [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
                'trimestre' => '2',
                'aluno_id' => $aluno1->id,
            ])
            ->assertRedirect();

        $nota1->refresh();
        $nota2->refresh();

        $this->assertSame('em_lancamento', $nota1->status);
        $this->assertTrue($nota1->bloqueado_t1);
        $this->assertFalse($nota1->bloqueado_t2);
        $this->assertTrue($nota1->bloqueado_t3);

        $this->assertSame('finalizado', $nota2->status);
        $this->assertTrue($nota2->bloqueado_t1);
        $this->assertTrue($nota2->bloqueado_t2);
        $this->assertTrue($nota2->bloqueado_t3);
    }

    public function test_operacoes_gerais_da_pauta_criam_um_unico_log_global(): void
    {
        $secretariaRole = $this->createRoleWithPermissions('secretaria', ['notas.editar', 'notas.reabrir']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $secretaria = User::factory()->create(['role_id' => $secretariaRole->id]);
        $aluno1 = User::factory()->create(['role_id' => $alunoRole->id]);
        $aluno2 = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica();

        $nota1 = Nota::create([
            'aluno_id' => $aluno1->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
        ]);

        $nota2 = Nota::create([
            'aluno_id' => $aluno2->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
        ]);

        $this
            ->actingAs($secretaria)
            ->post(route('notas.finalizar'), [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
            ])
            ->assertRedirect();

        $nota1->refresh();
        $nota2->refresh();

        $this->assertSame('finalizado', $nota1->status);
        $this->assertSame('finalizado', $nota2->status);
        $this->assertCount(1, NotaLog::where('acao', 'finalizacao')->get());

        $logFinalizacao = NotaLog::where('acao', 'finalizacao')->firstOrFail();
        $this->assertTrue($logFinalizacao->acao_global);
        $this->assertSame('pauta_completa', $logFinalizacao->campo_alterado);

        $this
            ->actingAs($secretaria)
            ->post(route('notas.reabrir'), [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
                'trimestre' => '2',
            ])
            ->assertRedirect();

        $nota1->refresh();
        $nota2->refresh();

        $this->assertSame('em_lancamento', $nota1->status);
        $this->assertSame('em_lancamento', $nota2->status);
        $this->assertFalse($nota1->bloqueado_t2);
        $this->assertFalse($nota2->bloqueado_t2);
        $this->assertCount(1, NotaLog::where('acao', 'reabertura')->get());

        $logReabertura = NotaLog::where('acao', 'reabertura')->firstOrFail();
        $this->assertTrue($logReabertura->acao_global);
        $this->assertSame('bloqueado_t2', $logReabertura->campo_alterado);
    }

    public function test_tela_do_professor_bloqueia_apenas_o_trimestre_fechado(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica();

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
            'bloqueado_t1' => true,
            'bloqueado_t2' => false,
            'bloqueado_t3' => true,
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('notas.professor-index', [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
            ]));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertTrue($this->inputHasAttribute($html, 'notas[0][mac1]', 'disabled'));
        $this->assertFalse($this->inputHasAttribute($html, 'notas[0][mac2]', 'disabled'));
        $this->assertTrue($this->inputHasAttribute($html, 'notas[0][mac3]', 'disabled'));
    }

    public function test_aluno_do_segundo_trimestre_nao_pode_receber_notas_no_primeiro(): void
    {
        $professorRole = $this->createRoleWithPermissions('professor', ['notas.lancar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $professor = User::factory()->create(['role_id' => $professorRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica();

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2026-01-15',
            'status' => 'matriculado',
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $professor->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('notas.professor-index', [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
            ]));

        $response->assertOk();
        $this->assertTrue($this->inputHasAttribute($response->getContent(), 'notas[0][mac1]', 'disabled'));

        $this
            ->actingAs($professor)
            ->post(route('notas.lancarTrimestre', 1), [
                'notas' => [
                    [
                        'id' => $nota->id,
                        'mac1' => 12,
                        'pp1' => 12,
                        'pt1' => 12,
                    ],
                ],
            ])
            ->assertRedirect();

        $nota->refresh();

        $this->assertNull($nota->mac1);
        $this->assertNull($nota->pp1);
        $this->assertNull($nota->pt1);
        $this->assertNull($nota->mt1);
    }

    public function test_ca_10_fica_somente_leitura_quando_aluno_ja_tem_turma_da_classe_anterior(): void
    {
        $adminRole = $this->createRoleWithPermissions('admin', ['notas.editar']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $aluno = User::factory()->create(['role_id' => $alunoRole->id]);

        $anoAnterior = AnoLetivo::create([
            'nome' => '2024/2025',
            'data_inicio' => '2024-09-01',
            'data_fim' => '2025-07-31',
            'ativo' => false,
            'encerrado' => true,
        ]);

        $anoAtual = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $coordenador = User::factory()->create();

        $curso = Curso::create([
            'nome' => 'Curso Teste',
            'codigo' => 'CT1',
            'coordenador_id' => $coordenador->id,
            'ativo' => true,
        ]);

        $turma10Anterior = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoAnterior->id,
            'coordenador_turma_id' => $coordenador->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $turma11Atual = Turma::create([
            'nome' => 'A',
            'classe' => '11',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoAtual->id,
            'coordenador_turma_id' => $coordenador->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $aluno->turmas()->attach($turma10Anterior->id, [
            'data_matricula' => '2024-09-10',
            'status' => 'concluido',
        ]);

        $disciplina = Disciplina::create([
            'nome' => 'Matematica',
            'codigo' => 'MAT2',
            'descricao' => 'Disciplina de teste',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma11Atual->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoAtual->id,
            'status' => 'em_lancamento',
            'ca_10' => 13.0,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('notas.update', $nota), [
                'ca_10' => 17.5,
            ])
            ->assertSessionHasErrors('ca_10');

        $nota->refresh();
        $this->assertSame(13.0, (float) $nota->ca_10);
    }

    public function test_secretaria_pode_atualizar_pauta_e_carregar_alunos_sem_notas_lancadas(): void
    {
        $adminRole = $this->createRoleWithPermissions('admin', ['notas.editar', 'notas.view_all']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $aluno1 = User::factory()->create(['role_id' => $alunoRole->id]);
        $aluno2 = User::factory()->create(['role_id' => $alunoRole->id]);

        ['anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina] = $this->createEstruturaAcademica();

        $turma->alunos()->attach($aluno1->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        $turma->alunos()->attach($aluno2->id, [
            'data_matricula' => '2025-09-03',
            'status' => 'matriculado',
        ]);

        $this->assertSame(0, Nota::count());

        $this
            ->actingAs($admin)
            ->post(route('notas.inicializar-pauta'), [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
            ])
            ->assertRedirect(route('notas.index', [
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
            ]));

        $this->assertSame(2, Nota::count());
        $this->assertDatabaseHas('notas', [
            'aluno_id' => $aluno1->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);
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

        $coordenador = User::factory()->create();

        $curso = Curso::create([
            'nome' => 'Curso Teste',
            'codigo' => 'CT',
            'coordenador_id' => $coordenador->id,
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => $overrides['classe'] ?? '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => $coordenador->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplina = Disciplina::create(array_merge([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'descricao' => 'Disciplina de teste',
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

    private function inputHasAttribute(string $html, string $inputName, string $attribute): bool
    {
        $dom = new \DOMDocument;

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('input') as $input) {
            if ($input->getAttribute('name') !== $inputName) {
                continue;
            }

            return $input->hasAttribute($attribute);
        }

        $this->fail("Input {$inputName} nao encontrado na resposta.");
    }
}

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
use App\Services\PautaGeralTemplateExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExportacoesXlsxTest extends TestCase
{
    use RefreshDatabase;

    public function test_exportador_da_pauta_geral_preenche_template_com_dados_da_turma(): void
    {
        $secretariaRole = $this->createRoleWithPermissions('secretaria', ['relatorios.pautas']);
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

        $coordenadorTurma = User::factory()->create();
        $coordenadorCurso = User::factory()->create();
        $secretaria = User::factory()->create(['role_id' => $secretariaRole->id]);
        /** @var User $secretaria */

        $anoLetivo = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $curso = Curso::create([
            'nome' => 'Informática',
            'codigo' => 'INF',
            'coordenador_id' => $coordenadorCurso->id,
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => $coordenadorTurma->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $portugues = Disciplina::create([
            'nome' => 'Língua Portuguesa',
            'codigo' => 'LP',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $matematica = Disciplina::create([
            'nome' => 'Matemática',
            'codigo' => 'MAT',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $turma->disciplinas()->attach([$portugues->id, $matematica->id]);

        $profPortugues = User::factory()->create();
        $profMatematica = User::factory()->create();

        ProfessorTurmaDisciplina::create([
            'professor_id' => $profPortugues->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $portugues->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $profMatematica->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $matematica->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $aluno1 = User::factory()->create([
            'role_id' => $alunoRole->id,
            'name' => 'Aluno A',
            'numero_processo' => '2024001',
            'genero' => 'M',
        ]);

        $aluno2 = User::factory()->create([
            'role_id' => $alunoRole->id,
            'name' => 'Aluno B',
            'numero_processo' => '2024002',
            'genero' => 'F',
        ]);

        $turma->alunos()->attach($aluno1->id, ['data_matricula' => now(), 'status' => 'matriculado']);
        $turma->alunos()->attach($aluno2->id, ['data_matricula' => now(), 'status' => 'matriculado']);

        foreach ([$aluno1, $aluno2] as $aluno) {
            Nota::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'disciplina_id' => $portugues->id,
                'ano_letivo_id' => $anoLetivo->id,
                'mt1' => 13,
                'mt2' => 14,
                'mt3' => 15,
                'pg' => 16,
                'ca' => 15,
                'cfd' => 15,
                'status' => 'finalizado',
            ]);

            Nota::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'disciplina_id' => $matematica->id,
                'ano_letivo_id' => $anoLetivo->id,
                'mt1' => 12,
                'mt2' => 13,
                'mt3' => 14,
                'pg' => 15,
                'ca' => 14,
                'cfd' => 14,
                'status' => 'finalizado',
            ]);
        }

        $spreadsheet = app(PautaGeralTemplateExporter::class)->build([
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'trimestre' => 'final',
        ]);

        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('PAUTA GERAL DO ANO LETIVO', $sheet->getCell('X6')->getValue());
        $this->assertSame('L. PORTUGUESA', $sheet->getCell('E12')->getValue());
        $this->assertSame('MATEMAT.', $sheet->getCell('L12')->getValue());
        $this->assertSame('MT1', $sheet->getCell('E13')->getValue());
        $this->assertSame('CFD', $sheet->getCell('K13')->getValue());
        $this->assertSame('2024001', $sheet->getCell('B15')->getValue());
        $this->assertSame('Aluno A', $sheet->getCell('C15')->getValue());
        $this->assertSame(13.0, (float) $sheet->getCell('E15')->getCalculatedValue());
        $this->assertSame(15.0, (float) $sheet->getCell('K15')->getCalculatedValue());
        $this->assertSame('Transita', $sheet->getCell('CI15')->getValue());
        $this->assertSame($profPortugues->name, $sheet->getCell('E51')->getValue());
        $this->assertSame($coordenadorTurma->name, $sheet->getCell('H60')->getValue());
        $this->assertSame($coordenadorCurso->name, $sheet->getCell('AQ60')->getValue());

        $this->actingAs($secretaria)
            ->get(route('relatorios.pauta', ['turma' => $turma, 'formato' => 'excel', 'ano_letivo_id' => $anoLetivo->id]))
            ->assertDownload('pauta-geral-a.xlsx');
    }

    public function test_dashboard_de_logs_exporta_workbook_xlsx_formatado(): void
    {
        $role = $this->createRoleWithPermissions('admin', ['logs.view']);
        /** @var User $user */
        $user = User::factory()->create(['role_id' => $role->id]);

        $anoLetivo = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $curso = Curso::create([
            'nome' => 'Informática',
            'codigo' => 'INF',
            'coordenador_id' => $user->id,
            'ativo' => true,
        ]);

        $aluno = User::factory()->create(['name' => 'Aluno Dashboard']);
        $turma = Turma::create([
            'nome' => 'B',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => null,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplina = Disciplina::create([
            'nome' => 'TIC',
            'codigo' => 'TIC',
            'leciona_10' => true,
            'leciona_11' => false,
            'leciona_12' => false,
            'disciplina_terminal' => true,
            'ativo' => true,
        ]);

        NotaLog::create([
            'usuario_id' => $user->id,
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'acao' => 'edicao',
            'campo_alterado' => 'mac1',
            'valor_anterior' => '10',
            'valor_novo' => '14',
            'trimestre' => '1',
            'ip_address' => '127.0.0.1',
            'data_alteracao' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('logs.exportar', ['contexto' => 'dashboard']));

        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $path = $response->baseResponse->getFile()->getPathname();
        $spreadsheet = IOFactory::load($path);

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Logs Recentes', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('DASHBOARD DE LOGS', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Data/Hora', $spreadsheet->getSheet(1)->getCell('A1')->getValue());
        $this->assertSame('Aluno Dashboard', $spreadsheet->getSheet(1)->getCell('D2')->getValue());
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

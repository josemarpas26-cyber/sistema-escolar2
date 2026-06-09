<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\ClassificacaoEnsinoMedio;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\NotaLog;
use App\Models\Permission;
use App\Models\ProfessorTurmaDisciplina;
use App\Models\Role;
use App\Exports\PautaExport;
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

        $turma->alunos()->attach($aluno1->id, ['data_matricula' => '2025-09-02', 'status' => 'matriculado']);
        $turma->alunos()->attach($aluno2->id, ['data_matricula' => '2025-09-02', 'status' => 'matriculado']);

        foreach ([$aluno1, $aluno2] as $aluno) {
            Nota::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'disciplina_id' => $portugues->id,
                'ano_letivo_id' => $anoLetivo->id,
                'mt1' => 13,
                'mt2' => 14,
                'mt3' => 15,
                'pt3' => 16,
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
                'pt3' => 15,
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
        $templateSheet = IOFactory::load(resource_path('templates/pauta-geral-template.xlsx'))->getActiveSheet();

        $this->assertSame('PAUTA GERAL DO ANO LETIVO', $sheet->getCell('F6')->getValue());
        $this->assertSame('T', $sheet->getHighestColumn());
        $this->assertSame(29, $sheet->getHighestRow());
        $this->assertEquals($templateSheet->getColumnDimension('E')->getWidth(), $sheet->getColumnDimension('E')->getWidth());
        $this->assertSame(
            $templateSheet->getStyle('E12')->getFill()->getStartColor()->getRGB(),
            $sheet->getStyle('E12')->getFill()->getStartColor()->getRGB()
        );
        $this->assertSame('L. PORTUGUESA', $sheet->getCell('E12')->getValue());
        $this->assertSame('MATEMAT.', $sheet->getCell('L12')->getValue());
        $this->assertSame('FALTAS', $sheet->getCell('E13')->getValue());
        $this->assertSame('CF', $sheet->getCell('G13')->getValue());
        $this->assertSame('MT1', $sheet->getCell('H13')->getValue());
        $this->assertSame('CFD', $sheet->getCell('K13')->getValue());
        $this->assertSame('J', $sheet->getCell('E14')->getValue());
        $this->assertSame('I', $sheet->getCell('F14')->getValue());
        $this->assertSame('OBSERV.', $sheet->getCell('S14')->getValue());
        $this->assertSame('RESULTADO', $sheet->getCell('T14')->getValue());
        $this->assertSame('2024001', $sheet->getCell('B15')->getValue());
        $this->assertSame('Aluno A', $sheet->getCell('C15')->getValue());
        $this->assertSame(13.0, (float) $sheet->getCell('H15')->getCalculatedValue());
        $this->assertSame(15.0, (float) $sheet->getCell('K15')->getCalculatedValue());
        $this->assertSame('Transita', $sheet->getCell('T15')->getValue());
        $this->assertSame($profPortugues->name, $sheet->getCell('E17')->getValue());
        $this->assertSame($coordenadorTurma->name, $sheet->getCell('A26')->getValue());
        $this->assertSame($coordenadorCurso->name, $sheet->getCell('H26')->getValue());

        $this->actingAs($secretaria)
            ->get(route('relatorios.pauta-geral', ['turma' => $turma, 'formato' => 'xlsx', 'ano_letivo_id' => $anoLetivo->id]))
            ->assertDownload('pauta-10a-a-informatica.xlsx');
    }

    public function test_pauta_geral_marca_recurso_quando_negativa_terminal_e_maior_ou_igual_a_sete(): void
    {
        $anoLetivo = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $curso = Curso::create([
            'nome' => 'Informatica',
            'codigo' => 'INF',
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $portugues = Disciplina::create([
            'nome' => 'Lingua Portuguesa',
            'codigo' => 'LP',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $tic = Disciplina::create([
            'nome' => 'TIC',
            'codigo' => 'TIC',
            'leciona_10' => true,
            'leciona_11' => false,
            'leciona_12' => false,
            'disciplina_terminal' => true,
            'ativo' => true,
        ]);

        $turma->disciplinas()->attach([$portugues->id, $tic->id]);
        $curso->disciplinas()->attach($portugues->id, ['ano_terminal' => null]);
        $curso->disciplinas()->attach($tic->id, ['ano_terminal' => 10]);

        $aluno = User::factory()->create([
            'name' => 'Aluno Recurso',
            'numero_processo' => '2024555',
            'genero' => 'M',
        ]);

        $turma->alunos()->attach($aluno->id, ['data_matricula' => '2025-09-02', 'status' => 'matriculado']);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $portugues->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 14,
            'mt2' => 14,
            'mt3' => 14,
            'mft2' => 14,
            'cf' => 14,
            'ca' => 14,
            'cfd' => 14,
            'status' => 'finalizado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $tic->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 8,
            'mt2' => 8,
            'mt3' => 8,
            'mft2' => 8,
            'cf' => 8,
            'ca' => 8,
            'cfd' => 8,
            'status' => 'finalizado',
        ]);

        $spreadsheet = app(PautaGeralTemplateExporter::class)->build([
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'trimestre' => 'final',
        ]);

        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Recurso', $sheet->getCell('S15')->getValue());
        $this->assertSame('Não Transita', $sheet->getCell('T15')->getValue());
    }

    public function test_exportador_da_pauta_geral_da_decima_terceira_usa_template_especifico(): void
    {
        $anoLetivo = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
            'encerrado' => false,
        ]);

        $coordenadorTurma = User::factory()->create(['name' => 'Director da Turma']);
        $coordenadorCurso = User::factory()->create(['name' => 'Coordenador do Curso']);

        $curso = Curso::create([
            'nome' => 'InformÃ¡tica',
            'codigo' => 'INF',
            'coordenador_id' => $coordenadorCurso->id,
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '13',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => $coordenadorTurma->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $portugues = Disciplina::create([
            'nome' => 'PortuguÃªs',
            'codigo' => 'LP',
            'leciona_13' => true,
            'ativo' => true,
        ]);

        $matematica = Disciplina::create([
            'nome' => 'MatemÃ¡tica',
            'codigo' => 'MAT',
            'leciona_13' => true,
            'ativo' => true,
        ]);

        $projecto = Disciplina::create([
            'nome' => 'Projecto TecnolÃ³gico',
            'codigo' => 'PTEC',
            'leciona_13' => true,
            'ativo' => true,
        ]);

        $turma->disciplinas()->attach([$portugues->id, $matematica->id, $projecto->id]);
        $curso->disciplinas()->attach($portugues->id, ['ano_terminal' => 13]);
        $curso->disciplinas()->attach($matematica->id, ['ano_terminal' => null]);
        $curso->disciplinas()->attach($projecto->id, ['ano_terminal' => 13]);

        $profPortugues = User::factory()->create(['name' => 'Prof PortuguÃªs']);
        $profProjecto = User::factory()->create(['name' => 'Prof Projecto']);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $profPortugues->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $portugues->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        ProfessorTurmaDisciplina::create([
            'professor_id' => $profProjecto->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $projecto->id,
            'ano_letivo_id' => $anoLetivo->id,
        ]);

        $aluno = User::factory()->create([
            'name' => 'Aluno 13Âª',
            'numero_processo' => '20251301',
            'genero' => 'M',
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $portugues->id,
            'ano_letivo_id' => $anoLetivo->id,
            'cfd' => 14,
            'status' => 'finalizado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $matematica->id,
            'ano_letivo_id' => $anoLetivo->id,
            'cfd' => 12,
            'status' => 'finalizado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $projecto->id,
            'ano_letivo_id' => $anoLetivo->id,
            'ca_12' => 13,
            'mft2' => 14,
            'mac3' => 15,
            'pp3' => 16,
            'mt3' => 17,
            'ca' => 18,
            'cfd' => 16,
            'status' => 'finalizado',
        ]);

        ClassificacaoEnsinoMedio::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_letivo_id' => $anoLetivo->id,
            'ecs' => 12,
            'pap' => 18,
            'observacoes' => 'Apto para defesa final.',
        ]);

        $spreadsheet = app(PautaGeralTemplateExporter::class)->build([
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'trimestre' => 'final',
        ]);

        $sheet = $spreadsheet->getActiveSheet();
        $colPortugues = $this->findColumnContainingInRow($sheet, 12, 'PORTUGU');
        $colMatematica = $this->findColumnContainingInRow($sheet, 12, 'MATEM');

        $this->assertSame('AK', $sheet->getHighestColumn());
        $this->assertGreaterThanOrEqual(30, $sheet->getHighestRow());
        $this->assertSame('PAUTA FINAL DA 13ª CLASSE', $sheet->getCell('D9')->getValue());
        $this->assertSame('TURMA: INF13A', $sheet->getCell('AB10')->getValue());
        $this->assertNotNull($colPortugues);
        $this->assertNotNull($colMatematica);
        $this->assertStringContainsString('PROJECTO', (string) $sheet->getCell('U12')->getValue());
        $this->assertStringContainsString('Aluno 13', (string) $sheet->getCell('C15')->getValue());
        $this->assertSame(14.0, (float) $sheet->getCell($colPortugues.'15')->getCalculatedValue());
        $this->assertSame(12.0, (float) $sheet->getCell($colMatematica.'15')->getCalculatedValue());
        $this->assertSame(13.0, (float) $sheet->getCell('W15')->getCalculatedValue());
        $this->assertSame(14.0, (float) $sheet->getCell('X15')->getCalculatedValue());
        $this->assertSame(15.0, (float) $sheet->getCell('Y15')->getCalculatedValue());
        $this->assertSame(16.0, (float) $sheet->getCell('Z15')->getCalculatedValue());
        $this->assertSame(17.0, (float) $sheet->getCell('AA15')->getCalculatedValue());
        $this->assertSame(18.0, (float) $sheet->getCell('AB15')->getCalculatedValue());
        $this->assertSame(16.0, (float) $sheet->getCell('AC15')->getCalculatedValue());
        $this->assertSame(12.0, (float) $sheet->getCell('AF15')->getCalculatedValue());
        $this->assertSame(18.0, (float) $sheet->getCell('AG15')->getCalculatedValue());
        $this->assertSame(14.0, (float) $sheet->getCell('AH15')->getCalculatedValue());
        $this->assertSame(14.0, (float) $sheet->getCell('AI15')->getCalculatedValue());
        $this->assertSame('Apto para defesa final.', $sheet->getCell('AJ15')->getValue());
        $this->assertSame('APROVADO', $sheet->getCell('AK15')->getValue());
        $this->assertSame($profPortugues->name, $sheet->getCell('U16')->getValue());
        $this->assertSame($profProjecto->name, $sheet->getCell('AD16')->getValue());
        $this->assertSame($coordenadorTurma->name, $sheet->getCell('A26')->getValue());
        $this->assertSame($coordenadorCurso->name, $sheet->getCell('F26')->getValue());
    }

    public function test_pauta_export_mantem_mt1_vazio_para_aluno_que_ingressou_no_segundo_trimestre(): void
    {
        $alunoRole = $this->createRoleWithPermissions('aluno', []);

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
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplina = Disciplina::create([
            'nome' => 'Matemática',
            'codigo' => 'MAT',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $aluno = User::factory()->create([
            'role_id' => $alunoRole->id,
            'name' => 'Aluno 2º Trimestre',
            'numero_processo' => '2024999',
            'genero' => 'M',
        ]);

        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => '2026-01-15',
            'status' => 'matriculado',
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => null,
            'mt2' => 15,
            'mt3' => 16,
            'mft2' => 15,
            'cf' => 15.5,
            'ca' => 16,
            'cfd' => 16,
            'status' => 'em_lancamento',
        ]);

        $rows = (new PautaExport($turma, $disciplina, collect([$nota->load('aluno')])))->array();
        $linhaAluno = $rows[14];

        $this->assertNull($linhaAluno['K']);
        $this->assertSame(15.0, (float) $linhaAluno['O']);
        $this->assertSame(16.0, (float) $linhaAluno['S']);
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

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
        ]);

        NotaLog::create([
            'nota_id' => $nota->id,
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
        $this->assertSame('Registro Recentes', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('DASHBOARD DE LOGS', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Data/Hora', $spreadsheet->getSheet(1)->getCell('A1')->getValue());
        $this->assertSame('Aluno Dashboard', $spreadsheet->getSheet(1)->getCell('D2')->getValue());
    }


    public function test_exportacao_de_logs_lista_mantem_cabecalho_de_filtros_formatado(): void
    {
        $role = $this->createRoleWithPermissions('admin', ['logs.view']);
        /** @var User $user */
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)
            ->get(route('logs.exportar', [
                'contexto' => 'lista',
                'utilizador' => 'Nome muito longo para testar quebra de linha no resumo de filtros',
                'disciplina' => 'Disciplina com nome muito longo para não alargar a tabela',
            ]));

        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $path = $response->baseResponse->getFile()->getPathname();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertArrayHasKey('B3:K3', $sheet->getMergeCells());
        $this->assertTrue($sheet->getStyle('B3')->getAlignment()->getWrapText());
        $this->assertSame('Data/Hora', $sheet->getCell('A5')->getValue());
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

    private function findColumnContainingInRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, string $expected): ?string
    {
        $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $expected = strtoupper($expected);

        for ($columnIndex = 1; $columnIndex <= $lastColIndex; $columnIndex++) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $value = strtoupper((string) $sheet->getCell($column.$row)->getValue());

            if (str_contains($value, $expected)) {
                return $column;
            }
        }

        return null;
    }
}

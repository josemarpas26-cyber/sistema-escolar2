<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\HistoricoAcademico;
use App\Models\Nota;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatorioHistoricoAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_historico_distingue_recurso_pendente_de_reprovacao_com_observacao_de_recurso(): void
    {
        $roleAluno = $this->criarRole('aluno');
        $this->atribuirPermissao($roleAluno, 'relatorios.historico');

        $roleProfessor = $this->criarRole('professor');

        $aluno = User::factory()->create([
            'role_id' => $roleAluno->id,
            'ativo' => true,
        ]);

        $coordenadorCurso = User::factory()->create([
            'role_id' => $roleProfessor->id,
            'ativo' => true,
        ]);

        $diretorTurma = User::factory()->create([
            'role_id' => $roleProfessor->id,
            'ativo' => true,
        ]);

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
            'descricao' => 'Curso de teste',
            'coordenador_id' => $coordenadorCurso->id,
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => $diretorTurma->id,
            'capacidade' => 35,
            'ativo' => true,
        ]);

        $disciplinaTerminal = Disciplina::create([
            'nome' => 'Matemática',
            'codigo' => 'MAT',
            'descricao' => 'Disciplina terminal',
            'leciona_10' => true,
            'leciona_11' => false,
            'leciona_12' => false,
            'leciona_13' => false,
            'disciplina_terminal' => true,
            'ativo' => true,
        ]);

        $disciplinaNaoTerminal = Disciplina::create([
            'nome' => 'História',
            'codigo' => 'HIS',
            'descricao' => 'Disciplina não terminal',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'leciona_13' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $turma->disciplinas()->attach([$disciplinaTerminal->id, $disciplinaNaoTerminal->id]);
        $curso->disciplinas()->attach($disciplinaTerminal->id, ['ano_terminal' => 10]);
        $curso->disciplinas()->attach($disciplinaNaoTerminal->id, ['ano_terminal' => null]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinaTerminal->id,
            'ano_letivo_id' => $anoLetivo->id,
            'cf' => 8,
            'cfd' => 8,
            'status' => 'finalizado',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinaNaoTerminal->id,
            'ano_letivo_id' => $anoLetivo->id,
            'cf' => 9,
            'cfd' => 9,
            'nota_recurso' => 9.5,
            'status' => 'finalizado',
        ]);

        HistoricoAcademico::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinaTerminal->id,
            'ano_letivo_id' => $anoLetivo->id,
            'classe' => '10',
            'classificacao_final' => 8,
            'resultado' => 'reprovado',
            'observacoes' => 'Registo automático no fecho anual da turma (aluno em recurso).',
            'data_conclusao' => now(),
        ]);

        HistoricoAcademico::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinaNaoTerminal->id,
            'ano_letivo_id' => $anoLetivo->id,
            'classe' => '10',
            'classificacao_final' => 9.5,
            'resultado' => 'reprovado',
            'observacoes' => 'Registo atualizado automaticamente após lançamento de recurso. Nota de recurso: 9.50.',
            'data_conclusao' => now(),
        ]);

        $response = $this
            ->actingAs($aluno)
            ->get(route('relatorios.historico'));

        $response->assertOk();
        $response->assertSeeText('Matemática');
        $response->assertSeeText('História');
        $response->assertSeeText('9,50');

        $conteudo = $response->getContent();

        $this->assertMatchesRegularExpression('/Matemática[\s\S]*?Recurso/u', $conteudo);
        $this->assertMatchesRegularExpression('/História[\s\S]*?Reprovado/u', $conteudo);
    }

    private function criarRole(string $name): Role
    {
        return Role::firstOrCreate(
            ['name' => $name],
            [
                'display_name' => ucfirst($name),
                'description' => "Role {$name} para testes",
            ]
        );
    }

    private function atribuirPermissao(Role $role, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            [
                'display_name' => ucfirst(str_replace('.', ' ', $permissionName)),
                'description' => "Permissão {$permissionName} para testes",
            ]
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $role->flushPermissionsCache();
    }
}

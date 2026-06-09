<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\HistoricoAcademico;
use App\Models\Nota;
use App\Models\ProfessorTurmaDisciplina;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlunoDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_do_aluno_exibe_disciplina_professor_coordenador_e_diretor_da_turma(): void
    {
        ['aluno' => $aluno, 'anoLetivo' => $anoLetivo, 'turma' => $turma, 'disciplina' => $disciplina, 'professor' => $professor, 'diretor' => $diretor, 'coordenadorDisciplina' => $coordenadorDisciplina] = $this->criarContextoAluno();

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
            'mac1' => 12,
            'pp1' => 13,
            'pt1' => 14,
            'mt1' => 13,
            'mac2' => 15,
            'pp2' => 14,
            'pt2' => 16,
            'mt2' => 15,
            'mft2' => 14,
            'mac3' => 15,
            'pp3' => 14,
            'pt3' => 13,
            'mt3' => 15,
            'cf' => 14.5,
            'ca' => 13.9,
            'cfd' => 13.9,
            'status' => 'finalizado',
        ]);

        $response = $this
            ->actingAs($aluno)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Resumo academico completo do aluno');
        $response->assertSeeText($disciplina->nome);
        $response->assertSeeText($professor->name);
        $response->assertSeeText($coordenadorDisciplina->name);
        $response->assertSeeText($diretor->name);
        $response->assertSeeText('MAC1');
        $response->assertSeeText('PP2');
        $response->assertSeeText('CFD');
    }

    public function test_dashboard_do_aluno_respeita_relacionamento_aluno_turma_disciplina(): void
    {
        ['aluno' => $aluno, 'anoLetivo' => $anoLetivo, 'turma' => $turma] = $this->criarContextoAluno();

        $disciplinaValida = Disciplina::create([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'descricao' => 'Disciplina valida',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $disciplinaInvalida = Disciplina::create([
            'nome' => 'Fisica',
            'codigo' => 'FIS',
            'descricao' => 'Disciplina fora da turma',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $turma->disciplinas()->sync([$disciplinaValida->id]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinaValida->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 14,
            'status' => 'em_lancamento',
        ]);

        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinaInvalida->id,
            'ano_letivo_id' => $anoLetivo->id,
            'mt1' => 8,
            'status' => 'em_lancamento',
        ]);

        $response = $this
            ->actingAs($aluno)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Matematica');
        $response->assertDontSeeText('Fisica');
    }

    public function test_dashboard_do_aluno_exibe_historico_academico_de_anos_anteriores(): void
    {
        ['aluno' => $aluno, 'curso' => $curso] = $this->criarContextoAluno();

        $anoAnterior = AnoLetivo::create([
            'nome' => '2024/2025',
            'data_inicio' => '2024-09-01',
            'data_fim' => '2025-07-31',
            'ativo' => false,
            'encerrado' => true,
        ]);

        $diretorAnterior = User::factory()->create([
            'role_id' => $this->criarRole('professor')->id,
            'ativo' => true,
        ]);

        $coordenadorAnterior = User::factory()->create([
            'role_id' => $this->criarRole('professor_aux')->id,
            'ativo' => true,
        ]);

        $turmaAnterior = Turma::create([
            'nome' => 'B',
            'classe' => '10',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoAnterior->id,
            'coordenador_turma_id' => $diretorAnterior->id,
            'capacidade' => 35,
            'ativo' => true,
        ]);

        $disciplinaAnterior = Disciplina::create([
            'nome' => 'Quimica',
            'codigo' => 'QUI',
            'descricao' => 'Disciplina historica',
            'coordenador_id' => $coordenadorAnterior->id,
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        HistoricoAcademico::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turmaAnterior->id,
            'disciplina_id' => $disciplinaAnterior->id,
            'ano_letivo_id' => $anoAnterior->id,
            'classe' => '10',
            'classificacao_final' => 15.50,
            'resultado' => 'aprovado',
            'observacoes' => null,
            'data_conclusao' => now(),
        ]);

        $response = $this
            ->actingAs($aluno)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Historico academico');
        $response->assertSeeText('2024/2025');
        $response->assertSeeText('Quimica');
        $response->assertSeeText('Aprovado');
        $response->assertSeeText($diretorAnterior->name);
        $response->assertSeeText($coordenadorAnterior->name);
    }

    private function criarContextoAluno(): array
    {
        $roleAluno = $this->criarRole('aluno');
        $roleProfessor = $this->criarRole('professor');
        $roleProfessorCoord = $this->criarRole('professor_coord');

        $aluno = User::factory()->create([
            'role_id' => $roleAluno->id,
            'ativo' => true,
        ]);

        $coordenadorCurso = User::factory()->create([
            'role_id' => $roleProfessor->id,
            'ativo' => true,
        ]);

        $diretor = User::factory()->create([
            'role_id' => $roleProfessor->id,
            'ativo' => true,
        ]);

        $professor = User::factory()->create([
            'role_id' => $roleProfessor->id,
            'ativo' => true,
        ]);

        $coordenadorDisciplina = User::factory()->create([
            'role_id' => $roleProfessorCoord->id,
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
            'nome' => 'Curso Teste',
            'codigo' => 'CT',
            'descricao' => 'Curso do aluno',
            'coordenador_id' => $coordenadorCurso->id,
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '11',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'coordenador_turma_id' => $diretor->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplina = Disciplina::create([
            'nome' => 'Biologia',
            'codigo' => 'BIO',
            'descricao' => 'Disciplina atual',
            'coordenador_id' => $coordenadorDisciplina->id,
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => false,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $turma->disciplinas()->attach($disciplina->id);
        $curso->disciplinas()->attach($disciplina->id, ['ano_terminal' => 11]);
        $aluno->turmas()->attach($turma->id, [
            'data_matricula' => '2025-09-02',
            'status' => 'matriculado',
        ]);

        return compact(
            'aluno',
            'anoLetivo',
            'curso',
            'turma',
            'disciplina',
            'professor',
            'diretor',
            'coordenadorDisciplina'
        );
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
}

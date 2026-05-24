<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\ClassificacaoEnsinoMedio;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use App\Services\ClassificacaoEnsinoMedioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassificacaoEnsinoMedioServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_pc_e_media_final_da_decima_terceira(): void
    {
        [$turma, $aluno, $disciplinas, $ano] = $this->criarCenarioBase();

        foreach ($disciplinas as $disciplina) {
            Nota::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'disciplina_id' => $disciplina->id,
                'ano_letivo_id' => $ano->id,
                'cfd' => 14,
            ]);
        }

        ClassificacaoEnsinoMedio::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_letivo_id' => $ano->id,
            'pap' => 16,
            'ecs' => 12,
        ]);

        $resumo = app(ClassificacaoEnsinoMedioService::class)
            ->montarResumoDaTurma($turma)
            ->first();

        $this->assertSame(14.0, $resumo['pc']);
        $this->assertSame(14.0, $resumo['media_final']);
        $this->assertSame('Aprovado', $resumo['resultado']);
    }

    private function criarCenarioBase(): array
    {
        $roleAluno = Role::create([
            'name' => 'aluno',
            'display_name' => 'Aluno',
        ]);

        $aluno = User::factory()->create([
            'role_id' => $roleAluno->id,
        ]);

        $ano = AnoLetivo::create([
            'nome' => '2025/2026',
            'data_inicio' => '2025-09-01',
            'data_fim' => '2026-07-31',
            'ativo' => true,
        ]);

        $curso = Curso::create([
            'nome' => 'Informatica',
            'codigo' => 'INF',
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '13',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $ano->id,
            'capacidade' => 40,
            'turno' => 'M',
            'ativo' => true,
        ]);

        $disciplinas = collect([
            ['nome' => 'Projecto Tecnologico', 'codigo' => 'PTEC'],
        ])->map(function (array $dados) {
            return Disciplina::create([
                'nome' => $dados['nome'],
                'codigo' => $dados['codigo'],
                'leciona_13' => true,
                'ativo' => true,
            ]);
        });

        $turma->disciplinas()->attach($disciplinas->pluck('id'));
        $turma->alunos()->attach($aluno->id, [
            'data_matricula' => now()->toDateString(),
            'status' => 'matriculado',
        ]);

        return [$turma, $aluno, $disciplinas->all(), $ano];
    }
}

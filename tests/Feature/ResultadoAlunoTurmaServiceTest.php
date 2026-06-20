<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use App\Services\ResultadoAlunoTurmaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultadoAlunoTurmaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_aprova_com_ate_duas_negativas_nao_terminais_iguais_ou_superiores_a_sete(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase();

        $this->criarNota($turma, $aluno, $disciplinas[0], 8);
        $this->criarNota($turma, $aluno, $disciplinas[1], 8);
        $this->criarNota($turma, $aluno, $disciplinas[2], 14);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_TRANSITA, $resultado['status']);
        $this->assertSame('Transita', $resultado['resultado']);
        $this->assertSame('', $resultado['observacao']);
    }

    public function test_reprova_quando_tem_negativa_inferior_a_sete(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase();

        $this->criarNota($turma, $aluno, $disciplinas[0], 6);
        $this->criarNota($turma, $aluno, $disciplinas[1], 14);
        $this->criarNota($turma, $aluno, $disciplinas[2], 13);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_REPROVADO, $resultado['status']);
        $this->assertSame('Não Transita', $resultado['resultado']);
    }

    public function test_reprova_quando_tem_mais_de_duas_negativas_nao_terminais(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase();

        $this->criarNota($turma, $aluno, $disciplinas[0], 8);
        $this->criarNota($turma, $aluno, $disciplinas[1], 8);
        $this->criarNota($turma, $aluno, $disciplinas[2], 8);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_REPROVADO, $resultado['status']);
        $this->assertSame('Não Transita', $resultado['resultado']);
    }

    public function test_envia_para_recurso_quando_tem_negativa_em_disciplina_terminal(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase(classe: '12', terminalIndex: 2, anoTerminal: 12);

        $this->criarNota($turma, $aluno, $disciplinas[0], 14);
        $this->criarNota($turma, $aluno, $disciplinas[1], 13);
        $this->criarNota($turma, $aluno, $disciplinas[2], 8);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_RECURSO, $resultado['status']);
        $this->assertSame('Recurso', $resultado['observacao']);
        $this->assertSame('Não Transita', $resultado['resultado']);
    }

    public function test_reprova_com_negativa_inferior_a_sete_mesmo_tendo_recurso_pendente(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase(classe: '12', terminalIndex: 2, anoTerminal: 12);

        $this->criarNota($turma, $aluno, $disciplinas[0], 6);
        $this->criarNota($turma, $aluno, $disciplinas[1], 13);
        $this->criarNota($turma, $aluno, $disciplinas[2], 8);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_REPROVADO, $resultado['status']);
        $this->assertSame('Não Transita', $resultado['resultado']);
        $this->assertSame('', $resultado['observacao']);
    }

    public function test_reprova_com_mais_de_tres_negativas_mesmo_tendo_recurso_pendente(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase(classe: '12', terminalIndex: 2, anoTerminal: 12);
        $disciplinaExtra = $this->adicionarDisciplina($turma, 'Biologia', 'BIO');

        $this->criarNota($turma, $aluno, $disciplinas[0], 8);
        $this->criarNota($turma, $aluno, $disciplinas[1], 8);
        $this->criarNota($turma, $aluno, $disciplinas[2], 8);
        $this->criarNota($turma, $aluno, $disciplinaExtra, 8);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_REPROVADO, $resultado['status']);
        $this->assertSame('Não Transita', $resultado['resultado']);
        $this->assertSame('', $resultado['observacao']);
    }

    public function test_disciplina_terminal_de_outra_classe_conta_como_nao_terminal(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase(classe: '11', terminalIndex: 2, anoTerminal: 12);

        $this->criarNota($turma, $aluno, $disciplinas[0], 14);
        $this->criarNota($turma, $aluno, $disciplinas[1], 13);
        $this->criarNota($turma, $aluno, $disciplinas[2], 8);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_TRANSITA, $resultado['status']);
    }

    public function test_recurso_com_nota_superior_substitui_cfd_e_permita_transitar(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase(classe: '12', terminalIndex: 2, anoTerminal: 12);

        $this->criarNota($turma, $aluno, $disciplinas[0], 14);
        $this->criarNota($turma, $aluno, $disciplinas[1], 13);
        $notaTerminal = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplinas[2]->id,
            'ano_letivo_id' => $turma->ano_letivo_id,
            'cfd' => 8,
            'nota_recurso' => 12,
        ]);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertTrue($notaTerminal->fresh()->recursoMelhoraClassificacaoFinal());
        $this->assertSame(12.0, $notaTerminal->fresh()->cfd_efetiva);
        $this->assertSame(ResultadoAlunoTurmaService::STATUS_TRANSITA, $resultado['status']);
        $this->assertSame('Transita', $resultado['resultado']);
    }

    public function test_na_decima_segunda_so_disciplinas_terminais_bloqueiam_acesso_a_decima_terceira(): void
    {
        [$turma, $aluno, $disciplinas] = $this->criarCenarioBase(classe: '12', terminalIndex: 2, anoTerminal: 12);

        $this->criarNota($turma, $aluno, $disciplinas[0], 8);
        $this->criarNota($turma, $aluno, $disciplinas[1], 8);
        $this->criarNota($turma, $aluno, $disciplinas[2], 14);

        $resultado = $this->avaliar($turma, $aluno);

        $this->assertSame(ResultadoAlunoTurmaService::STATUS_TRANSITA, $resultado['status']);
        $this->assertSame('Transita', $resultado['resultado']);
    }

    public function test_infere_disciplina_terminal_da_decima_segunda_sem_relacao_com_curso(): void
    {
        $turma = new Turma(['classe' => '12', 'curso_id' => 999]);
        $disciplina = new Disciplina([
            'leciona_10' => false,
            'leciona_11' => false,
            'leciona_12' => true,
            'leciona_13' => true,
            'disciplina_terminal' => true,
        ]);
        $disciplina->setRelation('cursos', collect());

        $this->assertTrue($disciplina->ehTerminalNaTurma($turma));
        $this->assertSame(12, $disciplina->anoTerminalParaCurso($turma->curso_id));
    }

    private function avaliar(Turma $turma, User $aluno): array
    {
        $turma->load('disciplinas.cursos');

        $notas = Nota::query()
            ->where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->get(['disciplina_id', 'cf', 'cfd', 'nota_recurso']);

        return app(ResultadoAlunoTurmaService::class)->avaliar($turma, $turma->disciplinas, $notas);
    }

    private function adicionarDisciplina(Turma $turma, string $nome, string $codigo): Disciplina
    {
        $disciplina = Disciplina::create([
            'nome' => $nome,
            'codigo' => $codigo,
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'disciplina_terminal' => false,
            'ativo' => true,
        ]);

        $turma->disciplinas()->attach($disciplina->id);
        $turma->curso->disciplinas()->attach($disciplina->id, ['ano_terminal' => null]);

        return $disciplina;
    }

    private function criarNota(Turma $turma, User $aluno, Disciplina $disciplina, float $cfd): void
    {
        Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $turma->ano_letivo_id,
            'cfd' => $cfd,
        ]);
    }

    private function criarCenarioBase(string $classe = '10', ?int $terminalIndex = null, ?int $anoTerminal = null): array
    {
        $anoLetivo = AnoLetivo::create([
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
            'classe' => $classe,
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $aluno = User::factory()->create();

        $disciplinas = collect([
            ['nome' => 'Matematica', 'codigo' => 'MAT'],
            ['nome' => 'Fisica', 'codigo' => 'FIS'],
            ['nome' => 'Quimica', 'codigo' => 'QUI'],
        ])->map(function (array $dados, int $index) use ($terminalIndex) {
            return Disciplina::create([
                'nome' => $dados['nome'],
                'codigo' => $dados['codigo'],
                'leciona_10' => true,
                'leciona_11' => true,
                'leciona_12' => true,
                'disciplina_terminal' => $terminalIndex === $index,
                'ativo' => true,
            ]);
        });

        $turma->disciplinas()->attach($disciplinas->pluck('id'));

        foreach ($disciplinas as $index => $disciplina) {
            $curso->disciplinas()->attach($disciplina->id, [
                'ano_terminal' => $terminalIndex === $index ? $anoTerminal : null,
            ]);
        }

        return [$turma, $aluno, $disciplinas->values()->all()];
    }
}

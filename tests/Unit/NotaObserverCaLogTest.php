<?php

namespace Tests\Unit;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\NotaLog;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaObserverCaLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_regista_log_quando_ca_10_e_alterado(): void
    {
        $roleSecretaria = Role::create([
            'name' => 'secretaria',
            'display_name' => 'Secretaria',
        ]);

        $roleAluno = Role::create([
            'name' => 'aluno',
            'display_name' => 'Aluno',
        ]);

        $secretaria = User::factory()->create(['role_id' => $roleSecretaria->id]);
        $aluno = User::factory()->create(['role_id' => $roleAluno->id]);

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
            'ativo' => true,
        ]);

        $turma = Turma::create([
            'nome' => 'A',
            'classe' => '11',
            'curso_id' => $curso->id,
            'ano_letivo_id' => $anoLetivo->id,
            'capacidade' => 40,
            'ativo' => true,
        ]);

        $disciplina = Disciplina::create([
            'nome' => 'Matematica',
            'codigo' => 'MAT',
            'leciona_10' => true,
            'leciona_11' => true,
            'leciona_12' => true,
            'ativo' => true,
        ]);

        $nota = Nota::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'ano_letivo_id' => $anoLetivo->id,
            'status' => 'em_lancamento',
            'ca_10' => 12.0,
        ]);

        $this->actingAs($secretaria);

        $nota->ca_10 = 14.5;
        $nota->save();

        $log = NotaLog::query()
            ->where('nota_id', $nota->id)
            ->where('acao', 'edicao')
            ->where('campo_alterado', 'ca_10')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(12.0, (float) $log->valor_anterior);
        $this->assertSame(14.5, (float) $log->valor_novo);
    }
}

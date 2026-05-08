<?php

namespace App\Services;

use App\Models\Nota;
use App\Models\Turma;
use Illuminate\Support\Facades\DB;

class EstadoMatriculaService
{
    public function sincronizarAlunoNaTurma(int $turmaId, int $alunoId): void
    {
        $statusAtual = DB::table('turma_aluno')
            ->where('turma_id', $turmaId)
            ->where('aluno_id', $alunoId)
            ->value('status');

        if (! in_array($statusAtual, ['matriculado', 'concluido', 'aprovado', 'reprovado'], true)) {
            return;
        }

        $turma = Turma::query()
            ->with('disciplinas.cursos')
            ->find($turmaId, ['id', 'curso_id', 'classe', 'ano_letivo_id']);

        if (! $turma) {
            return;
        }

        $disciplinaIds = $turma->disciplinas->pluck('id');

        if ($disciplinaIds->isEmpty()) {
            $this->definirStatus($turmaId, $alunoId, 'matriculado');

            return;
        }

        $notas = Nota::query()
            ->where('turma_id', $turmaId)
            ->where('aluno_id', $alunoId)
            ->whereIn('disciplina_id', $disciplinaIds)
            ->get(['disciplina_id', 'cf', 'cfd', 'nota_recurso']);

        $resultado = app(ResultadoAlunoTurmaService::class)->avaliar($turma, $turma->disciplinas, $notas);

        $this->definirStatus(
            $turmaId,
            $alunoId,
            $resultado['status'] === ResultadoAlunoTurmaService::STATUS_TRANSITA
                ? 'aprovado'
                : ($resultado['status'] === ResultadoAlunoTurmaService::STATUS_REPROVADO ? 'reprovado' : 'matriculado')
        );
    }

    public function sincronizarTurma(int $turmaId): void
    {
        $alunoIds = DB::table('turma_aluno')
            ->where('turma_id', $turmaId)
            ->whereIn('status', ['matriculado', 'concluido', 'aprovado', 'reprovado'])
            ->pluck('aluno_id');

        foreach ($alunoIds as $alunoId) {
            $this->sincronizarAlunoNaTurma($turmaId, (int) $alunoId);
        }
    }

    private function definirStatus(int $turmaId, int $alunoId, string $status): void
    {
        DB::table('turma_aluno')
            ->where('turma_id', $turmaId)
            ->where('aluno_id', $alunoId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
}

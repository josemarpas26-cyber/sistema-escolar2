<?php

namespace App\Services;

use App\Models\Nota;
use Illuminate\Support\Facades\DB;

class EstadoMatriculaService
{
    public function sincronizarAlunoNaTurma(int $turmaId, int $alunoId): void
    {
        $statusAtual = DB::table('turma_aluno')
            ->where('turma_id', $turmaId)
            ->where('aluno_id', $alunoId)
            ->value('status');

        if (! in_array($statusAtual, ['matriculado', 'concluido'], true)) {
            return;
        }

        $disciplinaIds = DB::table('turma_disciplina')
            ->where('turma_id', $turmaId)
            ->pluck('disciplina_id');

        if ($disciplinaIds->isEmpty()) {
            $this->definirStatus($turmaId, $alunoId, 'matriculado');

            return;
        }

        $notas = Nota::query()
            ->where('turma_id', $turmaId)
            ->where('aluno_id', $alunoId)
            ->whereIn('disciplina_id', $disciplinaIds)
            ->get(['disciplina_id', 'cfd']);

        $disciplinasComNota = $notas->pluck('disciplina_id')->unique()->count();

        if ($disciplinasComNota < $disciplinaIds->count()) {
            $this->definirStatus($turmaId, $alunoId, 'matriculado');

            return;
        }

        $aprovadoEmTodas = $notas
            ->groupBy('disciplina_id')
            ->every(fn ($porDisciplina) => optional($porDisciplina->first())->cfd !== null
                && (float) $porDisciplina->first()->cfd >= 10.0);

        $this->definirStatus($turmaId, $alunoId, $aprovadoEmTodas ? 'concluido' : 'matriculado');
    }

    public function sincronizarTurma(int $turmaId): void
    {
        $alunoIds = DB::table('turma_aluno')
            ->where('turma_id', $turmaId)
            ->whereIn('status', ['matriculado', 'concluido'])
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

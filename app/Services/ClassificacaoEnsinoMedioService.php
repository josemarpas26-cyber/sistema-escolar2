<?php

namespace App\Services;

use App\Models\ClassificacaoEnsinoMedio;
use App\Models\ConfiguracaoAvaliacao;
use App\Models\Nota;
use App\Models\Turma;
use Illuminate\Support\Collection;

class ClassificacaoEnsinoMedioService
{
    public function montarResumoDaTurma(Turma $turma, ?Collection $notas = null): Collection
    {
        $turma->loadMissing(['alunos', 'disciplinas']);

        $disciplinasIds = $turma->disciplinas->pluck('id')->values();
        $totalDisciplinas = $disciplinasIds->count();

        $notas ??= Nota::query()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->whereIn('disciplina_id', $disciplinasIds)
            ->with(['disciplina.cursos', 'turma'])
            ->get();

        $notasPorAluno = $notas->groupBy('aluno_id');
        $classificacoes = ClassificacaoEnsinoMedio::query()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->get()
            ->keyBy('aluno_id');

        $notaMinima = $this->notaMinimaAprovacao($turma);

        return $turma->alunos
            ->whereIn('pivot.status', ['matriculado', 'recurso', 'aprovado', 'reprovado', 'concluido'])
            ->values()
            ->map(function ($aluno) use (
                $disciplinasIds,
                $totalDisciplinas,
                $notasPorAluno,
                $classificacoes,
                $notaMinima
            ) {
                return $this->montarResumoDoAlunoComDados(
                    $aluno,
                    $disciplinasIds,
                    $totalDisciplinas,
                    $notasPorAluno->get($aluno->id, collect()),
                    $classificacoes->get($aluno->id),
                    $notaMinima
                );
            });
    }

    public function montarResumoDoAluno(Turma $turma, mixed $aluno, ?Collection $notas = null): ?array
    {
        $turma->loadMissing('disciplinas');

        $alunoModel = $aluno instanceof \App\Models\User
            ? $aluno
            : $turma->alunos()
                ->where('users.id', (int) $aluno)
                ->wherePivotIn('status', ['matriculado', 'recurso', 'aprovado', 'reprovado', 'concluido'])
                ->first();

        if (! $alunoModel) {
            return null;
        }

        $disciplinasIds = $turma->disciplinas->pluck('id')->values();
        $totalDisciplinas = $disciplinasIds->count();

        $notas ??= Nota::query()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->where('aluno_id', $alunoModel->id)
            ->whereIn('disciplina_id', $disciplinasIds)
            ->with(['disciplina.cursos', 'turma'])
            ->get();

        $classificacao = ClassificacaoEnsinoMedio::query()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->where('aluno_id', $alunoModel->id)
            ->first();

        return $this->montarResumoDoAlunoComDados(
            $alunoModel,
            $disciplinasIds,
            $totalDisciplinas,
            $notas,
            $classificacao,
            $this->notaMinimaAprovacao($turma)
        );
    }

    public function calcularPc(Collection $disciplinasIds, Collection $notasAluno, int $totalDisciplinas): ?float
    {
        if ($totalDisciplinas === 0) {
            return null;
        }

        $notasValidas = $notasAluno
            ->whereIn('disciplina_id', $disciplinasIds)
            ->filter(fn (Nota $nota) => $nota->cfd_efetiva !== null);

        if ($notasValidas->count() !== $totalDisciplinas) {
            return null;
        }

        return round((float) $notasValidas->avg('cfd_efetiva'), 0);
    }

    public function calcularMediaFinal(mixed $pc, mixed $ecs, mixed $pap): ?float
    {
        if (! is_numeric($pc) || ! is_numeric($ecs) || ! is_numeric($pap)) {
            return null;
        }

        return round(((4 * (float) $pc) + (float) $ecs + (float) $pap) / 6, 0);
    }

    private function resolverResultado(Collection $notasAluno, ?float $mediaFinal, float $notaMinima, int $totalDisciplinas): string
    {
        if ($mediaFinal === null) {
            return 'Pendente';
        }

        $notasFinais = $notasAluno->filter(fn (Nota $nota) => $nota->cfd_efetiva !== null);

        if ($notasFinais->count() !== $totalDisciplinas) {
            return 'Pendente';
        }

        return $mediaFinal >= $notaMinima && $notasFinais->every(fn (Nota $nota) => $nota->isAprovado())
            ? 'Aprovado'
            : 'Reprovado';
    }

    private function montarResumoDoAlunoComDados(
        mixed $aluno,
        Collection $disciplinasIds,
        int $totalDisciplinas,
        Collection $notasAluno,
        ?ClassificacaoEnsinoMedio $classificacao,
        float $notaMinima
    ): array {
        $pc = $this->calcularPc($disciplinasIds, $notasAluno, $totalDisciplinas);
        $mediaFinal = $this->calcularMediaFinal($pc, $classificacao?->ecs, $classificacao?->pap);

        return [
            'aluno' => $aluno,
            'classificacao' => $classificacao,
            'pc' => $pc,
            'media_final' => $mediaFinal,
            'resultado' => $this->resolverResultado($notasAluno, $mediaFinal, $notaMinima, $totalDisciplinas),
        ];
    }

    private function notaMinimaAprovacao(Turma $turma): float
    {
        $configuracao = ConfiguracaoAvaliacao::query()
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->first();

        return (float) (
            $configuracao?->nota_minima_aprovacao
            ?? ConfiguracaoAvaliacao::estruturaPadrao()['nota_minima_aprovacao']
        );
    }
}

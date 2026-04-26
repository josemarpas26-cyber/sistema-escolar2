<?php

namespace App\Services;

use App\Models\ConfiguracaoAvaliacao;
use App\Models\Disciplina;
use App\Models\Turma;
use Illuminate\Support\Collection;

class ResultadoAlunoTurmaService
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_TRANSITA = 'transita';
    public const STATUS_RECURSO = 'recurso';
    public const STATUS_REPROVADO = 'reprovado';

    private const LIMITE_NEGATIVA_GRAVE = 7.0;
    private const MAX_NEGATIVAS_NAO_TERMINAIS = 2;

    private array $notaMinimaCache = [];

    public function avaliar(Turma $turma, Collection $disciplinas, iterable $notasAluno): array
    {
        $notasPorDisciplina = $this->indexarNotas($notasAluno);
        $notaMinima = $this->notaMinimaAprovacao($turma);

        $temNota = false;
        $temPendente = false;
        $temEEF = false;
        $temNegativaGrave = false;
        $temRecurso = false;
        $negativasNaoTerminais = 0;

        foreach ($disciplinas as $disciplina) {
            $nota = $notasPorDisciplina[$disciplina->id] ?? null;

            if ($this->notaEmEEF($nota)) {
                $temNota = true;
                $temEEF = true;
                continue;
            }

            $valorFinal = $this->valorFinal($nota);

            if ($valorFinal === null) {
                $temPendente = true;
                continue;
            }

            $temNota = true;

            if ($valorFinal >= $notaMinima) {
                continue;
            }

            if ($valorFinal < self::LIMITE_NEGATIVA_GRAVE) {
                $temNegativaGrave = true;
                continue;
            }

            if ($this->disciplinaTerminalNaTurma($disciplina, $turma)) {
                $temRecurso = true;
                continue;
            }

            $negativasNaoTerminais++;
        }

        if (! $temNota || $temPendente) {
            return $this->resultado(self::STATUS_PENDENTE, '', '');
        }

        if ($temEEF) {
            return $this->resultado(self::STATUS_REPROVADO, 'EEF', 'Não Transita');
        }

        if ($temNegativaGrave || $negativasNaoTerminais > self::MAX_NEGATIVAS_NAO_TERMINAIS) {
            return $this->resultado(self::STATUS_REPROVADO, '', 'Não Transita');
        }

        if ($temRecurso) {
            return $this->resultado(self::STATUS_RECURSO, 'Recurso', 'Não Transita');
        }

        return $this->resultado(self::STATUS_TRANSITA, '', 'Transita');
    }

    private function resultado(string $status, string $observacao, string $resultado): array
    {
        return [
            'status' => $status,
            'observacao' => $observacao,
            'resultado' => $resultado,
        ];
    }

    private function notaMinimaAprovacao(Turma $turma): float
    {
        if (isset($this->notaMinimaCache[$turma->ano_letivo_id])) {
            return $this->notaMinimaCache[$turma->ano_letivo_id];
        }

        $configuracao = ConfiguracaoAvaliacao::query()
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->first();

        return $this->notaMinimaCache[$turma->ano_letivo_id] = (float) (
            $configuracao?->nota_minima_aprovacao
            ?? ConfiguracaoAvaliacao::estruturaPadrao()['nota_minima_aprovacao']
        );
    }

    private function disciplinaTerminalNaTurma(Disciplina $disciplina, Turma $turma): bool
    {
        $classeAtual = (int) $turma->classe;
        $cursoId = $turma->curso_id;

        if ($disciplina->relationLoaded('cursos')) {
            $relacaoCurso = $disciplina->cursos->firstWhere('id', $cursoId);

            if (! $relacaoCurso) {
                return $disciplina->disciplina_terminal && $classeAtual === 10;
            }

            $anoTerminal = $relacaoCurso->pivot->ano_terminal;

            return $anoTerminal !== null && (int) $anoTerminal === $classeAtual;
        }

        return $disciplina->anoTerminalParaCurso($cursoId) === $classeAtual;
    }

    private function notaEmEEF(mixed $nota): bool
    {
        $cf = $this->campo($nota, 'cf');

        return is_string($cf) && strtoupper(trim($cf)) === 'EEF';
    }

    private function valorFinal(mixed $nota): ?float
    {
        $valor = $this->campo($nota, 'cfd');

        if ($valor === null || $valor === '') {
            $valor = $this->campo($nota, 'cf');
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    private function indexarNotas(iterable $notasAluno): array
    {
        $index = [];

        foreach ($notasAluno as $chave => $nota) {
            $disciplinaId = $this->campo($nota, 'disciplina_id');

            if ($disciplinaId === null && is_numeric($chave)) {
                $disciplinaId = (int) $chave;
            }

            if ($disciplinaId === null) {
                continue;
            }

            $index[(int) $disciplinaId] = $nota;
        }

        return $index;
    }

    private function campo(mixed $item, string $campo): mixed
    {
        if (is_array($item)) {
            return $item[$campo] ?? null;
        }

        if (is_object($item)) {
            return $item->{$campo} ?? null;
        }

        return null;
    }
}

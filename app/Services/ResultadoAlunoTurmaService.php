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

    private const RESULTADO_NAO_TRANSITA = "N\u{00E3}o Transita";
    private const LIMITE_NEGATIVA_GRAVE = 7.0;
    private const MAX_NEGATIVAS_NAO_TERMINAIS = 2;
    private const MAX_NEGATIVAS_COM_RECURSO = 3;

    private array $notaMinimaCache = [];

    public function avaliar(Turma $turma, Collection $disciplinas, iterable $notasAluno): array
    {
        $notasPorDisciplina = $this->indexarNotas($notasAluno);
        $notaMinima = $this->notaMinimaAprovacao($turma);

        if ((int) $turma->classe === 12) {
            return $this->avaliarTransicaoParaDecimaTerceira($turma, $disciplinas, $notasPorDisciplina, $notaMinima);
        }

        $temNota = false;
        $temPendente = false;
        $temEEF = false;
        $temNegativaGrave = false;
        $temRecurso = false;
        $temNegativaTerminalSemSucessoNoRecurso = false;
        $negativasNaoTerminais = 0;
        $totalNegativas = 0;

        foreach ($disciplinas as $disciplina) {
            $nota = $notasPorDisciplina[$disciplina->id] ?? null;

            if ($this->notaEmEEF($nota)) {
                $temNota = true;
                $temEEF = true;
                continue;
            }

            $valorBase = $this->valorBase($nota);
            $valorFinal = $this->valorFinal($nota);

            if ($valorFinal === null) {
                $temPendente = true;
                continue;
            }

            $temNota = true;

            if ($valorBase !== null && $valorBase < self::LIMITE_NEGATIVA_GRAVE) {
                $temNegativaGrave = true;
                continue;
            }

            if ($valorFinal >= $notaMinima) {
                continue;
            }

            $totalNegativas++;

            if ($this->disciplinaTerminalNaTurma($disciplina, $turma)) {
                if ($this->recursoPendente($nota, $notaMinima)) {
                    $temRecurso = true;
                    continue;
                }

                $temNegativaTerminalSemSucessoNoRecurso = true;
                continue;
            }

            $negativasNaoTerminais++;
        }

        if (! $temNota || $temPendente) {
            return $this->resultado(self::STATUS_PENDENTE, '', '');
        }

        if ($temEEF) {
            return $this->resultado(self::STATUS_REPROVADO, 'EEF', self::RESULTADO_NAO_TRANSITA);
        }

        if (
            $temNegativaGrave
            || $temNegativaTerminalSemSucessoNoRecurso
            || $negativasNaoTerminais > self::MAX_NEGATIVAS_NAO_TERMINAIS
            || $totalNegativas > self::MAX_NEGATIVAS_COM_RECURSO
        ) {
            return $this->resultado(self::STATUS_REPROVADO, '', self::RESULTADO_NAO_TRANSITA);
        }

        if ($temRecurso) {
            return $this->resultado(self::STATUS_RECURSO, 'Recurso', self::RESULTADO_NAO_TRANSITA);
        }

        return $this->resultado(self::STATUS_TRANSITA, '', 'Transita');
    }

    private function avaliarTransicaoParaDecimaTerceira(
        Turma $turma,
        Collection $disciplinas,
        array $notasPorDisciplina,
        float $notaMinima
    ): array {
        $disciplinasTerminais = $disciplinas
            ->filter(fn (Disciplina $disciplina) => $this->disciplinaTerminalNaTurma($disciplina, $turma))
            ->values();

        if ($disciplinasTerminais->isEmpty()) {
            return $this->resultado(self::STATUS_PENDENTE, '', '');
        }

        $bloqueioRecurso = $this->temBloqueioGeralDeRecurso($disciplinas, $notasPorDisciplina, $notaMinima);
        $temPendente = false;
        $temRecurso = false;
        $temTerminalReprovada = false;

        foreach ($disciplinasTerminais as $disciplina) {
            $nota = $notasPorDisciplina[$disciplina->id] ?? null;

            if ($this->notaEmEEF($nota)) {
                return $this->resultado(self::STATUS_REPROVADO, 'EEF', self::RESULTADO_NAO_TRANSITA);
            }

            $valorBase = $this->valorBase($nota);
            $valorFinal = $this->valorFinal($nota);

            if ($valorFinal === null) {
                $temPendente = true;
                continue;
            }

            if ($valorBase !== null && $valorBase < self::LIMITE_NEGATIVA_GRAVE) {
                $temTerminalReprovada = true;
                continue;
            }

            if ($valorFinal >= $notaMinima) {
                continue;
            }

            if ($this->recursoPendente($nota, $notaMinima)) {
                $temRecurso = true;
                continue;
            }

            $temTerminalReprovada = true;
        }

        if ($temPendente) {
            return $this->resultado(self::STATUS_PENDENTE, '', '');
        }

        if ($temTerminalReprovada || $bloqueioRecurso) {
            return $this->resultado(self::STATUS_REPROVADO, '', self::RESULTADO_NAO_TRANSITA);
        }

        if ($temRecurso) {
            return $this->resultado(self::STATUS_RECURSO, 'Recurso', self::RESULTADO_NAO_TRANSITA);
        }

        return $this->resultado(self::STATUS_TRANSITA, '', 'Transita');
    }


    private function temBloqueioGeralDeRecurso(Collection $disciplinas, array $notasPorDisciplina, float $notaMinima): bool
    {
        $totalNegativas = 0;

        foreach ($disciplinas as $disciplina) {
            $nota = $notasPorDisciplina[$disciplina->id] ?? null;

            if ($this->notaEmEEF($nota)) {
                return true;
            }

            $valorBase = $this->valorBase($nota);
            $valorFinal = $this->valorFinal($nota);

            if ($valorBase !== null && $valorBase < self::LIMITE_NEGATIVA_GRAVE) {
                return true;
            }

            if ($valorFinal === null || $valorFinal >= $notaMinima) {
                continue;
            }

            $totalNegativas++;
        }

        return $totalNegativas > self::MAX_NEGATIVAS_COM_RECURSO;
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
        return $disciplina->ehTerminalNaTurma($turma);
    }

    private function notaEmEEF(mixed $nota): bool
    {
        $cf = $this->campo($nota, 'cf');

        return is_string($cf) && strtoupper(trim($cf)) === 'EEF';
    }

    private function valorFinal(mixed $nota): ?float
    {
        $valor = $this->valorBase($nota);
        $notaRecurso = $this->campo($nota, 'nota_recurso');

        if (is_numeric($notaRecurso)) {
            $valorRecurso = (float) $notaRecurso;

            if ($valor === null) {
                return $valorRecurso;
            }

            return max($valor, $valorRecurso);
        }

        return $valor;
    }

    private function valorBase(mixed $nota): ?float
    {
        $valor = $this->campo($nota, 'cfd');

        if ($valor === null || $valor === '') {
            $valor = $this->campo($nota, 'cf');
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    private function recursoPendente(mixed $nota, float $notaMinima): bool
    {
        $valorBase = $this->valorBase($nota);

        if ($valorBase === null || $valorBase < self::LIMITE_NEGATIVA_GRAVE || $valorBase >= $notaMinima) {
            return false;
        }

        $notaRecurso = $this->campo($nota, 'nota_recurso');

        return ! is_numeric($notaRecurso);
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

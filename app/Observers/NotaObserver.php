<?php

namespace App\Observers;

use App\Models\Nota;
use App\Models\NotaLog;
use Illuminate\Support\Facades\Auth;

class NotaObserver
{
    /**
     * Flag estática para suprimir logs durante recálculo interno.
     *
     * Uso no controller/service:
     *   NotaObserver::$suprimirLogs = true;
     *   $nota->recalcularNota();   // salva campos derivados sem gerar log
     *   NotaObserver::$suprimirLogs = false;
     */
    public static bool $suprimirLogs = false;

    /**
     * Campos cujas alterações devem ser auditadas.
     * Campos derivados (mt1, mt2, mft2, cf, cfd, ca) são calculados
     * automaticamente e NÃO devem gerar log — só confundem o histórico.
     */
    private const CAMPOS_MONITORADOS = [
        'mac1', 'pp1', 'pt1',
        'mac2', 'pp2', 'pt2',
        'mac3', 'pp3', 'pg',
        'ca_10', 'ca_11',
        'status',
        'bloqueado_t1',
        'bloqueado_t2',
        'bloqueado_t3',
    ];

    // ── Hooks do Eloquent ────────────────────────────────────────────────────

    public function created(Nota $nota): void
    {
        if (self::$suprimirLogs || Auth::guest()) {
            return;
        }

        $this->registrarLog($nota, 'criacao', 'todos', null, null);
    }

    public function updating(Nota $nota): void
    {
        // Suprime logs durante recálculo interno (campos derivados)
        if (self::$suprimirLogs || Auth::guest()) {
            return;
        }

        foreach (self::CAMPOS_MONITORADOS as $campo) {
            if (! $nota->isDirty($campo)) {
                continue;
            }

            $valorAnterior = $this->normalizarValor($nota->getOriginal($campo));
            $valorNovo     = $this->normalizarValor($nota->{$campo});

            // Guarda contra log de "alteração" com valores idênticos após normalização.
            // Acontece quando o cast do Eloquent converte tipos (ex: "1" vs true).
            if ($valorAnterior === $valorNovo) {
                continue;
            }

            $this->registrarLog(
                $nota,
                'edicao',
                $campo,
                $valorAnterior,
                $valorNovo,
                $this->determinarTrimestre($campo)
            );
        }
    }

    public function deleted(Nota $nota): void
    {
        if (self::$suprimirLogs || Auth::guest()) {
            return;
        }

        $this->registrarLog($nota, 'exclusao', 'todos', null, null);
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    private function registrarLog(
        Nota $nota,
        string $acao,
        string $campo,
        mixed $valorAntigo,
        mixed $valorNovo,
        ?string $trimestre = null
    ): void {
        NotaLog::create([
            'nota_id'       => $nota->id,
            'usuario_id'    => Auth::id(),
            'aluno_id'      => $nota->aluno_id,
            'turma_id'      => $nota->turma_id,
            'disciplina_id' => $nota->disciplina_id,
            'acao'          => $acao,
            'campo_alterado'=> $campo,
            'valor_anterior'=> $valorAntigo,
            'valor_novo'    => $valorNovo,
            'trimestre'     => $trimestre,
            'motivo'        => request()?->input('motivo'),
            'ip_address'    => request()?->ip(),
            'data_alteracao'=> now(),
        ]);
    }

    private function normalizarValor(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) $valor;
    }

    private function determinarTrimestre(string $campo): ?string
    {
        return match (true) {
            in_array($campo, ['mac1', 'pp1', 'pt1', 'bloqueado_t1'], true) => '1',
            in_array($campo, ['mac2', 'pp2', 'pt2', 'bloqueado_t2'], true) => '2',
            in_array($campo, ['mac3', 'pp3', 'pg',  'bloqueado_t3'], true) => '3',
            default => null
        };
    }
}
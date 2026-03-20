<?php

namespace App\Observers;

use App\Models\Nota;
use App\Models\NotaLog;
use Illuminate\Support\Facades\Auth;

class NotaObserver
{
    public function created(Nota $nota): void
    {
        $this->registrarLog($nota, 'criacao', 'todas', null, null);
    }

    public function updating(Nota $nota): void
    {
        $camposMonitorados = [
            'mac1', 'pp1', 'pt1',
            'mac2', 'pp2', 'pt2',
            'mac3', 'pp3',
            'pg',
            'ca_10', 'ca_11',
            // Campos de bloqueio/finalização — antes ausentes
            'status',
            'bloqueado_t1', 'bloqueado_t2', 'bloqueado_t3',
        ];

        foreach ($camposMonitorados as $campo) {
            if ($nota->isDirty($campo)) {
                $this->registrarLog(
                    $nota,
                    'edicao',
                    $campo,
                    $nota->getOriginal($campo),
                    $nota->$campo,
                    $this->determinarTrimestre($campo)
                );
            }
        }
    }

    public function deleted(Nota $nota): void
    {
        $this->registrarLog($nota, 'exclusao', 'todas', null, null);
    }

    private function registrarLog(
        Nota $nota,
        string $acao,
        string $campo,
        mixed $valorAntigo,
        mixed $valorNovo,
        ?string $trimestre = null
    ): void {
        NotaLog::create([
            'nota_id'        => $nota->id,
            'usuario_id'     => Auth::id() ?? 1,
            'aluno_id'       => $nota->aluno_id,
            'turma_id'       => $nota->turma_id,
            'disciplina_id'  => $nota->disciplina_id,
            'acao'           => $acao,
            'campo_alterado' => $campo,
            'valor_anterior' => $valorAntigo,
            'valor_novo'     => $valorNovo,
            'trimestre'      => $trimestre,
            'ip_address'     => request()->ip(),
            'data_alteracao' => now(),
        ]);
    }

    private function determinarTrimestre(string $campo): ?string
    {
        return match(true) {
            in_array($campo, ['mac1', 'pp1', 'pt1', 'bloqueado_t1']) => '1',
            in_array($campo, ['mac2', 'pp2', 'pt2', 'bloqueado_t2']) => '2',
            in_array($campo, ['mac3', 'pp3', 'pg', 'bloqueado_t3']) => '3',
            default => null,
        };
    }
}
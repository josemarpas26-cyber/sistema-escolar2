<?php

namespace App\Observers;

use App\Models\Nota;
use App\Models\NotaLog;
use Illuminate\Support\Facades\Auth;

class NotaObserver
{
    /**
     * Registra a criação de uma nota
     */
    public function created(Nota $nota): void
    {
        $this->registrarLog($nota, 'criacao', 'todas', null, null);
    }

    /**
     * Registra as alterações antes de atualizar
     */
    public function updating(Nota $nota): void
    {
        // Campos que devem ser monitorados
        $camposMonitorados = [
            'mac1', 'pp1', 'pt1',
            'mac2', 'pp2', 'pt2',
            'mac3', 'pp3',
            'pg',
            'ca_10', 'ca_11',
        ];

        foreach ($camposMonitorados as $campo) {
            if ($nota->isDirty($campo)) {
                $valorAntigo = $nota->getOriginal($campo);
                $valorNovo = $nota->$campo;

                // Determinar trimestre
                $trimestre = $this->determinarTrimestre($campo);

                $this->registrarLog(
                    $nota,
                    'edicao',
                    $campo,
                    $valorAntigo,
                    $valorNovo,
                    $trimestre
                );
            }
        }
    }

    /**
     * Registra a exclusão de uma nota
     */
    public function deleted(Nota $nota): void
    {
        $this->registrarLog($nota, 'exclusao', 'todas', null, null);
    }

    /**
     * Cria o registro de log
     */
    private function registrarLog(
        Nota $nota,
        string $acao,
        string $campo,
        ?float $valorAntigo,
        ?float $valorNovo,
        ?string $trimestre = null
    ): void {
        NotaLog::create([
            'nota_id' => $nota->id,
            'usuario_id' => Auth::id() ?? 1, // Se não houver usuário autenticado, usa admin
            'aluno_id' => $nota->aluno_id,
            'turma_id' => $nota->turma_id,
            'disciplina_id' => $nota->disciplina_id,
            'acao' => $acao,
            'campo_alterado' => $campo,
            'valor_anterior' => $valorAntigo,
            'valor_novo' => $valorNovo,
            'trimestre' => $trimestre,
            'ip_address' => request()->ip(),
            'data_alteracao' => now(),
        ]);
    }

    /**
     * Determina o trimestre baseado no campo
     */
    private function determinarTrimestre(string $campo): ?string
    {
        if (in_array($campo, ['mac1', 'pp1', 'pt1'])) {
            return '1';
        }
        if (in_array($campo, ['mac2', 'pp2', 'pt2'])) {
            return '2';
        }
        if (in_array($campo, ['mac3', 'pp3'])) {
            return '3';
        }
        if ($campo === 'pg') {
            return '3';
        }
        return null;
    }
}
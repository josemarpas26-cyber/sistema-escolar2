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
            'mac1',
            'pp1',
            'pt1',
            'mac2',
            'pp2',
            'pt2',
            'mac3',
            'pp3',
            'pg',
            'ca_10',
            'ca_11',
            'status',
            'bloqueado_t1',
            'bloqueado_t2',
            'bloqueado_t3',
        ];

        $camposAlterados = [];

        foreach ($camposMonitorados as $campo) {
            if (!$nota->isDirty($campo)) {
                continue;
            }

            $valorAnterior = $this->normalizarValor($nota->getOriginal($campo));
            $valorNovo     = $this->normalizarValor($nota->{$campo});

            // Agrupado (JSON)
            $camposAlterados[$campo] = [
                'anterior' => $valorAnterior,
                'novo'     => $valorNovo,
                'trimestre'=> $this->determinarTrimestre($campo),
            ];

            // Detalhado (opcional - pode filtrar campos críticos aqui)
            $this->registrarLog(
                $nota,
                'edicao',
                $campo,
                $valorAnterior,
                $valorNovo,
                $this->determinarTrimestre($campo)
            );
        }

        // Log principal (1 único insert)
        if (!empty($camposAlterados)) {
            NotaLog::create([
                'nota_id'    => $nota->id,
                'tipo'       => 'edicao',
                'alteracoes' => json_encode($camposAlterados),
                'quantidade_campos' => count($camposAlterados),
            ]);
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
        $usuarioId = Auth::id();

        if ($usuarioId === null) {
            return;
        }

        NotaLog::create([
            'nota_id' => $nota->id,
            'usuario_id' => $usuarioId,
            'aluno_id' => $nota->aluno_id,
            'turma_id' => $nota->turma_id,
            'disciplina_id' => $nota->disciplina_id,
            'acao' => $acao,
            'campo_alterado' => $campo,
            'valor_anterior' => $valorAntigo,
            'valor_novo' => $valorNovo,
            'trimestre' => $trimestre,
            'motivo' => request()?->input('motivo'),
            'ip_address' => request()?->ip(),
            'data_alteracao' => now(),
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
            in_array($campo, ['mac3', 'pp3', 'pg', 'bloqueado_t3'], true) => '3',
            default => null,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'aluno_id',
        'turma_id',
        'disciplina_id',
        'ano_letivo_id',
        'mac1', 'pp1', 'pt1', 'mt1',
        'mac2', 'pp2', 'pt2', 'mt2', 'mft2',
        'mac3', 'pp3', 'mt3', 'cf',
        'pg',
        'ca',
        'cfd',
        'ca_10', 'ca_11',
        'status',
        'bloqueado_t1', 'bloqueado_t2', 'bloqueado_t3',
        'observacoes',
    ];

    protected $casts = [
        'mac1' => 'decimal:2', 'pp1' => 'decimal:2', 'pt1' => 'decimal:2', 'mt1' => 'decimal:2',
        'mac2' => 'decimal:2', 'pp2' => 'decimal:2', 'pt2' => 'decimal:2', 'mt2' => 'decimal:2', 'mft2' => 'decimal:2',
        'mac3' => 'decimal:2', 'pp3' => 'decimal:2', 'mt3' => 'decimal:2', 'cf' => 'decimal:2',
        'pg' => 'decimal:2',
        'ca' => 'decimal:2',
        'cfd' => 'decimal:2',
        'ca_10' => 'decimal:2', 'ca_11' => 'decimal:2',
        'bloqueado_t1' => 'boolean', 'bloqueado_t2' => 'boolean', 'bloqueado_t3' => 'boolean',
    ];

    // === RELACIONAMENTOS ===

    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class);
    }

    public function logs()
    {
        return $this->hasMany(NotaLog::class);
    }

    // === CÁLCULOS AUTOMÁTICOS ===

    /**
     * Recalcula todas as médias da nota
     */
// Nota.php — recalcular() com guard completo
    public function recalcular(): void
    {
        // Garantir que as relações necessárias estão carregadas
        if (!$this->relationLoaded('turma') || !$this->turma) {
            $this->load(['turma.curso', 'disciplina']);
        }
        
        if (!$this->turma) {
            throw new \RuntimeException("Nota {$this->id} não possui turma associada.");
        }

        $classe = $this->turma->classe;

        // 1º Trimestre
        if ($this->mac1 !== null && $this->pp1 !== null && $this->pt1 !== null) {
            $this->mt1 = round(($this->mac1 + $this->pp1 + $this->pt1) / 3, 2);
        }

        // 2º Trimestre
        if ($this->mac2 !== null && $this->pp2 !== null && $this->pt2 !== null) {
            $this->mt2 = round(($this->mac2 + $this->pp2 + $this->pt2) / 3, 2);
        }

        // MFT2
        if ($this->mt1 !== null && $this->mt2 !== null) {
            $this->mft2 = round(($this->mt1 + $this->mt2) / 2, 2);
        }

        match ($classe) {
            '10' => $this->calcularTrimestre3Classe10(),
            '11' => $this->calcularTrimestre3Classe11(),
            '12' => $this->calcularTrimestre3Classe12(),
            default => null,
        };
    }

    /**
     * 3º Trimestre - 10ª Classe
     */
    private function calcularTrimestre3Classe10(): void
    {
        // MT3 = (MAC3 + PP3) / 2
        if ($this->mac3 !== null && $this->pp3 !== null) {
            $this->mt3 = round(($this->mac3 + $this->pp3) / 2, 2);
        }

        // CF = (MFT2 + MT3) / 2
        if ($this->mft2 !== null && $this->mt3 !== null) {
            $this->cf = round(($this->mft2 + $this->mt3) / 2, 2);
        }

        // CA10ª = 0.6 × CF + 0.4 × PG
        if ($this->cf !== null && $this->pg !== null) {
            $this->ca = round((0.6 * $this->cf) + (0.4 * $this->pg), 2);
        }

        $this->aplicarRegraTerminalPorCurso(10);
    }

    /**
     * 3º Trimestre - 11ª Classe
     */
    private function calcularTrimestre3Classe11(): void
    {
        // MT3 = (MAC3 + PP3) / 2
        if ($this->mac3 !== null && $this->pp3 !== null) {
            $this->mt3 = round(($this->mac3 + $this->pp3) / 2, 2);
        }

        // CF = (MFT2 + MT3) / 2
        if ($this->mft2 !== null && $this->mt3 !== null) {
            $this->cf = round(($this->mft2 + $this->mt3) / 2, 2);
        }

        // CA11ª = 0.6 × CF + 0.4 × PG
        if ($this->cf !== null && $this->pg !== null) {
            $this->ca = round((0.6 * $this->cf) + (0.4 * $this->pg), 2);
        }

        // CFD = (CA10ª + CA11ª) / 2
        if ($this->ca_10 !== null && $this->ca !== null) {
            $this->cfd = round(($this->ca_10 + $this->ca) / 2, 2);
        }
         $this->aplicarRegraTerminalPorCurso(11);
    }

    /**
     * 3º Trimestre - 12ª Classe
     */
    private function calcularTrimestre3Classe12(): void
    {
        // MT3 = (MAC3 + PP3) / 2
        if ($this->mac3 !== null && $this->pp3 !== null) {
            $this->mt3 = round(($this->mac3 + $this->pp3) / 2, 2);
        }

        // CF = (MFT2 + MT3) / 2
        if ($this->mft2 !== null && $this->mt3 !== null) {
            $this->cf = round(($this->mft2 + $this->mt3) / 2, 2);
        }

        // CA12ª = 0.6 × CF + 0.4 × PG
        if ($this->cf !== null && $this->pg !== null) {
            $this->ca = round((0.6 * $this->cf) + (0.4 * $this->pg), 2);
        }

        // CFD = (CA10ª + CA11ª + CA12ª) / 3
        if ($this->ca_10 !== null && $this->ca_11 !== null && $this->ca !== null) {
            $this->cfd = round(($this->ca_10 + $this->ca_11 + $this->ca) / 3, 2);
        }

           $this->aplicarRegraTerminalPorCurso(12);
    }


        private function aplicarRegraTerminalPorCurso(int $classeAtual): void
        {
            $anoTerminal = $this->disciplina->anoTerminalParaCurso($this->turma?->curso_id);

            if ($anoTerminal !== $classeAtual) {
            return;
            }

            if ($classeAtual === 10 && $this->ca !== null) {
            $this->cfd = $this->ca;
            return;
        }

        if ($classeAtual === 11 && $this->ca_10 !== null && $this->ca !== null) {
            $this->cfd = round(($this->ca_10 + $this->ca) / 2, 2);
            return;
        }

        if ($classeAtual === 12 && $this->ca_10 !== null && $this->ca_11 !== null && $this->ca !== null) {
            $this->cfd = round(($this->ca_10 + $this->ca_11 + $this->ca) / 3, 2);
        }
    }

    // === HELPERS ===

    public function isAprovado(): bool
    {
        return $this->cfd !== null && $this->cfd >= 10;
    }

    public function getStatusFinalAttribute(): string
    {
        if ($this->cfd === null) {
            return 'Em andamento';
        }
        return $this->cfd >= 10 ? 'Aprovado' : 'Reprovado';
    }
}
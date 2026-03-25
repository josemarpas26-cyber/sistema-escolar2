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

    /**
     * Recalcula todos os campos derivados da nota.
     *
     * Nao dispara lazy-load. Exige que 'turma.curso' e 'disciplina'
     * estejam carregados antes da chamada.
     */
    public function recalcular(): void
    {
        if (!$this->relationLoaded('turma') || !$this->relationLoaded('disciplina')) {
            throw new \LogicException(
                "recalcular() chamado sem eager load em Nota #{$this->id}. " .
                "Use with(['turma.curso', 'disciplina']) antes de chamar recalcular()."
            );
        }

        if (!$this->turma) {
            throw new \RuntimeException("Nota {$this->id} nao possui turma associada.");
        }

        $classe = (int) $this->turma->classe;

        $this->mt1 = $this->mac1 !== null && $this->pp1 !== null && $this->pt1 !== null
            ? round(($this->mac1 + $this->pp1 + $this->pt1) / 3, 2)
            : null;

        $this->mt2 = $this->mac2 !== null && $this->pp2 !== null && $this->pt2 !== null
            ? round(($this->mac2 + $this->pp2 + $this->pt2) / 3, 2)
            : null;

        $this->mft2 = $this->mt1 !== null && $this->mt2 !== null
            ? round(($this->mt1 + $this->mt2) / 2, 2)
            : null;

        match ($classe) {
            10 => $this->calcularTrimestre3Classe10(),
            11 => $this->calcularTrimestre3Classe11(),
            12 => $this->calcularTrimestre3Classe12(),
            default => $this->limparCamposFinais(),
        };
    }

    private function calcularTrimestre3Classe10(): void
    {
        $this->mt3 = $this->mac3 !== null && $this->pp3 !== null
            ? round(($this->mac3 + $this->pp3) / 2, 2)
            : null;

        $this->cf = $this->mft2 !== null && $this->mt3 !== null
            ? round(($this->mft2 + $this->mt3) / 2, 2)
            : null;

        $this->ca = $this->cf !== null && $this->pg !== null
            ? round((0.6 * $this->cf) + (0.4 * $this->pg), 2)
            : null;

        $this->atualizarCfdParaClasseAtual(10);
    }

    private function calcularTrimestre3Classe11(): void
    {
        $this->mt3 = $this->mac3 !== null && $this->pp3 !== null
            ? round(($this->mac3 + $this->pp3) / 2, 2)
            : null;

        $this->cf = $this->mft2 !== null && $this->mt3 !== null
            ? round(($this->mft2 + $this->mt3) / 2, 2)
            : null;

        $this->ca = $this->cf !== null && $this->pg !== null
            ? round((0.6 * $this->cf) + (0.4 * $this->pg), 2)
            : null;

        $this->atualizarCfdParaClasseAtual(11);
    }

    private function calcularTrimestre3Classe12(): void
    {
        $this->mt3 = $this->mac3 !== null && $this->pp3 !== null
            ? round(($this->mac3 + $this->pp3) / 2, 2)
            : null;

        $this->cf = $this->mft2 !== null && $this->mt3 !== null
            ? round(($this->mft2 + $this->mt3) / 2, 2)
            : null;

        $this->ca = $this->cf !== null && $this->pg !== null
            ? round((0.6 * $this->cf) + (0.4 * $this->pg), 2)
            : null;

        $this->atualizarCfdParaClasseAtual(12);
    }

    private function atualizarCfdParaClasseAtual(int $classeAtual): void
    {
        $this->cfd = null;

        if ($this->ca === null) {
            return;
        }

        $classificacoes = [];

        if ($this->disciplina->leciona_10 && $classeAtual >= 10) {
            $ca10 = $classeAtual === 10 ? $this->ca : $this->ca_10;

            if ($ca10 === null) {
                return;
            }

            $classificacoes[] = $ca10;
        }

        if ($this->disciplina->leciona_11 && $classeAtual >= 11) {
            $ca11 = $classeAtual === 11 ? $this->ca : $this->ca_11;

            if ($ca11 === null) {
                return;
            }

            $classificacoes[] = $ca11;
        }

        if ($this->disciplina->leciona_12 && $classeAtual >= 12) {
            $classificacoes[] = $this->ca;
        }

        if (empty($classificacoes)) {
            $classificacoes[] = $this->ca;
        }

        $this->cfd = round(array_sum($classificacoes) / count($classificacoes), 2);
    }

    private function limparCamposFinais(): void
    {
        $this->mt3 = null;
        $this->cf = null;
        $this->ca = null;
        $this->cfd = null;
    }

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

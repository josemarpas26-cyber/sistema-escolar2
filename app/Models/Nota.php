<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    public const SENTINELA_AUSENCIA = -1.0;

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
        'usar_divisao_aritmetica_por_2',
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
        'usar_divisao_aritmetica_por_2' => 'boolean',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relacionamentos                                                    */
    /* ------------------------------------------------------------------ */

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

    public function solicitacoesDivisaoAritmetica()
    {
        return $this->hasMany(DivisaoAritmeticaSolicitacao::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Recálculo público                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Recalcula todos os campos derivados da nota.
     *
     * Exige que 'turma' e 'disciplina' estejam eager-loaded e não-nulos.
     * Exemplo: $nota->load(['turma', 'disciplina'])->recalcular();
     */
    public function recalcular(): void
    {
        $this->assertRelacoesCarregadas();

        $classe = (int) $this->turma->classe;

        $this->calcularMediasTrimestre1e2();

        match ($classe) {
            10, 11, 12 => $this->calcularTrimestre3($classe),
            default    => $this->limparCamposFinais(),
        };
    }

    /* ------------------------------------------------------------------ */
    /*  Guardas                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Garante que turma e disciplina estão eager-loaded e não são null.
     *
     * Chamada em qualquer ponto que precise dessas relações,
     * evitando lazy-load silencioso e "property on null".
     */
    private function assertRelacoesCarregadas(): void
    {
        if (!$this->relationLoaded('turma') || !$this->relationLoaded('disciplina')) {
            throw new \LogicException(
                "Relações não carregadas em Nota #{$this->id}. "
                . "Use load(['turma', 'disciplina']) ou with(['turma', 'disciplina']) antes de chamar recalcular()."
            );
        }

        if (!$this->turma) {
            throw new \RuntimeException(
                "Nota #{$this->id} não possui turma associada."
            );
        }

        if (!$this->disciplina) {
            throw new \RuntimeException(
                "Nota #{$this->id} não possui disciplina associada."
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Cálculos internos                                                  */
    /* ------------------------------------------------------------------ */

    private function calcularMediasTrimestre1e2(): void
    {
        $this->mt1 = $this->calcularMediaComSentinela([
            $this->mac1,
            $this->pp1,
            $this->pt1,
        ], 3);

        $this->mt2 = $this->calcularMediaComSentinela([
            $this->mac2,
            $this->pp2,
            $this->pt2,
        ], 3);

        $mediasDisponiveis = collect([$this->mt1, $this->mt2])->filter(fn ($valor) => $valor !== null)->values();

        if ($mediasDisponiveis->count() === 2) {
            $this->mft2 = round($mediasDisponiveis->sum() / 2, 2);

            return;
        }

        $this->mft2 = $this->usar_divisao_aritmetica_por_2 && $mediasDisponiveis->count() === 1
            ? round((float) $mediasDisponiveis->first(), 2)
            : null;
    }

    /**
     * Trimestre 3 + campos finais — lógica idêntica para classes 10, 11, 12.
     */
    private function calcularTrimestre3(int $classe): void
    {
        $this->mt3 = $this->calcularMediaComSentinela([
            $this->mac3,
            $this->pp3,
        ], 2);

        $this->cf = $this->calcularClassificacaoFinalComRegraEspecial();

        $this->ca = $this->cf !== null && $this->pg !== null
            ? round((0.6 * $this->cf) + (0.4 * $this->pg), 2)
            : null;

        $this->atualizarCfd($classe);
    }

    private function calcularMediaComSentinela(array $campos, int $divisor): ?float
    {
        if (collect($campos)->contains(fn ($valor) => $valor === null)) {
            return null;
        }

        if (collect($campos)->every(fn ($valor) => (float) $valor === self::SENTINELA_AUSENCIA)) {
            return null;
        }

        return round(array_sum($campos) / $divisor, 2);
    }

    private function calcularClassificacaoFinalComRegraEspecial(): ?float
    {
        if (! $this->usar_divisao_aritmetica_por_2) {
            return $this->mft2 !== null && $this->mt3 !== null
                ? round(($this->mft2 + $this->mt3) / 2, 2)
                : null;
        }

        $trimestres = collect([$this->mt1, $this->mt2, $this->mt3])
            ->filter(fn ($valor) => $valor !== null)
            ->values();

        if ($trimestres->count() < 2) {
            return null;
        }

        if ($trimestres->count() === 2) {
            return round($trimestres->sum() / 2, 2);
        }

        return $this->mft2 !== null && $this->mt3 !== null
            ? round(($this->mft2 + $this->mt3) / 2, 2)
            : null;
    }

    /**
     * Calcula o CFD usando a disciplina JÁ CARREGADA (nunca faz lazy-load).
     *
     * Recebe a disciplina explicitamente via $this->disciplina que foi
     * validada por assertRelacoesCarregadas().
     */
    private function atualizarCfd(int $classeAtual): void
    {
        $this->cfd = null;

        if ($this->ca === null) {
            return;
        }

        // $disciplina é garantidamente não-nula aqui (assertRelacoesCarregadas).
        $disciplina = $this->disciplina;

        $classificacoes = [];

        if ($disciplina->leciona_10 && $classeAtual >= 10) {
            $ca10 = $classeAtual === 10 ? $this->ca : $this->ca_10;

            if ($ca10 === null) {
                return;
            }

            $classificacoes[] = $ca10;
        }

        if ($disciplina->leciona_11 && $classeAtual >= 11) {
            $ca11 = $classeAtual === 11 ? $this->ca : $this->ca_11;

            if ($ca11 === null) {
                return;
            }

            $classificacoes[] = $ca11;
        }

        if ($disciplina->leciona_12 && $classeAtual >= 12) {
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
        $this->cf  = null;
        $this->ca  = null;
        $this->cfd = null;
    }

    /* ------------------------------------------------------------------ */
    /*  Acessores / helpers públicos                                       */
    /* ------------------------------------------------------------------ */

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

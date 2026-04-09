<?php

namespace App\Models;

use App\Models\AvaliacaoContinua;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
        'bloqueado_pp1', 'bloqueado_pt1', 'bloqueado_pp2', 'bloqueado_pt2', 'bloqueado_pp3', 'bloqueado_pg',
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
        'bloqueado_pp1' => 'boolean', 'bloqueado_pt1' => 'boolean', 'bloqueado_pp2' => 'boolean',
        'bloqueado_pt2' => 'boolean', 'bloqueado_pp3' => 'boolean', 'bloqueado_pg' => 'boolean',
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


    public function avaliacoesContinuas()
    {
        return $this->hasMany(AvaliacaoContinua::class)->orderBy('trimestre')->orderBy('data_avaliacao')->orderBy('id');
    }

    public function recalcular(): void
    {
        $this->assertRelacoesCarregadas();

        $classe = (int) $this->turma->classe;

        $this->calcularMediasTrimestre1e2();

        match ($classe) {
            10, 11, 12 => $this->calcularTrimestre3($classe),
            default => $this->limparCamposFinais(),
        };
    }

    private function assertRelacoesCarregadas(): void
    {
        if (! $this->relationLoaded('turma') || ! $this->relationLoaded('disciplina')) {
            throw new \LogicException(
                "Relacoes nao carregadas em Nota #{$this->id}. "
                . "Use load(['turma', 'disciplina']) ou with(['turma', 'disciplina']) antes de chamar recalcular()."
            );
        }

        if (! $this->turma) {
            throw new \RuntimeException("Nota #{$this->id} nao possui turma associada.");
        }

        if (! $this->disciplina) {
            throw new \RuntimeException("Nota #{$this->id} nao possui disciplina associada.");
        }
    }

    private function calcularMediasTrimestre1e2(): void
    {
        $this->limparTrimestresNaoAplicaveis();

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

        if (! $this->trimestreEstaDisponivel(2)) {
            $this->mft2 = null;

            return;
        }

        if (! $this->trimestreEstaDisponivel(1)) {
            $this->mft2 = $this->mt2 !== null
                ? round((float) $this->mt2, 2)
                : null;

            return;
        }

        $this->mft2 = $this->mt1 !== null && $this->mt2 !== null
            ? round(($this->mt1 + $this->mt2) / 2, 2)
            : null;
    }

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
        if ($this->trimestreEstaDisponivel(1)) {
            return $this->mft2 !== null && $this->mt3 !== null
                ? round(($this->mft2 + $this->mt3) / 2, 2)
                : null;
        }

        return $this->mt2 !== null && $this->mt3 !== null
            ? round(($this->mt2 + $this->mt3) / 2, 2)
            : null;
    }

    private function limparTrimestresNaoAplicaveis(): void
    {
        if (! $this->trimestreEstaDisponivel(1)) {
            $this->mac1 = null;
            $this->pp1 = null;
            $this->pt1 = null;
            $this->mt1 = null;
        }

        if (! $this->trimestreEstaDisponivel(2)) {
            $this->mac2 = null;
            $this->pp2 = null;
            $this->pt2 = null;
            $this->mt2 = null;
            $this->mft2 = null;
        }
    }

    public function trimestreEstaDisponivel(int $trimestre): bool
    {
        return $trimestre >= $this->trimestreInicialDisponivel();
    }

    public function trimestreInicialDisponivel(): int
    {
        $matricula = $this->dataMatriculaNaTurma();
        $inicioAno = $this->anoLetivo?->data_inicio?->copy()?->startOfDay();
        $fimAno = $this->anoLetivo?->data_fim?->copy()?->startOfDay();

        if (! $matricula || ! $inicioAno || ! $fimAno || $fimAno->lte($inicioAno)) {
            return 1;
        }

        $inicioT2 = $this->inicioDoTrimestre($inicioAno, $fimAno, 2);
        $inicioT3 = $this->inicioDoTrimestre($inicioAno, $fimAno, 3);

        return match (true) {
            $matricula->gte($inicioT3) => 3,
            $matricula->gte($inicioT2) => 2,
            default => 1,
        };
    }

    public function ingressouAposPrimeiroTrimestre(): bool
    {
        return $this->trimestreInicialDisponivel() > 1;
    }

    private function dataMatriculaNaTurma(): ?Carbon
    {
        $turmaAtual = $this->aluno?->turmas?->firstWhere('id', $this->turma_id);
        $dataMatricula = $turmaAtual?->pivot?->data_matricula;

        if ($dataMatricula instanceof Carbon) {
            return $dataMatricula->copy()->startOfDay();
        }

        if (blank($dataMatricula)) {
            return null;
        }

        return Carbon::parse($dataMatricula)->startOfDay();
    }

    private function inicioDoTrimestre(Carbon $inicioAno, Carbon $fimAno, int $trimestre): Carbon
    {
        $duracaoTotal = $inicioAno->diffInDays($fimAno) + 1;
        $duracaoTrimestre = (int) ceil($duracaoTotal / 3);

        return $inicioAno->copy()->addDays(($trimestre - 1) * $duracaoTrimestre);
    }

    private function atualizarCfd(int $classeAtual): void
    {
        $this->cfd = null;

        if ($this->ca === null) {
            return;
        }

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

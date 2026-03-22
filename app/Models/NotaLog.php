<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaLog extends Model
{
    use HasFactory;

    protected $table = 'notas_logs';

    public $timestamps = false;

    protected $fillable = [
        'nota_id',
        'usuario_id',
        'aluno_id',
        'turma_id',
        'disciplina_id',
        'acao',
        'campo_alterado',
        'valor_anterior',
        'valor_novo',
        'trimestre',
        'motivo',
        'ip_address',
        'data_alteracao',
    ];

    protected $casts = [
        // valor_anterior e valor_novo são string(20) após a migration de correção
        // para suportar tanto valores numéricos como textuais (ex: 'finalizado')
        'data_alteracao' => 'datetime',
    ];

    // ── Relacionamentos ──────────────────────────────────────────────────────

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    /**
     * withTrashed: mantém o log visível mesmo se o utilizador for deletado.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }

    /**
     * withTrashed: mantém o log visível mesmo se o aluno for deletado.
     */
    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id')->withTrashed();
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getDescricaoAcaoAttribute(): string
    {
        return match ($this->acao) {
            'criacao'  => 'Criou',
            'edicao'   => 'Editou',
            'exclusao' => 'Excluiu',
            default    => 'Ação desconhecida',
        };
    }

    public function getTipoBadgeAcaoAttribute(): string
    {
        return match ($this->acao) {
            'criacao'  => 'success',
            'exclusao' => 'danger',
            default    => 'info',
        };
    }

    public function getDescricaoCampoAttribute(): string
    {
        $campos = [
            'mac1'        => 'MAC 1º Trimestre',
            'pp1'         => 'PP 1º Trimestre',
            'pt1'         => 'PT 1º Trimestre',
            'mac2'        => 'MAC 2º Trimestre',
            'pp2'         => 'PP 2º Trimestre',
            'pt2'         => 'PT 2º Trimestre',
            'mac3'        => 'MAC 3º Trimestre',
            'pp3'         => 'PP 3º Trimestre',
            'pg'          => 'Prova Global',
            'ca_10'       => 'CA 10ª Classe',
            'ca_11'       => 'CA 11ª Classe',
            'status'      => 'Estado da pauta',
            'bloqueado_t1'=> 'Bloqueio 1º Trimestre',
            'bloqueado_t2'=> 'Bloqueio 2º Trimestre',
            'bloqueado_t3'=> 'Bloqueio 3º Trimestre',
        ];

        return $campos[$this->campo_alterado] ?? $this->campo_alterado;
    }

    public function getResumoAlteracaoAttribute(): string
    {
        if ($this->acao === 'criacao') {
            return 'Registo criado';
        }

        if ($this->acao === 'exclusao') {
            return 'Registo removido';
        }

        $anterior = $this->formatarValor($this->valor_anterior);
        $novo     = $this->formatarValor($this->valor_novo);

        return "{$anterior} → {$novo}";
    }

    /**
     * Formata o valor para exibição: numérico com 2 casas, texto como está.
     */
    private function formatarValor(mixed $valor): string
    {
        if ($valor === null) {
            return '—';
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, ',', '.');
        }

        // Valores textuais como 'finalizado', 'em_lancamento', etc.
        $labels = [
            'finalizado'    => 'Finalizado',
            'em_lancamento' => 'Em lançamento',
            'true'          => 'Sim',
            'false'         => 'Não',
            '1'             => 'Sim',
            '0'             => 'Não',
        ];

        return $labels[$valor] ?? $valor;
    }
}
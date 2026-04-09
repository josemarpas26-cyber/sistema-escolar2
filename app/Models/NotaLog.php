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
        'acao_global',
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
        'acao_global' => 'boolean',
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
            'criacao' => 'Criou',
            'edicao' => 'Editou',
            'exclusao' => 'Excluiu',
            'finalizacao' => 'Finalizou',
            'reabertura' => 'Reabriu',
            'avaliacao_continua_criada' => 'Criou avaliação contínua',
            'avaliacao_continua_editada' => 'Editou avaliação contínua',
            'avaliacao_continua_removida' => 'Removeu avaliação contínua',
            default => 'Ação desconhecida',
        };
    }

    public function getTipoBadgeAcaoAttribute(): string
    {
        return match ($this->acao) {
            'criacao' => 'success',
            'exclusao' => 'danger',
            'reabertura' => 'warning',
            'finalizacao' => 'secondary',
            'avaliacao_continua_criada' => 'success',
            'avaliacao_continua_editada' => 'info',
            'avaliacao_continua_removida' => 'danger',
            default => 'info',
        };
    }

    public function getDescricaoCampoAttribute(): string
    {
        $campos = [
            'mac1' => 'MAC 1º Trimestre',
            'pp1' => 'PP 1º Trimestre',
            'pt1' => 'PT 1º Trimestre',
            'mac2' => 'MAC 2º Trimestre',
            'pp2' => 'PP 2º Trimestre',
            'pt2' => 'PT 2º Trimestre',
            'mac3' => 'MAC 3º Trimestre',
            'pp3' => 'PP 3º Trimestre',
            'pg' => 'Prova Global',
            'ca_10' => 'CA 10ª Classe',
            'ca_11' => 'CA 11ª Classe',
            'status' => 'Estado da pauta',
            'bloqueado_t1' => 'Bloqueio 1º Trimestre',
            'bloqueado_t2' => 'Bloqueio 2º Trimestre',
            'bloqueado_t3' => 'Bloqueio 3º Trimestre',
            'bloqueado_pp1' => 'Bloqueio PP 1º Trimestre',
            'bloqueado_pt1' => 'Bloqueio PT 1º Trimestre',
            'bloqueado_pp2' => 'Bloqueio PP 2º Trimestre',
            'bloqueado_pt2' => 'Bloqueio PT 2º Trimestre',
            'bloqueado_pp3' => 'Bloqueio PP 3º Trimestre',
            'bloqueado_pg' => 'Bloqueio PG',
            'avaliacao_continua' => 'Avaliação contínua',
        ];

        $campos['pauta_completa'] = 'Pauta completa';

        return $campos[$this->campo_alterado] ?? $this->campo_alterado;
    }

    public function getAlvoExibicaoAttribute(): string
    {
        if ($this->acao_global) {
            return 'Todos os alunos';
        }

        return optional($this->aluno)->name ?? '-';
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
        $novo = $this->formatarValor($this->valor_novo);

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

        $avaliacaoFormatada = $this->formatarValorAvaliacaoContinua($valor);
        if ($avaliacaoFormatada !== null) {
            return $avaliacaoFormatada;
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, ',', '.');
        }

        // Valores textuais como 'finalizado', 'em_lancamento', etc.
        $labels = [
            'finalizado' => 'Finalizado',
            'em_lancamento' => 'Em lançamento',
            'true' => 'Sim',
            'false' => 'Não',
            '1' => 'Sim',
            '0' => 'Não',
        ];

        return $labels[$valor] ?? $valor;
    }

    private function formatarValorAvaliacaoContinua(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $dados = json_decode($valor, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($dados)) {
            return null;
        }

        $descricao = trim((string) ($dados['descricao'] ?? 'Sem descrição'));
        if ($descricao === '') {
            $descricao = 'Sem descrição';
        }

        $valorAvaliacao = array_key_exists('valor', $dados) && is_numeric($dados['valor'])
            ? number_format((float) $dados['valor'], 2, ',', '.')
            : '—';

        $data = $dados['data_avaliacao'] ?? null;
        if (! $data) {
            $dataFormatada = 'Sem data';
        } else {
            try {
                $dataFormatada = \Illuminate\Support\Carbon::parse($data)->format('d/m/Y');
            } catch (\Throwable) {
                $dataFormatada = (string) $data;
            }
        }

        return "{$descricao} | {$valorAvaliacao} | {$dataFormatada}";
    }
}

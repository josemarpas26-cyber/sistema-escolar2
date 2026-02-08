<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaLog extends Model
{
    use HasFactory;

    protected $table = 'notas_logs';

    public $timestamps = false; // Usamos apenas data_alteracao

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
        'valor_anterior' => 'decimal:2',
        'valor_novo' => 'decimal:2',
        'data_alteracao' => 'datetime',
    ];

    // === RELACIONAMENTOS ===

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

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

    // === HELPERS ===

    public function getDescricaoAcaoAttribute(): string
    {
        return match($this->acao) {
            'criacao' => 'Criou',
            'edicao' => 'Editou',
            'exclusao' => 'Excluiu',
            default => 'Ação desconhecida',
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
        ];

        return $campos[$this->campo_alterado] ?? $this->campo_alterado;
    }
}
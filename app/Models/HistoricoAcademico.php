<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoAcademico extends Model
{
    use HasFactory;

    protected $table = 'historico_academico';

    protected $fillable = [
        'aluno_id',
        'turma_id',
        'disciplina_id',
        'ano_letivo_id',
        'classe',
        'classificacao_final',
        'resultado',
        'observacoes',
        'data_conclusao',
    ];

    protected $casts = [
        'classificacao_final' => 'decimal:2',
        'data_conclusao' => 'datetime',
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

    // === SCOPES ===

    public function scopePorAluno($query, int $alunoId)
    {
        return $query->where('aluno_id', $alunoId)
            ->orderBy('classe')
            ->orderBy('ano_letivo_id');
    }

    // === HELPERS ===

    public function isAprovado(): bool
    {
        return $this->resultado === 'aprovado';
    }
}
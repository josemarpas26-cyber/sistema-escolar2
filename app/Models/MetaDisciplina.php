<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaDisciplina extends Model
{
    use HasFactory;

    protected $table = 'metas_disciplina';

    protected $fillable = [
        'aluno_id',
        'disciplina_id',
        'ano_letivo_id',
        'meta_nota',
        'data_definicao',
        'data_conclusao_prevista',
        'status',
    ];

    protected $casts = [
        'meta_nota' => 'decimal:2',
        'data_definicao' => 'date',
        'data_conclusao_prevista' => 'date',
    ];

    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class, 'ano_letivo_id');
    }

    public function scopeAtivas($query)
    {
        return $query->where('status', 'ativa');
    }
}

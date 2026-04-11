<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoDinamica extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes_dinamicas';

    protected $fillable = [
        'ano_letivo_id',
        'disciplina_id',
        'formula_avaliacao_id',
        'nome',
        'tipo',
        'peso',
        'excecoes',
    ];

    protected $casts = [
        'peso' => 'decimal:2',
        'excecoes' => 'array',
    ];

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class);
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function formula()
    {
        return $this->belongsTo(FormulaAvaliacao::class, 'formula_avaliacao_id');
    }
}

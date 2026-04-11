<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormulaAvaliacaoVersao extends Model
{
    use HasFactory;

    protected $table = 'formula_avaliacao_versoes';

    protected $fillable = [
        'formula_avaliacao_id',
        'versao',
        'componentes',
        'regras',
        'motivo',
        'criado_por',
    ];

    protected $casts = [
        'componentes' => 'array',
        'regras' => 'array',
    ];

    public function formula()
    {
        return $this->belongsTo(FormulaAvaliacao::class, 'formula_avaliacao_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}

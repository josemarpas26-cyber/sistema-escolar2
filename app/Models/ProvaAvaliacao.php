<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvaAvaliacao extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'provas_avaliacao';

    protected $fillable = [
        'configuracao_avaliacao_id',
        'periodo',
        'nome',
        'codigo',
        'peso',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'periodo' => 'integer',
        'peso' => 'decimal:4',
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function configuracao()
    {
        return $this->belongsTo(ConfiguracaoAvaliacao::class, 'configuracao_avaliacao_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DivisaoAritmeticaSolicitacao extends Model
{
    use HasFactory;

    protected $table = 'divisao_aritmetica_solicitacoes';

    protected $fillable = [
        'nota_id',
        'professor_id',
        'coordenador_id',
        'status',
        'mensagem',
        'respondida_em',
        'respondida_por',
        'resposta',
    ];

    protected $casts = [
        'respondida_em' => 'datetime',
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function coordenador()
    {
        return $this->belongsTo(User::class, 'coordenador_id');
    }

    public function respondedor()
    {
        return $this->belongsTo(User::class, 'respondida_por');
    }
}

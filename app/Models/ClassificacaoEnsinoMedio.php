<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificacaoEnsinoMedio extends Model
{
    use HasFactory;

    protected $table = 'classificacoes_ensino_medio';

    protected $fillable = [
        'aluno_id',
        'turma_id',
        'ano_letivo_id',
        'pap',
        'ecs',
        'observacoes',
    ];

    protected $casts = [
        'pap' => 'decimal:2',
        'ecs' => 'decimal:2',
    ];

    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class);
    }
}

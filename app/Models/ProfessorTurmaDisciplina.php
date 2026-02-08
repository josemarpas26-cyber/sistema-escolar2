<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorTurmaDisciplina extends Model
{
    use HasFactory;

    protected $table = 'professor_turma_disciplina';

    protected $fillable = [
        'professor_id',
        'turma_id',
        'disciplina_id',
        'ano_letivo_id',
    ];

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
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

    // Scope para filtrar atribuições do ano atual
    public function scopeAnoAtivo($query)
    {
        return $query->whereHas('anoLetivo', fn($q) => $q->ativo());
    }
}
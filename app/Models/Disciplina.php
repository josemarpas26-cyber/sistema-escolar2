<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disciplina extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'leciona_10',
        'leciona_11',
        'leciona_12',
        'disciplina_terminal',
        'ativo',
    ];

    protected $casts = [
        'leciona_10' => 'boolean',
        'leciona_11' => 'boolean',
        'leciona_12' => 'boolean',
        'disciplina_terminal' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_disciplina')
            ->withTimestamps();
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    public function atribuicoes()
    {
        return $this->hasMany(ProfessorTurmaDisciplina::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // Verifica se a disciplina é lecionada em determinada classe
    public function isLecionadaEm(string $classe): bool
    {
        return match($classe) {
            '10' => $this->leciona_10,
            '11' => $this->leciona_11,
            '12' => $this->leciona_12,
            default => false,
        };
    }
}
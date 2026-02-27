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

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_disciplina')
            ->withPivot('ano_terminal')
            ->withTimestamps();
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
    public function anoTerminalParaCurso(?int $cursoId): ?int
        {
            if (!$cursoId) {
                return null;
            }

            $relacao = $this->cursos()
                ->where('curso_id', $cursoId)
                ->first();

            if (!$relacao) {
                return $this->disciplina_terminal ? 10 : null;
            }

            return $relacao->pivot->ano_terminal !== null
                ? (int) $relacao->pivot->ano_terminal
                : null;
        }
}
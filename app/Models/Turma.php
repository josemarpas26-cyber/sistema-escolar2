<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turma extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'classe',
        'curso_id',
        'ano_letivo_id',
        'coordenador_turma_id',
        'capacidade',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'capacidade' => 'integer',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class);
    }

    public function coordenador()
    {
        return $this->belongsTo(User::class, 'coordenador_turma_id');
    }

    public function alunos()
    {
        return $this->belongsToMany(User::class, 'turma_aluno', 'turma_id', 'aluno_id')
            ->withPivot('data_matricula', 'status')
            ->withTimestamps();
    }

    public function disciplinas()
    {
        return $this->belongsToMany(Disciplina::class, 'turma_disciplina')
            ->withTimestamps();
    }

    public function professores()
    {
        return $this->belongsToMany(User::class, 'professor_turma_disciplina', 'turma_id', 'professor_id')
            ->withPivot('disciplina_id', 'ano_letivo_id')
            ->withTimestamps();
    }

    public function atribuicoes()
    {
        return $this->hasMany(ProfessorTurmaDisciplina::class);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeAnoAtivo($query)
    {
        return $query->whereHas('anoLetivo', fn($q) => $q->ativo());
    }

    // Helper: nome completo da turma
    public function getNomeCompletoAttribute(): string
    {
        return "{$this->classe}ª {$this->nome} - {$this->curso->nome}";
    }

    // Contagem de alunos
    public function getTotalAlunosAttribute(): int
    {
        return $this->alunos()->wherePivot('status', 'matriculado')->count();
    }

    public function hasVagas(): bool
    {
        return $this->totalAlunos < $this->capacidade;
    }
}
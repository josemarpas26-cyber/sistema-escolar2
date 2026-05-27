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
        'coordenador_id',
        'leciona_10',
        'leciona_11',
        'leciona_12',
        'leciona_13',
        'disciplina_terminal',
        'ativo',
    ];

    protected $casts = [
        'leciona_10' => 'boolean',
        'leciona_11' => 'boolean',
        'leciona_12' => 'boolean',
        'leciona_13' => 'boolean',
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

    public function coordenador()
    {
        return $this->belongsTo(User::class, 'coordenador_id');
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
            '13' => $this->leciona_13,
            default => false,
        };
    }

    public function ehTerminalNaTurma(Turma $turma): bool
    {
        $classeAtual = (int) $turma->classe;
        $cursoId = $turma->curso_id;

        if ($this->relationLoaded('cursos')) {
            $relacaoCurso = $this->cursos->firstWhere('id', $cursoId);

            if (! $relacaoCurso) {
                if ($this->cursos->isEmpty()) {
                    return $this->disciplinaTerminalInferida() === $classeAtual;
                }

                return false;
            }

            $anoTerminal = $relacaoCurso->pivot->ano_terminal;

            return $anoTerminal !== null && (int) $anoTerminal === $classeAtual;
        }

        return $this->anoTerminalParaCurso($cursoId) === $classeAtual;
    }

    public function anoTerminalParaCurso(?int $cursoId): ?int
    {
        if (! $cursoId) {
            return null;
        }

        $relacao = $this->cursos()
            ->where('curso_id', $cursoId)
            ->first();

        if (! $relacao) {
            $temRelacaoComAlgumCurso = $this->cursos()->exists();

            return $temRelacaoComAlgumCurso
                ? null
                : $this->disciplinaTerminalInferida();
        }

        return $relacao->pivot->ano_terminal !== null
            ? (int) $relacao->pivot->ano_terminal
            : null;
    }

    private function disciplinaTerminalInferida(): ?int
    {
        if (! $this->disciplina_terminal) {
            return null;
        }

        if ($this->leciona_13 && ! $this->leciona_10 && ! $this->leciona_11 && ! $this->leciona_12) {
            return 13;
        }

        if ($this->leciona_12 && ! $this->leciona_10 && ! $this->leciona_11) {
            return 12;
        }

        if ($this->leciona_11 && ! $this->leciona_10) {
            return 11;
        }

        return 10;
    }
}

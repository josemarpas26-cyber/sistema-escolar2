<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'bi',
        'data_nascimento',
        'genero',
        'telefone',
        'endereco',
        'foto_perfil',
        'numero_processo',
        'nome_encarregado',
        'contacto_encarregado',
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'data_nascimento' => 'date',
        'ativo' => 'boolean',
    ];

    // === RELACIONAMENTOS ===

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Para alunos
    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->withPivot('data_matricula', 'status')
            ->withTimestamps();
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'aluno_id');
    }

    public function historicoAcademico()
    {
        return $this->hasMany(HistoricoAcademico::class, 'aluno_id');
    }

    // Para professores
    public function atribuicoes()
    {
        return $this->hasMany(ProfessorTurmaDisciplina::class, 'professor_id');
    }

    public function turmasLecionadas()
    {
        return $this->belongsToMany(Turma::class, 'professor_turma_disciplina', 'professor_id', 'turma_id')
            ->withPivot('disciplina_id', 'ano_letivo_id')
            ->withTimestamps();
    }

    // Para coordenadores
    public function cursoCoordenado()
    {
        return $this->hasOne(Curso::class, 'coordenador_id');
    }

    public function turmaCoordenada()
    {
        return $this->hasOne(Turma::class, 'coordenador_turma_id');
    }

    // === SCOPES ===

    public function scopeAlunos($query)
    {
        return $query->whereHas('role', fn($q) => $q->where('name', 'aluno'));
    }

    public function scopeProfessores($query)
    {
        return $query->whereHas('role', fn($q) => $q->where('name', 'professor'));
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // === HELPERS ===

    public function isAdmin(): bool
    {
        return $this->role->name === 'admin';
    }

    public function isSecretaria(): bool
    {
        return $this->role->name === 'secretaria';
    }

    public function isProfessor(): bool
    {
        return $this->role->name === 'professor';
    }

    public function isAluno(): bool
    {
        return $this->role->name === 'aluno';
    }

    public function isCoordenadorCurso(): bool
    {
        return $this->cursoCoordenado()->exists();
    }

    public function isCoordenadorTurma(): bool
    {
        return $this->turmaCoordenada()->exists();
    }

    public function getFotoPerfilUrlAttribute(): string
    {
        if ($this->foto_perfil) {
            return asset('storage/' . $this->foto_perfil);
        }
        
        // Imagem padrão baseada no gênero
        $default = $this->genero === 'F' ? 'default-female.png' : 'default-male.png';
        return asset('images/' . $default);
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\HasMaskedRouteKey;
use App\Notifications\CustomResetPasswordNotification;
use App\Support\ProfilePhotoStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasMaskedRouteKey;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
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

    public function eventosCalendario()
    {
        return $this->hasMany(CalendarioEvento::class, 'professor_id');
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

    public function disciplinaCoordenada()
    {
        return $this->hasOne(Disciplina::class, 'coordenador_id');
    }

    // === SCOPES ===

    public function scopeAlunos($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', 'aluno'));
    }

    public function scopeProfessores($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', 'professor'));
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // === HELPERS ===

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isSecretaria(): bool
    {
        return $this->role?->name === 'secretaria';
    }

    public function isProfessor(): bool
    {
        return $this->role?->name === 'professor';
    }

    public function isAluno(): bool
    {
        return $this->role?->name === 'aluno';
    }

    public function isProgramador(): bool
    {
        return $this->role?->name === 'programador'
            || $this->email === config('app.programador_email');
    }

    public function isCoordenadorCurso(): bool
    {
        return $this->cursoCoordenado()->exists();
    }

    public function isCoordenadorTurma(): bool
    {
        return $this->turmaCoordenada()->exists();
    }

    public function isCoordenadorDisciplina(): bool
    {
        return $this->disciplinaCoordenada()->exists();
    }

    public function hasPermission(string $permissionName): bool
    {
        $this->loadMissing('role.permissions');

        return $this->role?->hasPermission($permissionName) ?? false;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * Accessor para foto de perfil em base64 (para PDFs e pre-visualizacoes).
     *
     * Retorna data URI pronto para usar em <img src="...">.
     */
    public function getFotoPerfilPdfSrcAttribute(): string
    {
        $fotoSrc = ProfilePhotoStorage::dataUri($this->foto_perfil ?? $this->foto ?? null);

        if ($fotoSrc) {
            return $fotoSrc;
        }

        // Fallback: imagem padrao (tambem em base64)
        return $this->fotoPerfilPadraoBase64();
    }

    /**
     * Retorna a imagem padrao (masculina/feminina) em base64.
     */
    private function fotoPerfilPadraoBase64(): string
    {
        $arquivo = $this->genero === 'F' ? 'default-female.png' : 'default-male.png';
        $caminho = public_path('images/' . $arquivo);

        if (file_exists($caminho)) {
            $data = base64_encode(file_get_contents($caminho));
            $mime = mime_content_type($caminho);

            return "data:{$mime};base64,{$data}";
        }

        // Se nem a imagem padrao existir, retorna um avatar SVG generico
        return $this->avatarSvgFallback();
    }

    /**
     * Avatar SVG generico (funciona sempre, sem ficheiros externos).
     */
    private function avatarSvgFallback(): string
    {
        $cor = $this->genero === 'F' ? '#EC4899' : '#3B82F6';

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect fill="#E5E7EB" width="100" height="100"/>
            <circle fill="{$cor}" cx="50" cy="35" r="20"/>
            <ellipse fill="{$cor}" cx="50" cy="85" rx="35" ry="25"/>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function fotoPerfilPadraoArquivo(): string
    {
        return $this->genero === 'F' ? 'default-female.png' : 'default-male.png';
    }

    public function getFotoPerfilUrlAttribute(): string
    {
        $fotoPerfilUrl = ProfilePhotoStorage::url($this->foto_perfil);

        if ($fotoPerfilUrl) {
            return $fotoPerfilUrl;
        }

        // Imagem padrao baseada no genero
        $default = $this->fotoPerfilPadraoArquivo();

        return asset('images/' . $default);
    }
}

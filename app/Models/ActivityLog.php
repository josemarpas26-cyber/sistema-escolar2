<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_logs';

    protected $fillable = [
        'usuario_id',
        'acao',
        'modelo',
        'modelo_id',
        'dados_anteriores',
        'ip_address',
        'criado_em',
    ];

    protected $casts = [
        'dados_anteriores' => 'array',
        'criado_em'        => 'datetime',
    ];

    // ── Relacionamentos ──────────────────────────────────────────────────────

    /**
     * Quem executou a ação.
     * withTrashed para manter histórico mesmo se o admin for deletado.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }

    // ── Labels legíveis ──────────────────────────────────────────────────────

    public function getDescricaoAcaoAttribute(): string
    {
        return match ($this->acao) {
            'user_deleted'  => 'Deletou utilizador',
            'user_restored' => 'Restaurou utilizador',
            default         => ucfirst(str_replace('_', ' ', $this->acao)),
        };
    }

    public function getTipoBadgeAttribute(): string
    {
        return match ($this->acao) {
            'user_deleted'  => 'danger',
            'user_restored' => 'success',
            default         => 'info',
        };
    }

    // ── Helpers estáticos ────────────────────────────────────────────────────

    /**
     * Regista qualquer ação genérica.
     */
    public static function registar(string $acao, Model $modelo, ?array $dados = null): void
    {
        static::create([
            'usuario_id'       => auth()->id(),
            'acao'             => $acao,
            'modelo'           => class_basename($modelo),
            'modelo_id'        => $modelo->getKey(),
            'dados_anteriores' => $dados,
            'ip_address'       => request()->ip(),
            'criado_em'        => now(),
        ]);
    }

    /**
     * Snapshot completo antes de deletar um utilizador.
     */
    public static function registarDelecao(User $user): void
    {
        static::registar('user_deleted', $user, [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'role'                  => $user->role?->name,
            'role_display'          => $user->role?->display_name,
            'numero_processo'       => $user->numero_processo,
            'bi'                    => $user->bi,
            'telefone'              => $user->telefone,
            'ativo'                 => $user->ativo,
            'turmas'                => $user->turmas()
                                        ->withPivot('status', 'data_matricula')
                                        ->get()
                                        ->map(fn($t) => [
                                            'id'             => $t->id,
                                            'nome'           => $t->nome_completo,
                                            'status'         => $t->pivot->status,
                                            'data_matricula' => $t->pivot->data_matricula,
                                        ])->toArray(),
        ]);
    }

    /**
     * Regista restauração de utilizador.
     */
    public static function registarRestauracao(User $user): void
    {
        static::registar('user_restored', $user, [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role?->name,
        ]);
    }
}
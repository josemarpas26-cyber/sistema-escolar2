<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    // Cache em memória por instância — vive só durante o request
    private ?Collection $permissionsCache = null;

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Carrega todas as permissões do role numa única query
     * e reutiliza o resultado durante o request inteiro.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->permissionsCache === null) {
            // Se já foi eager-loaded (via ::with), usa sem nova query.
            // Se não foi, carrega agora e armazena.
            $this->permissionsCache = $this->relationLoaded('permissions')
                ? $this->permissions
                : $this->permissions()->pluck('name');
        }

        return $this->permissionsCache->contains($permissionName);
    }

    /**
     * Invalida o cache em memória, se as permissões do role
     * forem alteradas durante o mesmo request (ex: testes, seeds).
     */
    public function flushPermissionsCache(): void
    {
        $this->permissionsCache = null;
        $this->unsetRelation('permissions');
    }
}
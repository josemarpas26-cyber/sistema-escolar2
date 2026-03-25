<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Role extends Model
{
    use HasFactory;

    private const REQUEST_CACHE_KEY = 'role.permission_names';

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    // In-memory cache per model instance for the current request.
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
     * Loads the role permissions once and reuses them throughout the request.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->getPermissionNames()->contains($permissionName);
    }

    /**
     * Clears the in-memory/request cache when permissions change in the same request.
     */
    public function flushPermissionsCache(): void
    {
        $this->permissionsCache = null;
        $this->unsetRelation('permissions');
        $this->forgetRequestCachedPermissionNames();
    }

    private function getPermissionNames(): Collection
    {
        if ($this->permissionsCache instanceof Collection) {
            return $this->permissionsCache;
        }

        $cachedPermissionNames = $this->getRequestCachedPermissionNames();

        if ($cachedPermissionNames instanceof Collection) {
            return $this->permissionsCache = $cachedPermissionNames;
        }

        $permissionNames = $this->relationLoaded('permissions')
            ? $this->permissions->pluck('name')
            : $this->permissions()->pluck('name');

        $permissionNames = $permissionNames->values();

        $this->permissionsCache = $permissionNames;
        $this->storeRequestCachedPermissionNames($permissionNames);

        return $permissionNames;
    }

    private function getRequestCachedPermissionNames(): ?Collection
    {
        $roleId = $this->getKey();

        if ($roleId === null || !app()->bound('request')) {
            return null;
        }

        return request()->attributes->get(self::REQUEST_CACHE_KEY, [])[$roleId] ?? null;
    }

    private function storeRequestCachedPermissionNames(Collection $permissionNames): void
    {
        $roleId = $this->getKey();

        if ($roleId === null || !app()->bound('request')) {
            return;
        }

        $cache = request()->attributes->get(self::REQUEST_CACHE_KEY, []);
        $cache[$roleId] = $permissionNames;

        request()->attributes->set(self::REQUEST_CACHE_KEY, $cache);
    }

    private function forgetRequestCachedPermissionNames(): void
    {
        $roleId = $this->getKey();

        if ($roleId === null || !app()->bound('request')) {
            return;
        }

        $cache = request()->attributes->get(self::REQUEST_CACHE_KEY, []);
        unset($cache[$roleId]);

        request()->attributes->set(self::REQUEST_CACHE_KEY, $cache);
    }
}

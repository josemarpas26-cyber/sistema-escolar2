<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_has_permission_works_with_eager_loaded_permissions(): void
    {
        $role = $this->createRoleWithPermission('users.create');

        $loadedRole = Role::with('permissions')->findOrFail($role->id);

        $this->assertTrue($loadedRole->hasPermission('users.create'));
        $this->assertFalse($loadedRole->hasPermission('users.delete'));
    }

    public function test_role_reuses_permission_names_across_fresh_instances_in_same_request(): void
    {
        $role = $this->createRoleWithPermission('users.create');

        $firstInstance = Role::query()->findOrFail($role->id);
        $secondInstance = Role::query()->findOrFail($role->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue($firstInstance->hasPermission('users.create'));

        DB::flushQueryLog();

        $this->assertTrue($secondInstance->hasPermission('users.create'));
        $this->assertCount(0, DB::getQueryLog());
    }

    private function createRoleWithPermission(string $permissionName): Role
    {
        $role = Role::create([
            'name' => 'role-' . str_replace('.', '-', $permissionName),
            'display_name' => 'Role ' . $permissionName,
        ]);

        $permission = Permission::create([
            'name' => $permissionName,
            'display_name' => 'Permission ' . $permissionName,
        ]);

        $role->permissions()->attach($permission);

        return $role;
    }
}

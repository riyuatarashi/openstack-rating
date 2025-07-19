<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [];
        $permissionNames = [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'view_permissions',
            'assign_permissions',
        ];

        foreach ($permissionNames as $permissionName) {
            $permissions[] = Permission::findOrCreate($permissionName);
        }

        Role::findOrCreate('admin')
            ->givePermissionTo($permissions);
    }
}

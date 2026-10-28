<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate(User::SUPER_ADMIN, 'web');

        foreach (Permissions::ROLES as $role => $modules) {
            Role::findOrCreate($role, 'web')->syncPermissions(Permissions::forModules($modules));
        }
    }
}

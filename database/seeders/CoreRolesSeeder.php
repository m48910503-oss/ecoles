<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\CoreRoles;
use Illuminate\Database\Seeder;

class CoreRolesSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIdsByName = Permission::query()->pluck('id', 'name');

        foreach (CoreRoles::definitions() as $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                    'is_optional' => $definition['is_optional'],
                    'is_active' => ! $definition['is_optional'],
                ]
            );

            $permissionIds = collect(CoreRoles::permissionMap()[$definition['slug']] ?? [])
                ->map(fn (string $permissionName) => $permissionIdsByName[$permissionName] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\CorePermissions;
use Illuminate\Database\Seeder;

class CorePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CorePermissions::definitions() as $definition) {
            Permission::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'module' => $definition['module'],
                    'description' => $definition['description'],
                ]
            );
        }
    }
}

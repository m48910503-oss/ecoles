<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\AcademicPermissions;
use Illuminate\Database\Seeder;

class AcademicPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AcademicPermissions::definitions() as $definition) {
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

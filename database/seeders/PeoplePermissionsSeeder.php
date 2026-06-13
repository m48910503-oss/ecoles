<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PeoplePermissions;
use Illuminate\Database\Seeder;

class PeoplePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PeoplePermissions::definitions() as $definition) {
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

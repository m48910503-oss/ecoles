<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoreSystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CorePermissionsSeeder::class,
            PeoplePermissionsSeeder::class,
            CoreRolesSeeder::class,
            AcademicYearSeeder::class,
            SystemSettingSeeder::class,
            SchoolSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}

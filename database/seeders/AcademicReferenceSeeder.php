<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AcademicReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AcademicPermissionsSeeder::class,
            PeoplePermissionsSeeder::class,
            AcademicCycleSeeder::class,
            AcademicYearCycleSeeder::class,
            AcademicLevelSeeder::class,
            AcademicSeriesSeeder::class,
            AcademicLevelSeriesSeeder::class,
            AcademicYearAvailabilitySeeder::class,
            AcademicSubjectSeeder::class,
            CoreRolesSeeder::class,
        ]);
    }
}

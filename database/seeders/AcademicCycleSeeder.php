<?php

namespace Database\Seeders;

use App\Enums\Academic\AcademicCycleCategory;
use App\Models\AcademicCycle;
use Illuminate\Database\Seeder;

class AcademicCycleSeeder extends Seeder
{
    public function run(): void
    {
        $cycles = [
            ['name' => 'Maternelle', 'code' => 'MATERNELLE', 'category' => AcademicCycleCategory::Preschool->value, 'description' => 'Cycle préscolaire', 'sort_order' => 1],
            ['name' => 'Primaire', 'code' => 'PRIMAIRE', 'category' => AcademicCycleCategory::Primary->value, 'description' => 'Cycle primaire', 'sort_order' => 2],
            ['name' => 'Secondaire premier cycle', 'code' => 'SECONDAIRE-1', 'category' => AcademicCycleCategory::LowerSecondary->value, 'description' => 'Premier cycle du secondaire', 'sort_order' => 3],
            ['name' => 'Secondaire second cycle', 'code' => 'SECONDAIRE-2', 'category' => AcademicCycleCategory::UpperSecondary->value, 'description' => 'Second cycle du secondaire', 'sort_order' => 4],
        ];

        foreach ($cycles as $cycle) {
            AcademicCycle::query()->updateOrCreate(
                ['code' => $cycle['code']],
                $cycle + ['is_active' => true]
            );
        }
    }
}

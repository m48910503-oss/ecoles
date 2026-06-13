<?php

namespace Database\Seeders;

use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearCycleSeeder extends Seeder
{
    public function run(): void
    {
        $cycleIds = AcademicCycle::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id');

        if ($cycleIds->isEmpty()) {
            return;
        }

        AcademicYear::query()
            ->doesntHave('cycles')
            ->get()
            ->each(fn (AcademicYear $academicYear) => $academicYear->cycles()->sync($cycleIds->all()));
    }
}

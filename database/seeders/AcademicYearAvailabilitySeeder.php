<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::query()
            ->with('cycles:id')
            ->get()
            ->each(function (AcademicYear $academicYear): void {
                $cycleIds = $academicYear->cycles
                    ->pluck('id')
                    ->map(fn ($cycleId) => (int) $cycleId)
                    ->all();

                if ($cycleIds === []) {
                    return;
                }

                $levelIds = AcademicLevel::query()
                    ->where('is_active', true)
                    ->whereIn('cycle_id', $cycleIds)
                    ->pluck('id')
                    ->all();

                $seriesIds = AcademicSeries::query()
                    ->where('is_active', true)
                    ->whereIn('cycle_id', $cycleIds)
                    ->pluck('id')
                    ->all();

                if ($levelIds !== []) {
                    $academicYear->levels()->syncWithoutDetaching($levelIds);
                }

                if ($seriesIds !== []) {
                    $academicYear->series()->syncWithoutDetaching($seriesIds);
                }
            });
    }
}

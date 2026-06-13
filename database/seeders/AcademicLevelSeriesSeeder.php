<?php

namespace Database\Seeders;

use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use Illuminate\Database\Seeder;

class AcademicLevelSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->first();

        if (! $cycle) {
            return;
        }

        $seriesIds = AcademicSeries::query()
            ->where('cycle_id', $cycle->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id')
            ->all();

        if ($seriesIds === []) {
            return;
        }

        AcademicLevel::query()
            ->where('cycle_id', $cycle->id)
            ->where('uses_series', true)
            ->get()
            ->each(fn (AcademicLevel $level) => $level->series()->sync($seriesIds));
    }
}

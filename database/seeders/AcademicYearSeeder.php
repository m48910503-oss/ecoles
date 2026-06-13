<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $startYear = $today->month >= 8 ? $today->year : $today->year - 1;
        $endYear = $startYear + 1;

        AcademicYear::query()->updateOrCreate(
            ['code' => "{$startYear}-{$endYear}"],
            [
                'name' => "{$startYear}-{$endYear}",
                'start_date' => CarbonImmutable::create($startYear, 9, 1, 0, 0, 0, config('app.timezone'))->toDateString(),
                'end_date' => CarbonImmutable::create($endYear, 7, 31, 0, 0, 0, config('app.timezone'))->toDateString(),
                'is_active' => true,
                'enrollment_open' => true,
                'notes' => "Année académique initiale générée lors de l'installation du socle Core System.",
            ]
        );

        AcademicYear::query()
            ->where('code', '!=', "{$startYear}-{$endYear}")
            ->update(['is_active' => false]);
    }
}

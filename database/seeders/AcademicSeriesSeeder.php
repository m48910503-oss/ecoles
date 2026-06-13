<?php

namespace Database\Seeders;

use App\Models\AcademicCycle;
use App\Models\AcademicSeries;
use Illuminate\Database\Seeder;

class AcademicSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->first();

        if (! $cycle) {
            return;
        }

        $series = [
            ['name' => 'Série A', 'code' => 'SERIE-A', 'short_name' => 'A', 'description' => 'Orientation littéraire', 'sort_order' => 1],
            ['name' => 'Série C', 'code' => 'SERIE-C', 'short_name' => 'C', 'description' => 'Orientation scientifique mathématiques', 'sort_order' => 2],
            ['name' => 'Série D', 'code' => 'SERIE-D', 'short_name' => 'D', 'description' => 'Orientation scientifique SVT', 'sort_order' => 3],
        ];

        foreach ($series as $item) {
            AcademicSeries::query()->updateOrCreate(
                ['code' => $item['code']],
                $item + [
                    'cycle_id' => $cycle->id,
                    'is_active' => true,
                ]
            );
        }
    }
}

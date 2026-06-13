<?php

namespace Database\Seeders;

use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use Illuminate\Database\Seeder;

class AcademicLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'MATERNELLE' => [
                ['name' => 'Petite section', 'code' => 'PS', 'short_name' => 'PS', 'sort_order' => 1, 'uses_series' => false],
                ['name' => 'Moyenne section', 'code' => 'MS', 'short_name' => 'MS', 'sort_order' => 2, 'uses_series' => false],
                ['name' => 'Grande section', 'code' => 'GS', 'short_name' => 'GS', 'sort_order' => 3, 'uses_series' => false],
            ],
            'PRIMAIRE' => [
                ['name' => 'CI', 'code' => 'CI', 'short_name' => 'CI', 'sort_order' => 1, 'uses_series' => false],
                ['name' => 'CP', 'code' => 'CP', 'short_name' => 'CP', 'sort_order' => 2, 'uses_series' => false],
                ['name' => 'CE1', 'code' => 'CE1', 'short_name' => 'CE1', 'sort_order' => 3, 'uses_series' => false],
                ['name' => 'CE2', 'code' => 'CE2', 'short_name' => 'CE2', 'sort_order' => 4, 'uses_series' => false],
                ['name' => 'CM1', 'code' => 'CM1', 'short_name' => 'CM1', 'sort_order' => 5, 'uses_series' => false],
                ['name' => 'CM2', 'code' => 'CM2', 'short_name' => 'CM2', 'sort_order' => 6, 'uses_series' => false],
            ],
            'SECONDAIRE-1' => [
                ['name' => '6e', 'code' => '6E', 'short_name' => '6e', 'sort_order' => 1, 'uses_series' => false],
                ['name' => '5e', 'code' => '5E', 'short_name' => '5e', 'sort_order' => 2, 'uses_series' => false],
                ['name' => '4e', 'code' => '4E', 'short_name' => '4e', 'sort_order' => 3, 'uses_series' => false],
                ['name' => '3e', 'code' => '3E', 'short_name' => '3e', 'sort_order' => 4, 'uses_series' => false],
            ],
            'SECONDAIRE-2' => [
                ['name' => 'Seconde', 'code' => 'SECONDE', 'short_name' => '2nde', 'sort_order' => 1, 'uses_series' => true],
                ['name' => 'Première', 'code' => 'PREMIERE', 'short_name' => '1ere', 'sort_order' => 2, 'uses_series' => true],
                ['name' => 'Terminale', 'code' => 'TERMINALE', 'short_name' => 'Tle', 'sort_order' => 3, 'uses_series' => true],
            ],
        ];

        foreach ($levels as $cycleCode => $items) {
            $cycle = AcademicCycle::query()->where('code', $cycleCode)->first();

            if (! $cycle) {
                continue;
            }

            foreach ($items as $item) {
                AcademicLevel::query()->updateOrCreate(
                    ['code' => $item['code']],
                    $item + [
                        'cycle_id' => $cycle->id,
                        'description' => null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_year_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_level_id')->constrained('academic_levels')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'academic_level_id'], 'academic_year_level_unique');
            $table->index(['academic_level_id', 'academic_year_id'], 'academic_year_level_lookup');
        });

        Schema::create('academic_year_series', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_series_id')->constrained('academic_series')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'academic_series_id'], 'academic_year_series_unique');
            $table->index(['academic_series_id', 'academic_year_id'], 'academic_year_series_lookup');
        });

        $this->backfillExistingAcademicYears();
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_year_series');
        Schema::dropIfExists('academic_year_levels');
    }

    private function backfillExistingAcademicYears(): void
    {
        $yearCycles = DB::table('academic_year_cycles')
            ->select(['academic_year_id', 'academic_cycle_id'])
            ->orderBy('academic_year_id')
            ->get();

        if ($yearCycles->isEmpty()) {
            return;
        }

        $levelsByCycle = DB::table('academic_levels')
            ->select(['id', 'cycle_id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->groupBy('cycle_id');

        $seriesByCycle = DB::table('academic_series')
            ->select(['id', 'cycle_id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->groupBy('cycle_id');

        $now = now();
        $levelRows = [];
        $seriesRows = [];

        foreach ($yearCycles as $yearCycle) {
            foreach ($levelsByCycle->get($yearCycle->academic_cycle_id, collect()) as $level) {
                $levelRows[] = [
                    'academic_year_id' => $yearCycle->academic_year_id,
                    'academic_level_id' => $level->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ($seriesByCycle->get($yearCycle->academic_cycle_id, collect()) as $series) {
                $seriesRows[] = [
                    'academic_year_id' => $yearCycle->academic_year_id,
                    'academic_series_id' => $series->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($levelRows !== []) {
            DB::table('academic_year_levels')->insert($levelRows);
        }

        if ($seriesRows !== []) {
            DB::table('academic_year_series')->insert($seriesRows);
        }
    }
};

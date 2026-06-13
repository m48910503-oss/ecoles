<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_year_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_cycle_id')->constrained('academic_cycles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'academic_cycle_id'], 'academic_year_cycle_unique');
            $table->index(['academic_cycle_id', 'academic_year_id'], 'academic_year_cycle_lookup');
        });

        $yearIds = DB::table('academic_years')->pluck('id');
        $cycleIds = DB::table('academic_cycles')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id');

        if ($yearIds->isEmpty() || $cycleIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($yearIds as $yearId) {
            foreach ($cycleIds as $cycleId) {
                $rows[] = [
                    'academic_year_id' => $yearId,
                    'academic_cycle_id' => $cycleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('academic_year_cycles')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_year_cycles');
    }
};

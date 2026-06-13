<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_level_series', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('level_id')->constrained('academic_levels')->cascadeOnDelete();
            $table->foreignId('series_id')->constrained('academic_series')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['level_id', 'series_id'], 'academic_level_series_unique');
            $table->index(['series_id', 'level_id'], 'academic_level_series_series_level_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_level_series');
    }
};

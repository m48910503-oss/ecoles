<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_coefficients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('academic_levels')->restrictOnDelete();
            $table->foreignId('series_id')->nullable()->constrained('academic_series')->nullOnDelete();
            $table->foreignId('academic_subject_id')->constrained('academic_subjects')->restrictOnDelete();
            $table->decimal('coefficient', 5, 2);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'is_active'], 'academic_coefficients_school_year_active_index');
            $table->index(['level_id', 'series_id'], 'academic_coefficients_level_series_index');
            $table->index(['academic_subject_id'], 'academic_coefficients_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_coefficients');
    }
};

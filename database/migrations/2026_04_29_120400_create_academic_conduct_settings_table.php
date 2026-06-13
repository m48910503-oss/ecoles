<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_conduct_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('academic_levels')->restrictOnDelete();
            $table->foreignId('academic_subject_id')->constrained('academic_subjects')->restrictOnDelete();
            $table->decimal('base_score', 5, 2)->default(20);
            $table->boolean('deductions_from_sanctions_only')->default(true);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year_id', 'level_id'], 'academic_conduct_settings_unique');
            $table->index(['school_id', 'academic_year_id', 'is_active'], 'academic_conduct_settings_school_year_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_conduct_settings');
    }
};

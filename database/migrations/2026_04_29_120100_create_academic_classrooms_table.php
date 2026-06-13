<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_classrooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('academic_levels')->restrictOnDelete();
            $table->foreignId('series_id')->nullable()->constrained('academic_series')->nullOnDelete();
            $table->string('name');
            $table->string('code', 100);
            $table->unsignedInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year_id', 'code'], 'academic_classrooms_school_year_code_unique');
            $table->index(['school_id', 'academic_year_id', 'is_active'], 'academic_classrooms_school_year_active_index');
            $table->index(['level_id', 'series_id'], 'academic_classrooms_level_series_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_classrooms');
    }
};

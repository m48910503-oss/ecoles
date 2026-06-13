<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_subject_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('academic_subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('academic_classroom_id')->constrained('academic_classrooms')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique([
                'teacher_id',
                'academic_subject_id',
                'academic_classroom_id',
                'academic_year_id',
            ], 'teacher_subject_assignments_unique');
            $table->index(['school_id', 'teacher_id'], 'teacher_subject_assignments_school_teacher_index');
            $table->index(['school_id', 'academic_subject_id'], 'teacher_subject_assignments_school_subject_index');
            $table->index(['school_id', 'academic_classroom_id'], 'teacher_subject_assignments_school_classroom_index');
            $table->index(['school_id', 'academic_year_id'], 'teacher_subject_assignments_school_year_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_assignments');
    }
};

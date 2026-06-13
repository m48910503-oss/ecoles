<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('matricule', 60);
            $table->string('last_name');
            $table->string('first_names');
            $table->string('gender', 20);
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('academic_classroom_id')->nullable()->constrained('academic_classrooms')->nullOnDelete();
            $table->foreignId('entry_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('status', 40)->default('preinscrit')->index();
            $table->string('status_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('matricule', 'students_matricule_unique');
            $table->index(['school_id', 'status'], 'students_school_status_index');
            $table->index(['school_id', 'academic_classroom_id'], 'students_school_classroom_index');
            $table->index(['school_id', 'last_name', 'first_names'], 'students_school_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

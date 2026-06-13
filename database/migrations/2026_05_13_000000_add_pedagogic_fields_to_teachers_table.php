<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->foreignId('academic_cycle_id')
                ->nullable()
                ->after('user_id')
                ->constrained('academic_cycles')
                ->nullOnDelete();
            $table->foreignId('main_classroom_id')
                ->nullable()
                ->after('academic_cycle_id')
                ->constrained('academic_classrooms')
                ->nullOnDelete();
            $table->foreignId('main_subject_id')
                ->nullable()
                ->after('main_classroom_id')
                ->constrained('academic_subjects')
                ->nullOnDelete();

            $table->index(['school_id', 'academic_cycle_id'], 'teachers_school_cycle_index');
            $table->index(['school_id', 'main_classroom_id'], 'teachers_school_main_classroom_index');
            $table->index(['school_id', 'main_subject_id'], 'teachers_school_main_subject_index');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropIndex('teachers_school_cycle_index');
            $table->dropIndex('teachers_school_main_classroom_index');
            $table->dropIndex('teachers_school_main_subject_index');

            $table->dropConstrainedForeignId('academic_cycle_id');
            $table->dropConstrainedForeignId('main_classroom_id');
            $table->dropConstrainedForeignId('main_subject_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_day_configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 20)->index();
            $table->foreignId('cycle_id')->nullable()->constrained('academic_cycles')->nullOnDelete();
            $table->string('day_type', 20)->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('afternoon_start_time')->nullable();
            $table->time('afternoon_end_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_locked')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'academic_year_id', 'scope_type'], 'school_day_scope_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_day_configurations');
    }
};

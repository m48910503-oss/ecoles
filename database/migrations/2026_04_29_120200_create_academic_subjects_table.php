<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 100);
            $table->string('short_name', 100)->nullable();
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->boolean('is_optional_by_default')->default(false);
            $table->boolean('is_conduct')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'academic_subjects_school_code_unique');
            $table->index(['school_id', 'category', 'is_active'], 'academic_subjects_school_category_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_subjects');
    }
};

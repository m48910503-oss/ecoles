<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cycle_id')->constrained('academic_cycles')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('short_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('uses_series')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['cycle_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};

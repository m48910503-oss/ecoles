<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('last_name');
            $table->string('first_names');
            $table->string('gender', 20);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('profession')->nullable();
            $table->string('status', 40)->default('actif')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'parents_school_status_index');
            $table->index(['school_id', 'last_name', 'first_names'], 'parents_school_name_index');
            $table->index(['school_id', 'phone'], 'parents_school_phone_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};

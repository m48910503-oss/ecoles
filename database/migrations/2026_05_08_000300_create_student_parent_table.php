<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_parent', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->string('relationship_type', 40);
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_financial_responsible')->default(false);
            $table->boolean('can_receive_notifications')->default(true);
            $table->boolean('can_pickup_child')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'parent_id'], 'student_parent_unique');
            $table->index(['student_id', 'is_primary_contact'], 'student_parent_primary_index');
            $table->index(['student_id', 'is_financial_responsible'], 'student_parent_financial_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_parent');
    }
};

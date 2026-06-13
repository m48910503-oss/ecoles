<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('recipient_email')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('status', 30)->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'teacher_id'], 'teacher_invitations_school_teacher_index');
            $table->index(['school_id', 'status'], 'teacher_invitations_school_status_index');
            $table->index(['school_id', 'expires_at'], 'teacher_invitations_school_expires_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_invitations');
    }
};

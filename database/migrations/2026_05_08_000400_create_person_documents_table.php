<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->morphs('documentable');
            $table->string('document_type', 60);
            $table->string('original_name');
            $table->string('file_path', 512);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('is_sensitive')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'document_type'], 'person_documents_school_type_index');
            $table->index(['school_id', 'is_sensitive'], 'person_documents_school_sensitive_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_documents');
    }
};

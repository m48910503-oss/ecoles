<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('matricule', 60);
            $table->string('last_name');
            $table->string('first_names');
            $table->string('gender', 20);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('position', 120);
            $table->string('system_role', 40)->nullable();
            $table->string('contract_type', 40)->default('contractuel');
            $table->date('service_start_date')->nullable();
            $table->string('status', 40)->default('actif')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('matricule', 'staff_matricule_unique');
            $table->unique('user_id', 'staff_user_unique');
            $table->index(['school_id', 'status'], 'staff_school_status_index');
            $table->index(['school_id', 'last_name', 'first_names'], 'staff_school_name_index');
            $table->index(['school_id', 'phone'], 'staff_school_phone_index');
            $table->index(['school_id', 'contract_type'], 'staff_school_contract_index');
            $table->index(['school_id', 'position'], 'staff_school_position_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};

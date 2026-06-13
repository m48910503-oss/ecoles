<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_calendar_id')->constrained('academic_calendars')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type', 40)->index();
            $table->dateTime('start_datetime')->index();
            $table->dateTime('end_datetime');
            $table->boolean('is_full_day')->default(false);
            $table->string('location')->nullable();
            $table->string('target_type', 20)->default('global')->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->string('visibility', 20)->default('internal')->index();
            $table->boolean('requires_validation')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('calendar_events', 'academic_period_id')) {
                $table->foreignId('academic_period_id')
                    ->nullable()
                    ->after('academic_calendar_id')
                    ->constrained('academic_periods')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            if (Schema::hasColumn('calendar_events', 'academic_period_id')) {
                $table->dropConstrainedForeignId('academic_period_id');
            }
        });
    }
};

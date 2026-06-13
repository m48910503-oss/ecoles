<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_calendars', function (Blueprint $table): void {
            if (! Schema::hasColumn('academic_calendars', 'academic_configuration_id')) {
                $table->foreignId('academic_configuration_id')
                    ->nullable()
                    ->after('academic_year_id')
                    ->constrained('academic_configurations')
                    ->nullOnDelete();
            }
        });

        Schema::create('academic_configuration_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_configuration_id')->constrained('academic_configurations')->cascadeOnDelete();
            $table->foreignId('academic_cycle_id')->constrained('academic_cycles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_configuration_id', 'academic_cycle_id'], 'academic_configuration_cycle_unique');
            $table->index(['academic_cycle_id', 'academic_configuration_id'], 'academic_configuration_cycle_lookup');
        });

        Schema::create('academic_calendar_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_calendar_id')->constrained('academic_calendars')->cascadeOnDelete();
            $table->foreignId('academic_cycle_id')->constrained('academic_cycles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_calendar_id', 'academic_cycle_id'], 'academic_calendar_cycle_unique');
            $table->index(['academic_cycle_id', 'academic_calendar_id'], 'academic_calendar_cycle_lookup');
        });

        $this->backfillConfigurationCycles();
        $this->backfillCalendarConfigurationLink();
        $this->backfillCalendarCycles();
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_cycles');
        Schema::dropIfExists('academic_configuration_cycles');

        Schema::table('academic_calendars', function (Blueprint $table): void {
            if (Schema::hasColumn('academic_calendars', 'academic_configuration_id')) {
                $table->dropConstrainedForeignId('academic_configuration_id');
            }
        });
    }

    private function backfillConfigurationCycles(): void
    {
        $configurations = DB::table('academic_configurations')
            ->select(['id', 'scope_type', 'cycle_id'])
            ->where('scope_type', 'cycle')
            ->whereNotNull('cycle_id')
            ->orderBy('id')
            ->get();

        foreach ($configurations as $configuration) {
            DB::table('academic_configuration_cycles')->updateOrInsert(
                [
                    'academic_configuration_id' => $configuration->id,
                    'academic_cycle_id' => $configuration->cycle_id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function backfillCalendarConfigurationLink(): void
    {
        $configurations = DB::table('academic_configurations')
            ->select(['id', 'school_id', 'academic_year_id', 'scope_type', 'cycle_id'])
            ->orderBy('id')
            ->get();

        foreach ($configurations as $configuration) {
            $calendarQuery = DB::table('academic_calendars')
                ->where('school_id', $configuration->school_id)
                ->where('academic_year_id', $configuration->academic_year_id)
                ->where('scope_type', $configuration->scope_type);

            if ($configuration->scope_type === 'cycle') {
                $calendarQuery->where('cycle_id', $configuration->cycle_id);
            } else {
                $calendarQuery->whereNull('cycle_id');
            }

            $calendarId = $calendarQuery->value('id');

            if (! $calendarId) {
                continue;
            }

            DB::table('academic_calendars')
                ->where('id', $calendarId)
                ->update([
                    'academic_configuration_id' => $configuration->id,
                    'updated_at' => now(),
                ]);
        }
    }

    private function backfillCalendarCycles(): void
    {
        $calendars = DB::table('academic_calendars')
            ->select(['id', 'academic_configuration_id', 'scope_type', 'cycle_id'])
            ->orderBy('id')
            ->get();

        foreach ($calendars as $calendar) {
            if ($calendar->academic_configuration_id) {
                $cycleIds = DB::table('academic_configuration_cycles')
                    ->where('academic_configuration_id', $calendar->academic_configuration_id)
                    ->pluck('academic_cycle_id');

                if ($cycleIds->isEmpty() && $calendar->cycle_id) {
                    $cycleIds = collect([$calendar->cycle_id]);
                }
            } elseif ($calendar->scope_type === 'cycle' && $calendar->cycle_id) {
                $cycleIds = collect([$calendar->cycle_id]);
            } else {
                $cycleIds = collect();
            }

            foreach ($cycleIds->unique()->values() as $cycleId) {
                DB::table('academic_calendar_cycles')->updateOrInsert(
                    [
                        'academic_calendar_id' => $calendar->id,
                        'academic_cycle_id' => $cycleId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }
};

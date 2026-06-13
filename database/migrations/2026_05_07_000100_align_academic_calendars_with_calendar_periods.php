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
            if (! Schema::hasColumn('academic_calendars', 'period_type')) {
                $table->string('period_type', 20)->nullable()->after('description');
            }

            if (! Schema::hasColumn('academic_calendars', 'period_count')) {
                $table->unsignedTinyInteger('period_count')->nullable()->after('period_type');
            }
        });

        Schema::table('academic_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('academic_periods', 'academic_calendar_id')) {
                $table->foreignId('academic_calendar_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('academic_calendars')
                    ->cascadeOnDelete();
            }
        });

        $this->backfillGlobalCalendars();
        $this->backfillCalendarPeriods();

        if (! Schema::hasIndex('academic_periods', 'academic_periods_configuration_position_index')) {
            Schema::table('academic_periods', function (Blueprint $table): void {
                $table->index(['academic_configuration_id', 'position'], 'academic_periods_configuration_position_index');
            });
        }

        if (! Schema::hasIndex('academic_periods', 'academic_periods_calendar_position_unique')) {
            Schema::table('academic_periods', function (Blueprint $table): void {
                $table->unique(['academic_calendar_id', 'position'], 'academic_periods_calendar_position_unique');
            });
        }

        if (Schema::hasIndex('academic_periods', 'academic_periods_academic_configuration_id_position_unique')) {
            Schema::table('academic_periods', function (Blueprint $table): void {
                $table->dropUnique('academic_periods_academic_configuration_id_position_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table): void {
            if (! Schema::hasIndex('academic_periods', 'academic_periods_academic_configuration_id_position_unique')) {
                $table->unique(['academic_configuration_id', 'position'], 'academic_periods_academic_configuration_id_position_unique');
            }

            if (Schema::hasIndex('academic_periods', 'academic_periods_calendar_position_unique')) {
                $table->dropUnique('academic_periods_calendar_position_unique');
            }

            if (Schema::hasIndex('academic_periods', 'academic_periods_configuration_position_index')) {
                $table->dropIndex('academic_periods_configuration_position_index');
            }
        });

        if (Schema::hasColumn('academic_periods', 'academic_calendar_id')) {
            Schema::table('academic_periods', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('academic_calendar_id');
            });
        }

        Schema::table('academic_calendars', function (Blueprint $table): void {
            $columns = array_filter([
                'period_type' => Schema::hasColumn('academic_calendars', 'period_type'),
                'period_count' => Schema::hasColumn('academic_calendars', 'period_count'),
            ]);

            if (! empty($columns)) {
                $table->dropColumn(array_keys($columns));
            }
        });
    }

    private function backfillGlobalCalendars(): void
    {
        $configurations = DB::table('academic_configurations')
            ->select(['id', 'school_id', 'academic_year_id', 'period_type', 'period_count', 'is_active', 'created_by'])
            ->orderBy('id')
            ->get();

        foreach ($configurations as $configuration) {
            $academicYear = DB::table('academic_years')
                ->select(['id', 'name'])
                ->where('id', $configuration->academic_year_id)
                ->first();

            if (! $academicYear) {
                continue;
            }

            $calendar = DB::table('academic_calendars')
                ->where('school_id', $configuration->school_id)
                ->where('academic_year_id', $configuration->academic_year_id)
                ->where('scope_type', 'global')
                ->first();

            $payload = [
                'school_id' => $configuration->school_id,
                'academic_year_id' => $configuration->academic_year_id,
                'name' => $academicYear->name,
                'description' => $calendar->description ?? null,
                'scope_type' => 'global',
                'cycle_id' => null,
                'level_id' => null,
                'class_id' => null,
                'period_type' => $configuration->period_type,
                'period_count' => $configuration->period_count,
                'is_active' => $calendar->is_active ?? (bool) $configuration->is_active,
                'validated_at' => $calendar->validated_at ?? null,
                'validated_by' => $calendar->validated_by ?? null,
                'created_by' => $calendar->created_by ?? $configuration->created_by,
                'updated_at' => now(),
            ];

            if ($calendar) {
                DB::table('academic_calendars')->where('id', $calendar->id)->update($payload + ['updated_at' => now()]);
            } else {
                $payload['created_at'] = now();
                $payload['updated_at'] = now();

                DB::table('academic_calendars')->insert($payload);
                $calendar = DB::table('academic_calendars')
                    ->where('school_id', $configuration->school_id)
                    ->where('academic_year_id', $configuration->academic_year_id)
                    ->where('scope_type', 'global')
                    ->first();
            }

            if (! $calendar) {
                continue;
            }
        }
    }

    private function backfillCalendarPeriods(): void
    {
        $periods = DB::table('academic_periods')
            ->select(['id', 'school_id', 'academic_configuration_id', 'academic_calendar_id'])
            ->orderBy('id')
            ->get();

        foreach ($periods as $period) {
            if ($period->academic_calendar_id) {
                continue;
            }

            $configuration = DB::table('academic_configurations')
                ->select(['id', 'school_id', 'academic_year_id'])
                ->where('id', $period->academic_configuration_id)
                ->first();

            if (! $configuration) {
                continue;
            }

            $calendar = DB::table('academic_calendars')
                ->where('school_id', $configuration->school_id)
                ->where('academic_year_id', $configuration->academic_year_id)
                ->where('scope_type', 'global')
                ->value('id');

            if (! $calendar) {
                continue;
            }

            DB::table('academic_periods')
                ->where('id', $period->id)
                ->update([
                    'academic_calendar_id' => $calendar,
                    'updated_at' => now(),
                ]);
        }
    }

};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_configurations', function (Blueprint $table): void {
            if (! Schema::hasColumn('academic_configurations', 'scope_type')) {
                $table->string('scope_type', 20)
                    ->default('global')
                    ->after('academic_year_id');
            }

            if (! Schema::hasColumn('academic_configurations', 'cycle_id')) {
                $table->foreignId('cycle_id')
                    ->nullable()
                    ->after('scope_type')
                    ->constrained('academic_cycles')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('academic_configurations', 'scope_signature')) {
                $table->string('scope_signature', 60)
                    ->default('global')
                    ->after('cycle_id');
            }
        });

        DB::table('academic_configurations')
            ->orderBy('id')
            ->get()
            ->each(function (object $configuration): void {
                $scopeSignature = $configuration->scope_type === 'cycle'
                    ? 'cycle:'.($configuration->cycle_id ?? '0')
                    : 'global';

                DB::table('academic_configurations')
                    ->where('id', $configuration->id)
                    ->update([
                        'scope_type' => $configuration->scope_type ?? 'global',
                        'scope_signature' => $scopeSignature,
                    ]);
            });

        Schema::table('academic_configurations', function (Blueprint $table): void {
            $table->unique(
                ['school_id', 'academic_year_id', 'scope_signature'],
                'academic_configurations_scope_unique'
            );

            $table->index(
                ['school_id', 'academic_year_id', 'scope_type'],
                'academic_configurations_scope_lookup'
            );

            $table->index(
                ['school_id', 'academic_year_id', 'cycle_id'],
                'academic_configurations_cycle_lookup'
            );

            if (Schema::hasIndex('academic_configurations', 'academic_configurations_school_id_academic_year_id_unique')) {
                $table->dropUnique('academic_configurations_school_id_academic_year_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_configurations', function (Blueprint $table): void {
            if (! Schema::hasIndex('academic_configurations', 'academic_configurations_school_id_academic_year_id_unique')) {
                $table->unique(['school_id', 'academic_year_id'], 'academic_configurations_school_id_academic_year_id_unique');
            }

            if (Schema::hasIndex('academic_configurations', 'academic_configurations_scope_lookup')) {
                $table->dropIndex('academic_configurations_scope_lookup');
            }

            if (Schema::hasIndex('academic_configurations', 'academic_configurations_cycle_lookup')) {
                $table->dropIndex('academic_configurations_cycle_lookup');
            }

            if (Schema::hasIndex('academic_configurations', 'academic_configurations_scope_unique')) {
                $table->dropUnique('academic_configurations_scope_unique');
            }
        });

        if (Schema::hasColumn('academic_configurations', 'scope_signature')) {
            Schema::table('academic_configurations', function (Blueprint $table): void {
                $table->dropColumn('scope_signature');
            });
        }

        if (Schema::hasColumn('academic_configurations', 'cycle_id')) {
            Schema::table('academic_configurations', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('cycle_id');
            });
        }

        if (Schema::hasColumn('academic_configurations', 'scope_type')) {
            Schema::table('academic_configurations', function (Blueprint $table): void {
                $table->dropColumn('scope_type');
            });
        }
    }
};

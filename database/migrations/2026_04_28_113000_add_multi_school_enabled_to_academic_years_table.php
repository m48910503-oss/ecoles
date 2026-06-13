<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table): void {
            $table->boolean('multi_school_enabled')
                ->default(false)
                ->after('enrollment_open');
        });

        $legacyMultiSchoolEnabled = (bool) DB::table('system_settings')
            ->where('group', 'organization')
            ->where('key', 'multi_school_enabled')
            ->value('value');

        if ($legacyMultiSchoolEnabled) {
            DB::table('academic_years')
                ->where('is_active', true)
                ->update(['multi_school_enabled' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table): void {
            $table->dropColumn('multi_school_enabled');
        });
    }
};

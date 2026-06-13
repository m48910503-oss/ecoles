<?php

use App\Enums\Academic\AcademicCycleCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_cycles', function (Blueprint $table): void {
            $table->string('category', 40)->default(AcademicCycleCategory::Primary->value)->after('code');
        });

        DB::table('academic_cycles')
            ->where('code', 'MATERNELLE')
            ->update(['category' => AcademicCycleCategory::Preschool->value]);

        DB::table('academic_cycles')
            ->where('code', 'PRIMAIRE')
            ->update(['category' => AcademicCycleCategory::Primary->value]);

        DB::table('academic_cycles')
            ->where('code', 'SECONDAIRE-1')
            ->update(['category' => AcademicCycleCategory::LowerSecondary->value]);

        DB::table('academic_cycles')
            ->where('code', 'SECONDAIRE-2')
            ->update(['category' => AcademicCycleCategory::UpperSecondary->value]);
    }

    public function down(): void
    {
        Schema::table('academic_cycles', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }
};

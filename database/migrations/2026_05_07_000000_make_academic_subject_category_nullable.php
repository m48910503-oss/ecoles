<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildTable(categoryNullable: true);

            return;
        }

        DB::statement('ALTER TABLE academic_subjects MODIFY category VARCHAR(100) NULL');
    }

    public function down(): void
    {
        DB::table('academic_subjects')
            ->whereNull('category')
            ->update(['category' => 'other']);

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildTable(categoryNullable: false);

            return;
        }

        DB::statement('ALTER TABLE academic_subjects MODIFY category VARCHAR(100) NOT NULL');
    }

    private function rebuildTable(bool $categoryNullable): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('academic_subjects_temp');

        Schema::create('academic_subjects_temp', function (Blueprint $table) use ($categoryNullable): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 100);
            $table->string('short_name', 100)->nullable();
            $table->string('category', 100)->nullable($categoryNullable);
            $table->text('description')->nullable();
            $table->boolean('is_optional_by_default')->default(false);
            $table->boolean('is_conduct')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $columns = [
            'id',
            'school_id',
            'name',
            'code',
            'short_name',
            'category',
            'description',
            'is_optional_by_default',
            'is_conduct',
            'is_active',
            'sort_order',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ];

        DB::statement(sprintf(
            'INSERT INTO academic_subjects_temp (%s) SELECT %s FROM academic_subjects',
            implode(', ', $columns),
            implode(', ', $columns)
        ));

        Schema::drop('academic_subjects');

        DB::statement('ALTER TABLE academic_subjects_temp RENAME TO academic_subjects');

        Schema::table('academic_subjects', function (Blueprint $table): void {
            $table->unique(['school_id', 'code'], 'academic_subjects_school_code_unique');
            $table->index(['school_id', 'category', 'is_active'], 'academic_subjects_school_category_active_index');
        });

        Schema::enableForeignKeyConstraints();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->updateOrInsert(
                ['group' => 'organization', 'key' => 'multi_school_enabled'],
                ['value' => '0', 'type' => 'boolean', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $schoolName = DB::table('system_settings')
            ->where('group', 'general')
            ->where('key', 'school_name')
            ->value('value') ?: config('app.name', 'ERP Scolaire');

        $supportEmail = DB::table('system_settings')
            ->where('group', 'general')
            ->where('key', 'support_email')
            ->value('value');

        $schoolPhone = DB::table('system_settings')
            ->where('group', 'general')
            ->where('key', 'school_phone')
            ->value('value');

        if (! DB::table('schools')->where('is_default', true)->exists()) {
            DB::table('schools')->insert([
                'code' => Str::upper(Str::substr(Str::slug((string) $schoolName, '-'), 0, 50)) ?: 'ECOLE-PRINCIPALE',
                'name' => $schoolName,
                'email' => $supportEmail ?: null,
                'phone' => $schoolPhone ?: null,
                'address' => null,
                'is_active' => true,
                'is_default' => true,
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('group', 'organization')
                ->where('key', 'multi_school_enabled')
                ->delete();
        }

        Schema::dropIfExists('schools');
    }
};

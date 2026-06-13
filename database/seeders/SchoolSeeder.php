<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schoolName = SystemSetting::query()
            ->where('group', 'general')
            ->where('key', 'school_name')
            ->value('value') ?: config('app.name', 'ERP Scolaire');

        $supportEmail = SystemSetting::query()
            ->where('group', 'general')
            ->where('key', 'support_email')
            ->value('value');

        $schoolPhone = SystemSetting::query()
            ->where('group', 'general')
            ->where('key', 'school_phone')
            ->value('value');

        School::query()->updateOrCreate(
            ['is_default' => true],
            [
                'code' => Str::upper(Str::substr(Str::slug((string) $schoolName, '-'), 0, 50)) ?: 'ECOLE-PRINCIPALE',
                'name' => $schoolName,
                'email' => filled($supportEmail) ? $supportEmail : null,
                'phone' => filled($schoolPhone) ? $schoolPhone : null,
                'address' => null,
                'is_active' => true,
                'settings' => null,
            ]
        );

        SystemSetting::query()->updateOrCreate(
            ['group' => 'organization', 'key' => 'multi_school_enabled'],
            ['value' => '0', 'type' => 'boolean']
        );
    }
}

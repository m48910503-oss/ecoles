<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'school_name', 'value' => 'ERP Scolaire', 'type' => 'string'],
            ['group' => 'general', 'key' => 'school_phone', 'value' => '', 'type' => 'string'],
            ['group' => 'general', 'key' => 'support_email', 'value' => 'support@ecoles.test', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'multi_school_enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'integer'],
            ['group' => 'security', 'key' => 'password_min_length', 'value' => '12', 'type' => 'integer'],
            ['group' => 'security', 'key' => 'allow_public_registration', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'finance', 'key' => 'minimum_initial_payment', 'value' => '0', 'type' => 'integer'],
            ['group' => 'finance', 'key' => 'financial_blocking_enabled', 'value' => '0', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}

<?php

namespace App\Services\Core;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolService $schoolService,
    ) {
    }

    /**
     * @return array<string, array{title: string, description: string, fields: array<int, array<string, string|int|bool>>}>
     */
    public function definitions(): array
    {
        return [
            'general' => [
                'title' => __('core.settings.edit.groups.general.title'),
                'description' => __('core.settings.edit.groups.general.description'),
                'fields' => [
                    ['key' => 'school_name', 'label' => __('core.settings.edit.fields.school_name'), 'type' => 'string'],
                    ['key' => 'school_phone', 'label' => __('core.settings.edit.fields.school_phone'), 'type' => 'string'],
                    ['key' => 'support_email', 'label' => __('core.settings.edit.fields.support_email'), 'type' => 'string'],
                ],
            ],
            'security' => [
                'title' => __('core.settings.edit.groups.security.title'),
                'description' => __('core.settings.edit.groups.security.description'),
                'fields' => [
                    ['key' => 'session_timeout_minutes', 'label' => __('core.settings.edit.fields.session_timeout_minutes'), 'type' => 'integer'],
                    ['key' => 'password_min_length', 'label' => __('core.settings.edit.fields.password_min_length'), 'type' => 'integer'],
                    ['key' => 'allow_public_registration', 'label' => __('core.settings.edit.fields.allow_public_registration'), 'type' => 'boolean'],
                ],
            ],
            'finance' => [
                'title' => __('core.settings.edit.groups.finance.title'),
                'description' => __('core.settings.edit.groups.finance.description'),
                'fields' => [
                    ['key' => 'minimum_initial_payment', 'label' => __('core.settings.edit.fields.minimum_initial_payment'), 'type' => 'integer'],
                    ['key' => 'financial_blocking_enabled', 'label' => __('core.settings.edit.fields.financial_blocking_enabled'), 'type' => 'boolean'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{title: string, description: string, fields: array<int, array<string, mixed>>}>
     */
    public function groupsWithValues(): array
    {
        $settings = SystemSetting::query()->get()->keyBy(fn (SystemSetting $setting) => "{$setting->group}.{$setting->key}");

        return collect($this->definitions())
            ->map(function (array $group, string $groupKey) use ($settings): array {
                $fields = collect($group['fields'])
                    ->map(function (array $field) use ($groupKey, $settings): array {
                        $setting = $settings->get("{$groupKey}.{$field['key']}");

                        return [
                            ...$field,
                            'value' => $setting?->typedValue(),
                        ];
                    })
                    ->all();

                return [
                    'title' => $group['title'],
                    'description' => $group['description'],
                    'fields' => $fields,
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(array $validated, User $actor): void
    {
        DB::transaction(function () use ($validated, $actor): void {
            $oldValues = SystemSetting::query()->get()->mapWithKeys(
                fn (SystemSetting $setting) => ["{$setting->group}.{$setting->key}" => $setting->typedValue()]
            )->all();

            foreach ($this->definitions() as $groupKey => $group) {
                foreach ($group['fields'] as $field) {
                    $inputKey = "{$groupKey}.{$field['key']}";
                    $value = data_get($validated, $inputKey);

                    SystemSetting::query()->updateOrCreate(
                        ['group' => $groupKey, 'key' => $field['key']],
                        ['value' => $this->normalizeValue($field['type'], $value), 'type' => $field['type']]
                    );
                }
            }

            $newValues = SystemSetting::query()->get()->mapWithKeys(
                fn (SystemSetting $setting) => ["{$setting->group}.{$setting->key}" => $setting->typedValue()]
            )->all();

            $this->auditLogService->log(
                module: 'core',
                action: 'settings.updated',
                oldValues: $oldValues,
                newValues: $newValues,
                description: 'Mise à jour des paramètres généraux.',
                user: $actor,
            );

            $this->schoolService->syncDefaultSchoolFromSettings();
        });
    }

    private function normalizeValue(string $type, mixed $value): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) ((int) $value),
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}

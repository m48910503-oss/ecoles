<?php

namespace App\Services\Core;

use App\Models\AcademicYear;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class ShellService
{
    public function __construct(
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function buildFor(User $user): array
    {
        $currentSchool = $this->schoolContext->current();
        $activeAcademicYear = AcademicYear::query()->active()->first(['id', 'name']);

        return [
            'school_name' => $currentSchool->name,
            'school_code' => $currentSchool->code,
            'support_email' => $currentSchool->email ?: $this->settingValue('general', 'support_email', 'support@ecoles.test'),
            'active_academic_year_id' => $activeAcademicYear?->id,
            'active_academic_year' => $activeAcademicYear?->name ?? __('ui.dashboard.not_configured'),
            'user_name' => $user->name,
            'user_role' => $this->localizedRole($user),
            'user_email' => $user->email,
            'user_initials' => Str::of($user->name)
                ->trim()
                ->explode(' ')
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
                ->join(''),
        ];
    }

    private function settingValue(string $group, string $key, string $fallback): string
    {
        return (string) (SystemSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?: $fallback);
    }

    private function localizedRole(User $user): string
    {
        $role = $user->roles->sortBy('name')->first();

        if (! $role) {
            return __('ui.roles.user');
        }

        $translationKey = "ui.roles.{$role->slug}";

        return Lang::has($translationKey) ? __($translationKey) : $role->name;
    }
}

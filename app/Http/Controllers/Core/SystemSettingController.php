<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\UpdateSystemSettingsRequest;
use App\Models\AcademicYear;
use App\Models\SystemSetting;
use App\Services\Core\SchoolService;
use App\Services\Core\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly SchoolService $schoolService,
    ) {
    }

    public function edit(): View
    {
        $this->authorize('viewAny', SystemSetting::class);

        return view('core.settings.edit', [
            'settingGroups' => $this->settingService->groupsWithValues(),
            'multiSchool' => $this->schoolService->multiSchoolStatus(),
            'activeAcademicYear' => AcademicYear::query()->active()->first(),
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        $this->settingService->update($request->validated(), $request->user());

        return redirect()
            ->route('core.settings.edit')
            ->with('success', __('core.settings.messages.updated'));
    }
}

<?php

namespace App\Services\Core;

use App\Models\School;

class SchoolContext
{
    private ?School $resolvedCurrentSchool = null;

    private ?bool $resolvedMultiSchoolEnabled = null;

    public function __construct(
        private readonly SchoolService $schoolService,
    ) {
    }

    public function current(): School
    {
        if ($this->resolvedCurrentSchool) {
            return $this->resolvedCurrentSchool;
        }

        $defaultSchool = $this->default();

        if (! $this->isMultiSchoolEnabled()) {
            return $this->resolvedCurrentSchool = $defaultSchool;
        }

        $schoolId = request()?->session()->get('current_school_id');

        if ($schoolId) {
            $school = School::query()->active()->find($schoolId);

            if ($school) {
                return $this->resolvedCurrentSchool = $school;
            }
        }

        return $this->resolvedCurrentSchool = $defaultSchool;
    }

    public function default(): School
    {
        return School::query()->default()->first() ?? $this->schoolService->ensureDefaultSchool();
    }

    public function isMultiSchoolEnabled(): bool
    {
        if ($this->resolvedMultiSchoolEnabled !== null) {
            return $this->resolvedMultiSchoolEnabled;
        }

        return $this->resolvedMultiSchoolEnabled = $this->schoolService->isMultiSchoolEnabled();
    }
}

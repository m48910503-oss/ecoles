<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\AcademicConfiguration;
use App\Models\AcademicPeriod;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicClassroom;
use App\Models\AcademicSubject;
use App\Models\AcademicCoefficient;
use App\Models\AcademicConductSetting;
use App\Models\AcademicCalendar;
use App\Models\CalendarEvent;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\SchoolDayConfiguration;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\AcademicYearPolicy;
use App\Policies\AcademicConfigurationPolicy;
use App\Policies\AcademicPeriodPolicy;
use App\Policies\AcademicCyclePolicy;
use App\Policies\AcademicLevelPolicy;
use App\Policies\AcademicSeriesPolicy;
use App\Policies\AcademicClassroomPolicy;
use App\Policies\AcademicSubjectPolicy;
use App\Policies\AcademicCoefficientPolicy;
use App\Policies\AcademicConductSettingPolicy;
use App\Policies\AcademicCalendarPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\CalendarEventPolicy;
use App\Policies\ParentPolicy;
use App\Policies\StudentPolicy;
use App\Policies\StaffPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SchoolPolicy;
use App\Policies\SchoolDayConfigurationPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(AcademicConfiguration::class, AcademicConfigurationPolicy::class);
        Gate::policy(AcademicPeriod::class, AcademicPeriodPolicy::class);
        Gate::policy(AcademicCycle::class, AcademicCyclePolicy::class);
        Gate::policy(AcademicLevel::class, AcademicLevelPolicy::class);
        Gate::policy(AcademicSeries::class, AcademicSeriesPolicy::class);
        Gate::policy(AcademicClassroom::class, AcademicClassroomPolicy::class);
        Gate::policy(AcademicSubject::class, AcademicSubjectPolicy::class);
        Gate::policy(AcademicCoefficient::class, AcademicCoefficientPolicy::class);
        Gate::policy(AcademicConductSetting::class, AcademicConductSettingPolicy::class);
        Gate::policy(SchoolDayConfiguration::class, SchoolDayConfigurationPolicy::class);
        Gate::policy(AcademicCalendar::class, AcademicCalendarPolicy::class);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
        Gate::policy(Guardian::class, ParentPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->isAdministrator() ? true : null;
        });
    }
}

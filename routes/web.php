<?php

use App\Http\Controllers\Core\AcademicYearController;
use App\Http\Controllers\Core\AcademicYearCycleController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\PermissionController;
use App\Http\Controllers\Core\RoleController;
use App\Http\Controllers\Core\SchoolController;
use App\Http\Controllers\Core\SystemSettingController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\People\PeopleDashboardController;
use App\Http\Controllers\People\PersonAccountController;
use App\Http\Controllers\People\PersonDocumentController;
use App\Http\Controllers\People\ParentController;
use App\Http\Controllers\People\ParentStudentController;
use App\Http\Controllers\People\StaffController;
use App\Http\Controllers\People\TeacherInvitationController;
use App\Http\Controllers\People\TeacherAssignmentController;
use App\Http\Controllers\People\TeacherController;
use App\Http\Controllers\People\StudentController;
use App\Http\Controllers\People\StudentParentController;
use App\Http\Controllers\Academic\AcademicConfigurationController;
use App\Http\Controllers\Academic\AcademicCycleController;
use App\Http\Controllers\Academic\AcademicClassroomController;
use App\Http\Controllers\Academic\AcademicCoefficientController;
use App\Http\Controllers\Academic\AcademicConductSettingController;
use App\Http\Controllers\Academic\AcademicDashboardController;
use App\Http\Controllers\Academic\AcademicLevelController;
use App\Http\Controllers\Academic\AcademicPeriodController;
use App\Http\Controllers\Academic\AcademicSeriesController;
use App\Http\Controllers\Academic\AcademicSubjectController;
use App\Http\Controllers\Academic\AcademicCalendarController;
use App\Http\Controllers\Academic\CalendarEventController;
use App\Http\Controllers\Academic\SchoolDayConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active', 'permission:core.dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('core')
        ->name('core.')
        ->group(function (): void {
            Route::resource('users', UserController::class)
                ->only(['index', 'create', 'store', 'edit', 'update']);

            Route::resource('roles', RoleController::class)
                ->only(['index', 'create', 'store', 'edit', 'update']);

            Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

            Route::resource('schools', SchoolController::class)
                ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::post('schools/{school}/switch', [SchoolController::class, 'switchCurrent'])->name('schools.switch');

            Route::resource('academic-years', AcademicYearController::class)
                ->parameters(['academic-years' => 'academic_year'])
                ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
            Route::get('academic-years/{academic_year}/cycles/{cycle}', [AcademicYearCycleController::class, 'show'])
                ->name('academic-years.cycles.show');
            Route::patch('academic-years/{academic_year}/cycles/{cycle}/levels/{level}/toggle-active', [AcademicYearCycleController::class, 'toggleLevel'])
                ->name('academic-years.cycles.levels.toggle-active');
            Route::patch('academic-years/{academic_year}/cycles/{cycle}/series/{series}/toggle-active', [AcademicYearCycleController::class, 'toggleSeries'])
                ->name('academic-years.cycles.series.toggle-active');
            Route::post('academic-years/create/step-1', [AcademicYearController::class, 'storeStepOne'])
                ->name('academic-years.create.step1.store');
            Route::get('academic-years/create/step-2', [AcademicYearController::class, 'createStepTwo'])
                ->name('academic-years.create.step2');
            Route::post('academic-years/create/step-2', [AcademicYearController::class, 'storeStepTwo'])
                ->name('academic-years.create.step2.store');
            Route::get('academic-years/create/step-3', [AcademicYearController::class, 'createStepThree'])
                ->name('academic-years.create.step3');
            Route::post('academic-years/create/step-3', [AcademicYearController::class, 'storeStepThree'])
                ->name('academic-years.create.step3.store');
            Route::get('academic-years/create/step-4', [AcademicYearController::class, 'createStepFour'])
                ->name('academic-years.create.step4');
            Route::get('academic-years/{academic_year}/sectors/{group}', [AcademicYearController::class, 'showSector'])
                ->whereIn('group', ['preschool', 'primary', 'secondary'])
                ->name('academic-years.sectors.show');

            Route::get('settings', [SystemSettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SystemSettingController::class, 'update'])->name('settings.update');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

    Route::prefix('academic')
        ->name('academic.')
        ->group(function (): void {
            Route::get('dashboard', AcademicDashboardController::class)
                ->middleware('permission:academic.cycles.view|academic.cycles.manage|academic.levels.view|academic.levels.manage|academic.series.view|academic.series.manage|academic.classes.view|academic.classes.manage|academic.subjects.view|academic.subjects.manage|academic.coefficients.view|academic.coefficients.manage|academic.conduct.view|academic.conduct.manage|academic.config.view|academic.config.manage|academic.periods.view|academic.periods.manage|academic.day_config.view|academic.day_config.manage|academic.calendars.view|academic.calendars.manage|academic.events.view|academic.events.view_internal|academic.events.view_public|academic.events.manage_all')
                ->name('dashboard');

            Route::get('cycles', [AcademicCycleController::class, 'index'])
                ->middleware('permission:academic.cycles.view|academic.cycles.manage')
                ->name('cycles.index');
            Route::get('cycles/create', [AcademicCycleController::class, 'create'])
                ->middleware('permission:academic.cycles.create|academic.cycles.manage')
                ->name('cycles.create');
            Route::post('cycles', [AcademicCycleController::class, 'store'])
                ->middleware('permission:academic.cycles.create|academic.cycles.manage')
                ->name('cycles.store');
            Route::get('cycles/{cycle}', [AcademicCycleController::class, 'show'])
                ->middleware('permission:academic.cycles.view|academic.cycles.manage')
                ->name('cycles.show');
            Route::get('cycles/{cycle}/edit', [AcademicCycleController::class, 'edit'])
                ->middleware('permission:academic.cycles.update|academic.cycles.manage')
                ->name('cycles.edit');
            Route::put('cycles/{cycle}', [AcademicCycleController::class, 'update'])
                ->middleware('permission:academic.cycles.update|academic.cycles.manage')
                ->name('cycles.update');
            Route::delete('cycles/{cycle}', [AcademicCycleController::class, 'destroy'])
                ->middleware('permission:academic.cycles.manage')
                ->name('cycles.destroy');
            Route::patch('cycles/{cycle}/toggle-active', [AcademicCycleController::class, 'toggleActive'])
                ->middleware('permission:academic.cycles.disable|academic.cycles.manage')
                ->name('cycles.toggle-active');

            Route::get('levels', [AcademicLevelController::class, 'index'])
                ->middleware('permission:academic.levels.view|academic.levels.manage')
                ->name('levels.index');
            Route::get('levels/create', [AcademicLevelController::class, 'create'])
                ->middleware('permission:academic.levels.create|academic.levels.manage')
                ->name('levels.create');
            Route::post('levels', [AcademicLevelController::class, 'store'])
                ->middleware('permission:academic.levels.create|academic.levels.manage')
                ->name('levels.store');
            Route::get('levels/{level}', [AcademicLevelController::class, 'show'])
                ->middleware('permission:academic.levels.view|academic.levels.manage')
                ->name('levels.show');
            Route::get('levels/{level}/edit', [AcademicLevelController::class, 'edit'])
                ->middleware('permission:academic.levels.update|academic.levels.manage')
                ->name('levels.edit');
            Route::put('levels/{level}', [AcademicLevelController::class, 'update'])
                ->middleware('permission:academic.levels.update|academic.levels.manage')
                ->name('levels.update');
            Route::delete('levels/{level}', [AcademicLevelController::class, 'destroy'])
                ->middleware('permission:academic.levels.manage')
                ->name('levels.destroy');
            Route::patch('levels/{level}/toggle-active', [AcademicLevelController::class, 'toggleActive'])
                ->middleware('permission:academic.levels.disable|academic.levels.manage')
                ->name('levels.toggle-active');

            Route::get('series', [AcademicSeriesController::class, 'index'])
                ->middleware('permission:academic.series.view|academic.series.manage')
                ->name('series.index');
            Route::get('series/create', [AcademicSeriesController::class, 'create'])
                ->middleware('permission:academic.series.create|academic.series.manage')
                ->name('series.create');
            Route::post('series', [AcademicSeriesController::class, 'store'])
                ->middleware('permission:academic.series.create|academic.series.manage')
                ->name('series.store');
            Route::get('series/{series}', [AcademicSeriesController::class, 'show'])
                ->middleware('permission:academic.series.view|academic.series.manage')
                ->name('series.show');
            Route::get('series/{series}/edit', [AcademicSeriesController::class, 'edit'])
                ->middleware('permission:academic.series.update|academic.series.manage')
                ->name('series.edit');
            Route::put('series/{series}', [AcademicSeriesController::class, 'update'])
                ->middleware('permission:academic.series.update|academic.series.manage')
                ->name('series.update');
            Route::delete('series/{series}', [AcademicSeriesController::class, 'destroy'])
                ->middleware('permission:academic.series.manage')
                ->name('series.destroy');
            Route::patch('series/{series}/toggle-active', [AcademicSeriesController::class, 'toggleActive'])
                ->middleware('permission:academic.series.disable|academic.series.manage')
                ->name('series.toggle-active');

            Route::get('classes', [AcademicClassroomController::class, 'index'])
                ->middleware('permission:academic.classes.view|academic.classes.manage')
                ->name('classes.index');
            Route::get('classes/create', [AcademicClassroomController::class, 'create'])
                ->middleware('permission:academic.classes.create|academic.classes.manage')
                ->name('classes.create');
            Route::post('classes', [AcademicClassroomController::class, 'store'])
                ->middleware('permission:academic.classes.create|academic.classes.manage')
                ->name('classes.store');
            Route::get('classes/{classroom}', [AcademicClassroomController::class, 'show'])
                ->middleware('permission:academic.classes.view|academic.classes.manage')
                ->name('classes.show');
            Route::get('classes/{classroom}/edit', [AcademicClassroomController::class, 'edit'])
                ->middleware('permission:academic.classes.update|academic.classes.manage')
                ->name('classes.edit');
            Route::put('classes/{classroom}', [AcademicClassroomController::class, 'update'])
                ->middleware('permission:academic.classes.update|academic.classes.manage')
                ->name('classes.update');
            Route::delete('classes/{classroom}', [AcademicClassroomController::class, 'destroy'])
                ->middleware('permission:academic.classes.manage')
                ->name('classes.destroy');
            Route::patch('classes/{classroom}/toggle-active', [AcademicClassroomController::class, 'toggleActive'])
                ->middleware('permission:academic.classes.disable|academic.classes.manage')
                ->name('classes.toggle-active');

            Route::get('subjects', [AcademicSubjectController::class, 'index'])
                ->middleware('permission:academic.subjects.view|academic.subjects.manage')
                ->name('subjects.index');
            Route::get('subjects/create', [AcademicSubjectController::class, 'create'])
                ->middleware('permission:academic.subjects.create|academic.subjects.manage')
                ->name('subjects.create');
            Route::post('subjects', [AcademicSubjectController::class, 'store'])
                ->middleware('permission:academic.subjects.create|academic.subjects.manage')
                ->name('subjects.store');
            Route::get('subjects/{subject}', [AcademicSubjectController::class, 'show'])
                ->middleware('permission:academic.subjects.view|academic.subjects.manage')
                ->name('subjects.show');
            Route::get('subjects/{subject}/edit', [AcademicSubjectController::class, 'edit'])
                ->middleware('permission:academic.subjects.update|academic.subjects.manage')
                ->name('subjects.edit');
            Route::put('subjects/{subject}', [AcademicSubjectController::class, 'update'])
                ->middleware('permission:academic.subjects.update|academic.subjects.manage')
                ->name('subjects.update');
            Route::patch('subjects/{subject}/toggle-active', [AcademicSubjectController::class, 'toggleActive'])
                ->middleware('permission:academic.subjects.disable|academic.subjects.manage')
                ->name('subjects.toggle-active');

            Route::get('coefficients', [AcademicCoefficientController::class, 'index'])
                ->middleware('permission:academic.coefficients.view|academic.coefficients.manage')
                ->name('coefficients.index');
            Route::get('coefficients/create', [AcademicCoefficientController::class, 'create'])
                ->middleware('permission:academic.coefficients.create|academic.coefficients.manage')
                ->name('coefficients.create');
            Route::post('coefficients', [AcademicCoefficientController::class, 'store'])
                ->middleware('permission:academic.coefficients.create|academic.coefficients.manage')
                ->name('coefficients.store');
            Route::get('coefficients/{coefficient}', [AcademicCoefficientController::class, 'show'])
                ->middleware('permission:academic.coefficients.view|academic.coefficients.manage')
                ->name('coefficients.show');
            Route::get('coefficients/{coefficient}/edit', [AcademicCoefficientController::class, 'edit'])
                ->middleware('permission:academic.coefficients.update|academic.coefficients.manage')
                ->name('coefficients.edit');
            Route::put('coefficients/{coefficient}', [AcademicCoefficientController::class, 'update'])
                ->middleware('permission:academic.coefficients.update|academic.coefficients.manage')
                ->name('coefficients.update');
            Route::patch('coefficients/{coefficient}/toggle-active', [AcademicCoefficientController::class, 'toggleActive'])
                ->middleware('permission:academic.coefficients.disable|academic.coefficients.manage')
                ->name('coefficients.toggle-active');

            Route::get('conduct', [AcademicConductSettingController::class, 'index'])
                ->middleware('permission:academic.conduct.view|academic.conduct.manage')
                ->name('conduct.index');
            Route::get('conduct/create', [AcademicConductSettingController::class, 'create'])
                ->middleware('permission:academic.conduct.create|academic.conduct.manage')
                ->name('conduct.create');
            Route::post('conduct', [AcademicConductSettingController::class, 'store'])
                ->middleware('permission:academic.conduct.create|academic.conduct.manage')
                ->name('conduct.store');
            Route::get('conduct/{conduct_setting}', [AcademicConductSettingController::class, 'show'])
                ->middleware('permission:academic.conduct.view|academic.conduct.manage')
                ->name('conduct.show');
            Route::get('conduct/{conduct_setting}/edit', [AcademicConductSettingController::class, 'edit'])
                ->middleware('permission:academic.conduct.update|academic.conduct.manage')
                ->name('conduct.edit');
            Route::put('conduct/{conduct_setting}', [AcademicConductSettingController::class, 'update'])
                ->middleware('permission:academic.conduct.update|academic.conduct.manage')
                ->name('conduct.update');
            Route::patch('conduct/{conduct_setting}/toggle-active', [AcademicConductSettingController::class, 'toggleActive'])
                ->middleware('permission:academic.conduct.disable|academic.conduct.manage')
                ->name('conduct.toggle-active');

            Route::get('configuration', [AcademicConfigurationController::class, 'index'])
                ->middleware('permission:academic.config.view|academic.config.manage')
                ->name('configuration.index');
            Route::get('configuration/create', [AcademicConfigurationController::class, 'create'])
                ->middleware('permission:academic.config.update|academic.config.manage')
                ->name('configuration.create');
            Route::post('configuration', [AcademicConfigurationController::class, 'store'])
                ->middleware('permission:academic.config.update|academic.config.manage')
                ->name('configuration.store');
            Route::get('configuration/{configuration}/edit', [AcademicConfigurationController::class, 'edit'])
                ->middleware('permission:academic.config.update|academic.config.manage')
                ->name('configuration.edit');
            Route::put('configuration/{configuration}', [AcademicConfigurationController::class, 'update'])
                ->middleware('permission:academic.config.update|academic.config.manage')
                ->name('configuration.update');

            Route::get('periods', [AcademicPeriodController::class, 'index'])
                ->middleware('permission:academic.periods.view|academic.periods.manage')
                ->name('periods.index');
            Route::get('periods/create', [AcademicPeriodController::class, 'create'])
                ->middleware('permission:academic.periods.create|academic.periods.manage')
                ->name('periods.create');
            Route::post('periods', [AcademicPeriodController::class, 'store'])
                ->middleware('permission:academic.periods.create|academic.periods.manage')
                ->name('periods.store');
            Route::get('periods/{period}', [AcademicPeriodController::class, 'show'])
                ->middleware('permission:academic.periods.view|academic.periods.manage')
                ->name('periods.show');
            Route::get('periods/{period}/edit', [AcademicPeriodController::class, 'edit'])
                ->middleware('permission:academic.periods.update|academic.periods.manage')
                ->name('periods.edit');
            Route::put('periods/{period}', [AcademicPeriodController::class, 'update'])
                ->middleware('permission:academic.periods.update|academic.periods.manage')
                ->name('periods.update');
            Route::patch('periods/{period}/toggle-lock', [AcademicPeriodController::class, 'toggleLock'])
                ->middleware('permission:academic.periods.lock|academic.periods.manage')
                ->name('periods.toggle-lock');

            Route::get('day-configurations', [SchoolDayConfigurationController::class, 'index'])
                ->middleware('permission:academic.day_config.view|academic.day_config.manage')
                ->name('day-configurations.index');
            Route::get('day-configurations/create', [SchoolDayConfigurationController::class, 'create'])
                ->middleware('permission:academic.day_config.create|academic.day_config.manage')
                ->name('day-configurations.create');
            Route::post('day-configurations', [SchoolDayConfigurationController::class, 'store'])
                ->middleware('permission:academic.day_config.create|academic.day_config.manage')
                ->name('day-configurations.store');
            Route::get('day-configurations/{day_configuration}', [SchoolDayConfigurationController::class, 'show'])
                ->middleware('permission:academic.day_config.view|academic.day_config.manage')
                ->name('day-configurations.show');
            Route::get('day-configurations/{day_configuration}/edit', [SchoolDayConfigurationController::class, 'edit'])
                ->middleware('permission:academic.day_config.update|academic.day_config.manage')
                ->name('day-configurations.edit');
            Route::put('day-configurations/{day_configuration}', [SchoolDayConfigurationController::class, 'update'])
                ->middleware('permission:academic.day_config.update|academic.day_config.manage')
                ->name('day-configurations.update');
            Route::patch('day-configurations/{day_configuration}/toggle-lock', [SchoolDayConfigurationController::class, 'toggleLock'])
                ->middleware('permission:academic.day_config.lock|academic.day_config.manage')
                ->name('day-configurations.toggle-lock');

            Route::get('calendars', [AcademicCalendarController::class, 'index'])
                ->middleware('permission:academic.calendars.view|academic.calendars.manage')
                ->name('calendars.index');
            Route::get('calendars/periods', [AcademicCalendarController::class, 'managePeriods'])
                ->middleware('permission:academic.calendars.view|academic.calendars.manage')
                ->name('calendars.periods.index');
            Route::post('calendars/periods/generate', [AcademicCalendarController::class, 'generatePeriods'])
                ->middleware('permission:academic.calendars.create|academic.calendars.update|academic.calendars.manage')
                ->name('calendars.periods.generate');
            Route::put('calendars/periods/{academic_year}', [AcademicCalendarController::class, 'updatePeriods'])
                ->middleware('permission:academic.calendars.create|academic.calendars.update|academic.calendars.manage')
                ->name('calendars.periods.update');
            Route::get('calendars/create', [AcademicCalendarController::class, 'create'])
                ->middleware('permission:academic.calendars.create|academic.calendars.manage')
                ->name('calendars.create');
            Route::post('calendars', [AcademicCalendarController::class, 'store'])
                ->middleware('permission:academic.calendars.create|academic.calendars.manage')
                ->name('calendars.store');
            Route::get('calendars/{calendar}', [AcademicCalendarController::class, 'show'])
                ->middleware('permission:academic.calendars.view|academic.calendars.manage')
                ->name('calendars.show');
            Route::get('calendars/{calendar}/edit', [AcademicCalendarController::class, 'edit'])
                ->middleware('permission:academic.calendars.update|academic.calendars.manage')
                ->name('calendars.edit');
            Route::put('calendars/{calendar}', [AcademicCalendarController::class, 'update'])
                ->middleware('permission:academic.calendars.update|academic.calendars.manage')
                ->name('calendars.update');
            Route::patch('calendars/{calendar}/validate', [AcademicCalendarController::class, 'validateCalendar'])
                ->middleware('permission:academic.calendars.update|academic.calendars.manage')
                ->name('calendars.validate');
            Route::patch('calendars/{calendar}/toggle-active', [AcademicCalendarController::class, 'toggleActive'])
                ->middleware('permission:academic.calendars.disable|academic.calendars.manage')
                ->name('calendars.toggle-active');

            Route::get('events', [CalendarEventController::class, 'index'])
                ->middleware('permission:academic.events.view|academic.events.view_internal|academic.events.view_public|academic.events.manage_all')
                ->name('events.index');
            Route::get('events/create', [CalendarEventController::class, 'create'])
                ->middleware('permission:academic.events.create|academic.events.manage_all')
                ->name('events.create');
            Route::post('events', [CalendarEventController::class, 'store'])
                ->middleware('permission:academic.events.create|academic.events.manage_all')
                ->name('events.store');
            Route::get('events/{event}', [CalendarEventController::class, 'show'])
                ->middleware('permission:academic.events.view|academic.events.view_internal|academic.events.view_public|academic.events.manage_all')
                ->name('events.show');
            Route::get('events/{event}/edit', [CalendarEventController::class, 'edit'])
                ->middleware('permission:academic.events.update|academic.events.manage_all')
                ->name('events.edit');
            Route::put('events/{event}', [CalendarEventController::class, 'update'])
                ->middleware('permission:academic.events.update|academic.events.manage_all')
                ->name('events.update');
            Route::post('events/{event}/publish', [CalendarEventController::class, 'publish'])
                ->middleware('permission:academic.events.publish|academic.events.manage_all')
                ->name('events.publish');
            Route::post('events/{event}/cancel', [CalendarEventController::class, 'cancel'])
                ->middleware('permission:academic.events.cancel|academic.events.manage_all')
                ->name('events.cancel');
            Route::post('events/{event}/archive', [CalendarEventController::class, 'archive'])
                ->middleware('permission:academic.events.archive|academic.events.manage_all')
                ->name('events.archive');
            Route::delete('events/{event}', [CalendarEventController::class, 'destroy'])
                ->middleware('permission:academic.events.delete|academic.events.manage_all')
                ->name('events.destroy');
        });

    Route::prefix('people')
        ->name('people.')
        ->group(function (): void {
            Route::get('dashboard', PeopleDashboardController::class)
                ->middleware('permission:people.dashboard.view')
                ->name('dashboard');

            Route::get('students', [StudentController::class, 'index'])
                ->middleware('permission:people.students.view')
                ->name('students.index');
            Route::get('students/create', [StudentController::class, 'create'])
                ->middleware('permission:people.students.create')
                ->name('students.create');
            Route::post('students', [StudentController::class, 'store'])
                ->middleware('permission:people.students.create')
                ->name('students.store');
            Route::get('students/{student}', [StudentController::class, 'show'])
                ->middleware('can:view,student')
                ->name('students.show');
            Route::get('students/{student}/edit', [StudentController::class, 'edit'])
                ->middleware('permission:people.students.update')
                ->name('students.edit');
            Route::put('students/{student}', [StudentController::class, 'update'])
                ->middleware('permission:people.students.update')
                ->name('students.update');
            Route::patch('students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])
                ->middleware('permission:people.students.disable')
                ->name('students.toggle-status');

            Route::post('students/{student}/parents', [StudentParentController::class, 'store'])
                ->middleware('permission:people.parents.link_student')
                ->name('students.parents.store');
            Route::delete('students/{student}/parents/{parent}', [StudentParentController::class, 'destroy'])
                ->middleware('permission:people.parents.link_student')
                ->name('students.parents.destroy');

            Route::post('students/{student}/documents', [PersonDocumentController::class, 'storeForStudent'])
                ->middleware('permission:people.documents.attach')
                ->name('students.documents.store');

            Route::post('students/{student}/account', [PersonAccountController::class, 'storeForStudent'])
                ->middleware('permission:people.accounts.create')
                ->name('students.account.store');
            Route::post('students/{student}/account/reset', [PersonAccountController::class, 'resetForStudent'])
                ->middleware('permission:people.accounts.reset')
                ->name('students.account.reset');
            Route::patch('students/{student}/account/toggle', [PersonAccountController::class, 'toggleForStudent'])
                ->middleware('permission:people.accounts.suspend')
                ->name('students.account.toggle');
            Route::delete('students/{student}/account', [PersonAccountController::class, 'destroyForStudent'])
                ->middleware('permission:people.accounts.unlink')
                ->name('students.account.destroy');

            Route::post('parents/{parent}/students', [ParentStudentController::class, 'store'])
                ->middleware('permission:people.parents.link_student')
                ->name('parents.students.store');
            Route::delete('parents/{parent}/students/{student}', [ParentStudentController::class, 'destroy'])
                ->middleware('permission:people.parents.link_student')
                ->name('parents.students.destroy');

            Route::post('parents/{parent}/documents', [PersonDocumentController::class, 'storeForParent'])
                ->middleware('permission:people.documents.attach')
                ->name('parents.documents.store');

            Route::post('parents/{parent}/account', [PersonAccountController::class, 'storeForParent'])
                ->middleware('permission:people.accounts.create')
                ->name('parents.account.store');
            Route::post('parents/{parent}/account/reset', [PersonAccountController::class, 'resetForParent'])
                ->middleware('permission:people.accounts.reset')
                ->name('parents.account.reset');
            Route::patch('parents/{parent}/account/toggle', [PersonAccountController::class, 'toggleForParent'])
                ->middleware('permission:people.accounts.suspend')
                ->name('parents.account.toggle');
            Route::delete('parents/{parent}/account', [PersonAccountController::class, 'destroyForParent'])
                ->middleware('permission:people.accounts.unlink')
                ->name('parents.account.destroy');

            Route::get('staff', [StaffController::class, 'index'])
                ->middleware('permission:people.staff.view')
                ->name('staff.index');
            Route::get('staff/create', [StaffController::class, 'create'])
                ->middleware('permission:people.staff.create')
                ->name('staff.create');
            Route::post('staff', [StaffController::class, 'store'])
                ->middleware('permission:people.staff.create')
                ->name('staff.store');
            Route::get('staff/{staff}', [StaffController::class, 'show'])
                ->middleware('can:view,staff')
                ->name('staff.show');
            Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])
                ->middleware('permission:people.staff.update')
                ->name('staff.edit');
            Route::put('staff/{staff}', [StaffController::class, 'update'])
                ->middleware('permission:people.staff.update')
                ->name('staff.update');
            Route::patch('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])
                ->middleware('permission:people.staff.disable')
                ->name('staff.toggle-status');

            Route::post('staff/{staff}/documents', [PersonDocumentController::class, 'storeForStaff'])
                ->middleware('permission:people.documents.attach')
                ->name('staff.documents.store');

            Route::post('staff/{staff}/account', [PersonAccountController::class, 'storeForStaff'])
                ->middleware('permission:people.accounts.create')
                ->name('staff.account.store');
            Route::post('staff/{staff}/account/reset', [PersonAccountController::class, 'resetForStaff'])
                ->middleware('permission:people.accounts.reset')
                ->name('staff.account.reset');
            Route::patch('staff/{staff}/account/toggle', [PersonAccountController::class, 'toggleForStaff'])
                ->middleware('permission:people.accounts.suspend')
                ->name('staff.account.toggle');
            Route::delete('staff/{staff}/account', [PersonAccountController::class, 'destroyForStaff'])
                ->middleware('permission:people.accounts.unlink')
                ->name('staff.account.destroy');

            Route::get('teachers', [TeacherController::class, 'index'])
                ->middleware('permission:people.teachers.view')
                ->name('teachers.index');
            Route::get('teachers/create', [TeacherController::class, 'create'])
                ->middleware('permission:people.teachers.create')
                ->name('teachers.create');
            Route::post('teachers', [TeacherController::class, 'store'])
                ->middleware('permission:people.teachers.create')
                ->name('teachers.store');
            Route::get('teachers/{teacher}', [TeacherController::class, 'show'])
                ->middleware('can:view,teacher')
                ->name('teachers.show');
            Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])
                ->middleware('permission:people.teachers.update')
                ->name('teachers.edit');
            Route::put('teachers/{teacher}', [TeacherController::class, 'update'])
                ->middleware('permission:people.teachers.update')
                ->name('teachers.update');

            Route::post('teachers/{teacher}/assignments', [TeacherAssignmentController::class, 'store'])
                ->middleware('permission:people.teachers.assign_subjects')
                ->name('teachers.assignments.store');
            Route::delete('teachers/{teacher}/assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])
                ->middleware('permission:people.teachers.assign_subjects')
                ->name('teachers.assignments.destroy');

            Route::post('teachers/{teacher}/documents', [PersonDocumentController::class, 'storeForTeacher'])
                ->middleware('permission:people.documents.attach')
                ->name('teachers.documents.store');

            Route::post('teachers/{teacher}/account', [PersonAccountController::class, 'storeForTeacher'])
                ->middleware('permission:people.accounts.create')
                ->name('teachers.account.store');
            Route::post('teachers/{teacher}/account/reset', [PersonAccountController::class, 'resetForTeacher'])
                ->middleware('permission:people.accounts.reset')
                ->name('teachers.account.reset');
            Route::patch('teachers/{teacher}/account/toggle', [PersonAccountController::class, 'toggleForTeacher'])
                ->middleware('permission:people.accounts.suspend')
                ->name('teachers.account.toggle');
            Route::delete('teachers/{teacher}/account', [PersonAccountController::class, 'destroyForTeacher'])
                ->middleware('permission:people.accounts.unlink')
                ->name('teachers.account.destroy');

            Route::post('teachers/{teacher}/invitation', [TeacherInvitationController::class, 'store'])
                ->middleware('permission:people.teachers.invite')
                ->name('teachers.invitation.store');
            Route::patch('teachers/{teacher}/invitation/validate', [TeacherInvitationController::class, 'validateInvitation'])
                ->middleware('permission:people.teachers.validate')
                ->name('teachers.invitation.validate');

            Route::get('documents/{document}/download', [PersonDocumentController::class, 'download'])
                ->middleware('permission:people.documents.view')
                ->name('documents.download');
            Route::delete('documents/{document}', [PersonDocumentController::class, 'destroy'])
                ->middleware('permission:people.documents.remove')
                ->name('documents.destroy');

            Route::get('parents', [ParentController::class, 'index'])
                ->middleware('permission:people.parents.view|people.parents.create|people.parents.update|people.parents.link_student')
                ->name('parents.index');
            Route::get('parents/create', [ParentController::class, 'create'])
                ->middleware('permission:people.parents.create')
                ->name('parents.create');
            Route::post('parents', [ParentController::class, 'store'])
                ->middleware('permission:people.parents.create')
                ->name('parents.store');
            Route::get('parents/{parent}', [ParentController::class, 'show'])
                ->middleware('can:view,parent')
                ->name('parents.show');
            Route::get('parents/{parent}/edit', [ParentController::class, 'edit'])
                ->middleware('permission:people.parents.update')
                ->name('parents.edit');
            Route::put('parents/{parent}', [ParentController::class, 'update'])
                ->middleware('permission:people.parents.update')
                ->name('parents.update');
        });
});

require __DIR__.'/auth.php';

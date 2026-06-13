<?php

namespace Tests\Feature\Core;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\AcademicCycleCategory;
use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarEventType;
use App\Enums\Academic\CalendarEventVisibility;
use App\Enums\Academic\CalendarScopeType;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\CalendarEvent;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Core\DashboardService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_exposes_academic_management_cards_with_live_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $level = AcademicLevel::query()->where('is_active', true)->firstOrFail();

        AcademicClassroom::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
            'series_id' => null,
            'name' => 'Dashboard CM2 A',
            'code' => 'DASH-CM2-A',
            'capacity' => 38,
            'description' => 'Classe de test pour le dashboard.',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $calendar = AcademicCalendar::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Calendrier dashboard test',
            'description' => 'Calendrier de test pour le dashboard.',
            'scope_type' => CalendarScopeType::Global->value,
            'cycle_id' => null,
            'level_id' => null,
            'class_id' => null,
            'period_type' => AcademicPeriodType::Trimester->value,
            'period_count' => 3,
            'is_active' => true,
            'validated_at' => now(),
            'validated_by' => $admin->id,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::query()->create([
            'school_id' => $school->id,
            'academic_calendar_id' => $calendar->id,
            'title' => 'Réunion dashboard',
            'description' => 'Événement de test pour le dashboard.',
            'event_type' => CalendarEventType::Reunion->value,
            'start_datetime' => now()->addDays(5),
            'end_datetime' => now()->addDays(5)->addHours(2),
            'is_full_day' => false,
            'location' => 'Salle des professeurs',
            'target_type' => 'global',
            'target_id' => null,
            'status' => CalendarEventStatus::Draft->value,
            'visibility' => CalendarEventVisibility::Internal->value,
            'requires_validation' => false,
            'created_by' => $admin->id,
        ]);

        $dashboard = app(DashboardService::class)->build($admin);
        $cards = collect($dashboard['module_cards'])->keyBy('label');

        $this->assertSame(route('core.academic-years.index'), $cards[__('ui.nav.academic_years')]['route']);
        $this->assertSame((string) \App\Models\AcademicYear::query()->count(), $cards[__('ui.nav.academic_years')]['value']);

        $this->assertSame(route('academic.cycles.index'), $cards[__('ui.nav.academic_cycles')]['route']);
        $this->assertSame((string) AcademicCycle::query()->where('is_active', true)->count(), $cards[__('ui.nav.academic_cycles')]['value']);

        $this->assertSame(route('academic.levels.index'), $cards[__('ui.nav.academic_levels')]['route']);
        $this->assertSame((string) AcademicLevel::query()->where('is_active', true)->count(), $cards[__('ui.nav.academic_levels')]['value']);

        $this->assertSame(route('academic.series.index'), $cards[__('ui.nav.academic_series')]['route']);
        $this->assertSame((string) \App\Models\AcademicSeries::query()->where('is_active', true)->count(), $cards[__('ui.nav.academic_series')]['value']);

        $this->assertSame(route('academic.classes.index'), $cards[__('ui.nav.academic_classes')]['route']);
        $this->assertSame(
            (string) AcademicClassroom::query()
                ->where('school_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->count(),
            $cards[__('ui.nav.academic_classes')]['value']
        );

        $this->assertSame(route('academic.events.index'), $cards[__('ui.nav.calendar_events')]['route']);
        $this->assertSame(
            (string) CalendarEvent::query()
                ->where('school_id', $school->id)
                ->whereHas('calendar', fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->count(),
            $cards[__('ui.nav.calendar_events')]['value']
        );

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.nav.academic_years'))
            ->assertSee(__('ui.nav.academic_cycles'))
            ->assertSee(__('ui.nav.academic_levels'))
            ->assertSee(__('ui.nav.academic_series'))
            ->assertSee(__('ui.nav.academic_classes'))
            ->assertSee(__('ui.nav.calendar_events'));
    }

    public function test_parent_account_sees_only_parent_portal_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $parentRole = Role::query()->where('slug', 'parent')->firstOrFail();

        $parentUser = User::factory()->create([
            'is_active' => true,
            'name' => 'Mme HOUNTON Alice',
            'email' => 'alice.parent@example.com',
        ]);
        $parentUser->roles()->attach($parentRole->id, ['assigned_at' => now()]);
        $parentUser->refresh()->load('roles');

        $guardian = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => $parentUser->id,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 62000001',
            'email' => 'alice.parent@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $linkedStudent = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9901',
            'last_name' => 'HOUNTON',
            'first_names' => 'Marc',
            'gender' => 'masculin',
            'date_of_birth' => '2014-06-12',
            'place_of_birth' => 'Cotonou',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $otherStudent = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9902',
            'last_name' => 'KOUASSI',
            'first_names' => 'Nadia',
            'gender' => 'feminin',
            'date_of_birth' => '2015-04-10',
            'place_of_birth' => 'Porto-Novo',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $linkedStudent->parents()->attach($guardian->id, [
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $dashboard = app(DashboardService::class)->build($parentUser);

        $this->assertSame('parent', $dashboard['mode']);
        $stats = collect($dashboard['stats'])->pluck('value', 'label');
        $this->assertSame('1', $stats[__('ui.dashboard.parent.stats.children')]);
        $this->assertSame('1', $stats[__('ui.dashboard.parent.stats.active_children')]);
        $this->assertCount(1, $dashboard['children']);
        $this->assertSame($linkedStudent->fullName(), $dashboard['children'][0]['name']);
        $this->assertSame(route('people.students.show', $linkedStudent), $dashboard['children'][0]['route']);

        $this->actingAs($parentUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.dashboard.parent.title'))
            ->assertSee(__('ui.nav.parent_space'))
            ->assertSee(__('ui.nav.parent_dashboard'))
            ->assertSee(__('ui.nav.parent_record'))
            ->assertSee(__('ui.nav.profile'))
            ->assertSee(__('ui.nav.calendar_events'))
            ->assertSee($linkedStudent->fullName())
            ->assertSee(__('ui.dashboard.parent.children_title'))
            ->assertDontSee($otherStudent->fullName())
            ->assertDontSee(__('ui.dashboard.financial_cards.total_revenue.label'))
            ->assertDontSee(__('ui.dashboard.modules.people'))
            ->assertDontSee(__('ui.dashboard.recent_activity.title'))
            ->assertDontSee(__('ui.preview.schooling'))
            ->assertDontSee(__('ui.preview.staff'))
            ->assertDontSee(__('ui.nav.people_management'))
            ->assertDontSee(__('ui.nav.academic_reference'))
            ->assertDontSee(__('ui.nav.core_system'));
    }

    public function test_teacher_account_sees_only_teacher_portal_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = $this->defaultSchool();
        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();

        $teacherUser = User::factory()->create([
            'is_active' => true,
            'name' => 'M. KOFFI Robert',
            'email' => 'robert.teacher@example.com',
        ]);
        $teacherUser->roles()->attach($teacherRole->id, ['assigned_at' => now()]);
        $teacherUser->refresh()->load('roles');

        $teacher = Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'matricule' => 'ENS-2026-0001',
            'last_name' => 'KOFFI',
            'first_names' => 'Robert',
            'gender' => 'masculin',
            'phone' => '+229 61000002',
            'email' => 'robert.teacher@example.com',
            'address' => 'Cotonou',
            'specialty' => 'SVT',
            'diploma' => 'Licence',
            'contract_type' => 'contractuel',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $teacherUser->id,
            'updated_by' => $teacherUser->id,
        ]);

        $dashboard = app(DashboardService::class)->build($teacherUser);

        $this->assertSame('teacher', $dashboard['mode']);
        $this->assertSame($teacher->fullName(), $dashboard['portal_profile']['title']);
        $this->assertSame('0', collect($dashboard['stats'])->pluck('value', 'label')[__('ui.dashboard.teacher.stats.assignments')]);

        $this->actingAs($teacherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.dashboard.teacher.title'))
            ->assertSee(__('ui.nav.teacher_space'))
            ->assertSee(__('ui.nav.teacher_dashboard'))
            ->assertSee(__('ui.nav.teacher_record'))
            ->assertSee(__('ui.nav.teacher_assignments'))
            ->assertSee(__('ui.nav.teacher_documents'))
            ->assertSee(__('ui.nav.profile'))
            ->assertSee(__('ui.nav.calendar_events'))
            ->assertSee($teacher->fullName())
            ->assertDontSee(__('ui.dashboard.financial_cards.total_revenue.label'))
            ->assertDontSee(__('ui.dashboard.modules.people'))
            ->assertDontSee(__('ui.preview.schooling'))
            ->assertDontSee(__('ui.nav.people_management'))
            ->assertDontSee(__('ui.nav.academic_reference'))
            ->assertDontSee(__('ui.nav.core_system'));
    }

    public function test_teacher_account_with_primary_profile_sees_main_class_in_teacher_portal(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();

        $cycle = AcademicCycle::query()->create([
            'name' => 'Cycle primaire',
            'code' => 'CYCLE-PRIMAIRE-TEST',
            'category' => AcademicCycleCategory::Primary->value,
            'description' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $level = AcademicLevel::query()->create([
            'cycle_id' => $cycle->id,
            'name' => 'CM1',
            'code' => 'CM1-TEST',
            'short_name' => 'CM1',
            'description' => null,
            'sort_order' => 1,
            'uses_series' => false,
            'is_active' => true,
        ]);

        $classroom = AcademicClassroom::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
            'series_id' => null,
            'name' => 'CM1 A',
            'code' => 'CM1-A-TEST',
            'capacity' => 40,
            'description' => null,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $teacherUser = User::factory()->create([
            'is_active' => true,
            'name' => 'Mme ADA Grace',
            'email' => 'grace.teacher@example.com',
        ]);
        $teacherUser->roles()->attach($teacherRole->id, ['assigned_at' => now()]);
        $teacherUser->refresh()->load('roles');

        $teacher = Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'academic_cycle_id' => $cycle->id,
            'main_classroom_id' => $classroom->id,
            'main_subject_id' => null,
            'matricule' => 'ENS-2026-1001',
            'last_name' => 'ADA',
            'first_names' => 'Grace',
            'gender' => 'feminin',
            'phone' => '+229 62000002',
            'email' => 'grace.teacher@example.com',
            'address' => 'Cotonou',
            'specialty' => 'Pédagogie générale',
            'diploma' => 'Licence',
            'contract_type' => 'contractuel',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $teacherUser->id,
            'updated_by' => $teacherUser->id,
        ]);

        $dashboard = app(DashboardService::class)->build($teacherUser);

        $this->assertSame('teacher', $dashboard['mode']);

        $stats = collect($dashboard['stats'])->pluck('value', 'label');
        $this->assertSame('1', $stats[__('ui.dashboard.teacher.stats.assignments')]);
        $this->assertSame('1', $stats[__('ui.dashboard.teacher.stats.classes')]);

        $profileFields = collect($dashboard['portal_profile']['fields'])->pluck('value', 'label');
        $this->assertSame($cycle->name, $profileFields[__('people.teachers.form.academic_cycle')]);
        $this->assertSame(__('people.teachers.options.tracks.primary'), $profileFields[__('people.teachers.show.track')]);
        $this->assertSame($classroom->label(), $profileFields[__('people.teachers.form.main_classroom')]);

        $this->assertCount(1, $dashboard['portal_related']['items']);
        $this->assertSame($classroom->label(), $dashboard['portal_related']['items'][0]['title']);
        $this->assertSame(route('people.teachers.show', $teacher).'#pedagogic-profile', $dashboard['portal_related']['items'][0]['route']);

        $this->actingAs($teacherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.dashboard.teacher.title'))
            ->assertSee($classroom->label())
            ->assertSee(__('people.teachers.form.main_classroom'))
            ->assertDontSee(__('ui.dashboard.teacher.alerts.no_assignments.title'));
    }

    public function test_student_account_sees_only_student_portal_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $studentRole = Role::query()->where('slug', 'eleve')->firstOrFail();

        $studentUser = User::factory()->create([
            'is_active' => true,
            'name' => 'Nadia KOUTON',
            'email' => 'nadia.student@example.com',
        ]);
        $studentUser->roles()->attach($studentRole->id, ['assigned_at' => now()]);
        $studentUser->refresh()->load('roles');

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'matricule' => 'ELV-2026-0001',
            'last_name' => 'KOUTON',
            'first_names' => 'Nadia',
            'gender' => 'feminin',
            'date_of_birth' => '2015-03-14',
            'place_of_birth' => 'Porto-Novo',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $studentUser->id,
            'updated_by' => $studentUser->id,
        ]);

        $dashboard = app(DashboardService::class)->build($studentUser);

        $this->assertSame('student', $dashboard['mode']);
        $this->assertSame($student->fullName(), $dashboard['portal_profile']['title']);
        $this->assertSame('0', collect($dashboard['stats'])->pluck('value', 'label')[__('ui.dashboard.student.stats.parents')]);

        $this->actingAs($studentUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.dashboard.student.title'))
            ->assertSee(__('ui.nav.student_space'))
            ->assertSee(__('ui.nav.student_dashboard'))
            ->assertSee(__('ui.nav.student_record'))
            ->assertSee(__('ui.nav.student_parents'))
            ->assertSee(__('ui.nav.student_documents'))
            ->assertSee(__('ui.nav.profile'))
            ->assertSee(__('ui.nav.calendar_events'))
            ->assertSee($student->fullName())
            ->assertDontSee(__('ui.dashboard.financial_cards.total_revenue.label'))
            ->assertDontSee(__('ui.dashboard.modules.people'))
            ->assertDontSee(__('ui.preview.schooling'))
            ->assertDontSee(__('ui.nav.people_management'))
            ->assertDontSee(__('ui.nav.academic_reference'))
            ->assertDontSee(__('ui.nav.core_system'));
    }

    private function defaultSchool(): School
    {
        return School::query()->where('is_default', true)->firstOrFail();
    }

    private function activeAcademicYear()
    {
        return \App\Models\AcademicYear::query()->active()->firstOrFail();
    }
}

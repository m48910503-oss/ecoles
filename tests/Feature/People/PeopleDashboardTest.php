<?php

namespace Tests\Feature\People;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\TeacherInvitation;
use App\Models\Teacher;
use App\Models\User;
use App\Services\People\PeopleDashboardService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_service_returns_business_counts_alerts_and_quick_actions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $actor = $this->adminUser();
        $inactiveUser = User::factory()->create([
            'is_active' => false,
        ]);

        Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9001',
            'last_name' => 'KOUAME',
            'first_names' => 'Maya',
            'gender' => 'feminin',
            'date_of_birth' => '2013-05-14',
            'place_of_birth' => 'Cotonou',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => $inactiveUser->id,
            'last_name' => 'DOSSOU',
            'first_names' => 'Awa',
            'gender' => 'feminin',
            'phone' => '+229 61000001',
            'email' => 'awa.dossou@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ENS-2026-9001',
            'last_name' => 'HOUNTON',
            'first_names' => 'Kossi',
            'gender' => 'masculin',
            'phone' => '+229 61000002',
            'email' => 'kossi.hounton@example.com',
            'address' => 'Porto-Novo',
            'specialty' => 'Mathématiques',
            'diploma' => 'Licence',
            'contract_type' => 'contractuel',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $teacher = Teacher::query()->where('matricule', 'ENS-2026-9001')->firstOrFail();

        TeacherInvitation::query()->create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'recipient_email' => $teacher->email,
            'token_hash' => hash('sha256', 'people-dashboard-teacher-invitation-pending'),
            'status' => TeacherInvitation::STATUS_PENDING,
            'sent_at' => now()->subDays(4),
            'expires_at' => now()->addDays(3),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        TeacherInvitation::query()->create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'recipient_email' => $teacher->email,
            'token_hash' => hash('sha256', 'people-dashboard-teacher-invitation-completed'),
            'status' => TeacherInvitation::STATUS_COMPLETED,
            'sent_at' => now()->subDays(3),
            'completed_at' => now()->subDays(2),
            'expires_at' => now()->addDays(2),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        TeacherInvitation::query()->create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'recipient_email' => $teacher->email,
            'token_hash' => hash('sha256', 'people-dashboard-teacher-invitation-validated'),
            'status' => TeacherInvitation::STATUS_VALIDATED,
            'sent_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
            'validated_at' => now()->subHours(12),
            'expires_at' => now()->addDays(1),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        TeacherInvitation::query()->create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'recipient_email' => $teacher->email,
            'token_hash' => hash('sha256', 'people-dashboard-teacher-invitation-expired'),
            'status' => TeacherInvitation::STATUS_PENDING,
            'sent_at' => now()->subDays(1),
            'expires_at' => now()->subHour(),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-9001',
            'last_name' => 'AHO',
            'first_names' => 'Sophie',
            'gender' => 'feminin',
            'phone' => '+229 61000003',
            'email' => 'sophie.aho@example.com',
            'address' => 'Abomey-Calavi',
            'position' => 'secretaire',
            'system_role' => 'autre',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $dashboard = app(PeopleDashboardService::class)->build($actor);

        $stats = collect($dashboard['stats'])->pluck('value', 'label');
        $this->assertSame('1', $stats[__('people.dashboard.stats.students_active')]);
        $this->assertSame('1', $stats[__('people.dashboard.stats.parents')]);
        $this->assertSame('1', $stats[__('people.dashboard.stats.teachers')]);
        $this->assertSame('1', $stats[__('people.dashboard.stats.staff')]);
        $this->assertSame('1', $stats[__('people.dashboard.stats.without_parents')]);
        $this->assertSame('1', $stats[__('people.dashboard.stats.inactive_accounts')]);

        $teacherInvitationStats = $dashboard['teacher_invitations_stats'];
        $this->assertSame(4, $teacherInvitationStats['total']);
        $this->assertSame(1, $teacherInvitationStats['pending']);
        $this->assertSame(1, $teacherInvitationStats['to_validate']);
        $this->assertSame(1, $teacherInvitationStats['validated']);
        $this->assertSame(1, $teacherInvitationStats['expired']);

        $alertTitles = collect($dashboard['alerts'])->pluck('title')->all();
        $this->assertContains(__('people.dashboard.alerts.without_parents.title'), $alertTitles);
        $this->assertContains(__('people.dashboard.alerts.parents_without_children.title'), $alertTitles);
        $this->assertContains(__('people.dashboard.alerts.teachers_without_assignments.title'), $alertTitles);
        $this->assertContains(__('people.dashboard.alerts.staff_without_account.title'), $alertTitles);
        $this->assertContains(__('people.dashboard.alerts.inactive_accounts.title'), $alertTitles);

        $quickActionLabels = collect($dashboard['quick_actions'])->pluck('label')->all();
        $this->assertContains(__('people.dashboard.quick_actions.create_parent'), $quickActionLabels);
        $this->assertContains(__('people.dashboard.quick_actions.view_parents'), $quickActionLabels);
        $this->assertContains(__('people.dashboard.quick_actions.view_accounts'), $quickActionLabels);

        $this->assertCount(1, $dashboard['recent_students']);
        $this->assertCount(1, $dashboard['recent_parents']);
        $this->assertCount(1, $dashboard['recent_teachers']);
        $this->assertCount(1, $dashboard['recent_staff']);
        $this->assertCount(4, $dashboard['recent_teacher_invitations']);

        $recentInvitationStatuses = collect($dashboard['recent_teacher_invitations'])
            ->map(fn (TeacherInvitation $invitation) => $invitation->displayStatus())
            ->all();

        $this->assertContains(TeacherInvitation::STATUS_PENDING, $recentInvitationStatuses);
        $this->assertContains(TeacherInvitation::STATUS_COMPLETED, $recentInvitationStatuses);
        $this->assertContains(TeacherInvitation::STATUS_VALIDATED, $recentInvitationStatuses);
        $this->assertContains('expired', $recentInvitationStatuses);

        $this->actingAs($actor)
            ->get(route('people.dashboard'))
            ->assertOk()
            ->assertSee(__('people.dashboard.teacher_invitations.actions.view_pending_teachers'))
            ->assertSee(__('people.dashboard.teacher_invitations.recent.actions.resend_link'));
    }

    public function test_limited_user_sees_only_authorized_dashboard_sections(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $actor = $this->adminUser();
        $limitedUser = $this->accountantUser();

        Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9002',
            'last_name' => 'ADJAKPA',
            'first_names' => 'Noah',
            'gender' => 'masculin',
            'date_of_birth' => '2014-03-02',
            'place_of_birth' => 'Cotonou',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'TCHIBOZO',
            'first_names' => 'Mireille',
            'gender' => 'feminin',
            'phone' => '+229 61000011',
            'email' => 'mireille.tchibozo@example.com',
            'address' => 'Cotonou',
            'profession' => 'Infirmière',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ENS-2026-9002',
            'last_name' => 'MEDE',
            'first_names' => 'Patrice',
            'gender' => 'masculin',
            'phone' => '+229 61000012',
            'email' => 'patrice.mede@example.com',
            'address' => 'Porto-Novo',
            'specialty' => 'SVT',
            'diploma' => 'Master',
            'contract_type' => 'titulaire',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-9002',
            'last_name' => 'BADA',
            'first_names' => 'Celine',
            'gender' => 'feminin',
            'phone' => '+229 61000013',
            'email' => 'celine.bada@example.com',
            'address' => 'Ouidah',
            'position' => 'agent_administratif',
            'system_role' => 'autre',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->actingAs($limitedUser)
            ->get(route('people.dashboard'))
            ->assertOk()
            ->assertSee(__('people.dashboard.alerts_title'))
            ->assertSee(__('people.dashboard.quick_actions_title'))
            ->assertSee(__('people.dashboard.stats.students_active'))
            ->assertSee(__('people.dashboard.stats.parents'))
            ->assertDontSee(__('people.dashboard.stats.teachers'))
            ->assertDontSee(__('people.dashboard.stats.staff'))
            ->assertDontSee(__('people.dashboard.alerts.teachers_without_assignments.title'))
            ->assertDontSee(__('people.dashboard.alerts.staff_without_account.title'))
            ->assertDontSee(__('people.dashboard.quick_actions.view_teachers'))
            ->assertDontSee(__('people.dashboard.quick_actions.view_staff'))
            ->assertDontSee(__('people.dashboard.quick_actions.create_teacher'))
            ->assertDontSee(__('people.dashboard.quick_actions.create_staff'))
            ->assertDontSee(__('people.dashboard.teacher_invitations.title'))
            ->assertSee(__('people.dashboard.recent_students.title'))
            ->assertSee(__('people.dashboard.recent_parents.title'))
            ->assertDontSee(__('people.dashboard.recent_teachers.title'))
            ->assertDontSee(__('people.dashboard.teacher_invitations.recent.title'))
            ->assertDontSee(__('people.dashboard.recent_staff.title'));
    }

    private function adminUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::query()->where('slug', 'administrateur')->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user->load('roles.permissions');
    }

    private function accountantUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::query()->where('slug', 'comptable')->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user->load('roles.permissions');
    }

    private function defaultSchool(): School
    {
        return School::query()->where('is_default', true)->firstOrFail();
    }

    private function activeAcademicYear(): AcademicYear
    {
        return AcademicYear::query()->active()->firstOrFail();
    }
}

<?php

namespace Tests\Feature\People;

use App\Enums\Academic\AcademicCycleCategory;
use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Services\People\TeacherService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherInvitationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_generate_teacher_invitation_and_manual_account_creation_revokes_pending_invitation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $teacher = $this->createPrimaryTeacher($admin, $school, $academicYear, 'marie.invitee@example.com');

        $response = $this->actingAs($admin)->post(route('people.teachers.invitation.store', $teacher));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('teacher_invitation_url')
            ->assertRedirect(route('people.teachers.show', $teacher).'#account');

        $invitationUrl = session('teacher_invitation_url');
        $this->assertIsString($invitationUrl);

        $teacher->refresh()->load('latestInvitation');

        $this->assertNotNull($teacher->latestInvitation);
        $this->assertSame(TeacherInvitation::STATUS_PENDING, $teacher->latestInvitation?->status);
        $this->assertSame('marie.invitee@example.com', $teacher->latestInvitation?->recipient_email);

        $this->actingAs($admin)
            ->post(route('people.teachers.account.store', $teacher))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.teachers.show', $teacher).'#account');

        $teacher->refresh()->load('user.roles', 'latestInvitation');

        $this->assertNotNull($teacher->user_id);
        $this->assertTrue($teacher->user?->is_active ?? false);
        $this->assertSame(TeacherInvitation::STATUS_REVOKED, $teacher->latestInvitation?->status);
        $this->assertDatabaseHas('teacher_invitations', [
            'id' => $teacher->latestInvitation?->id,
            'status' => TeacherInvitation::STATUS_REVOKED,
        ]);
    }

    public function test_teacher_can_complete_invitation_and_secretary_can_validate_it(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $teacher = $this->createPrimaryTeacher($admin, $school, $academicYear, 'paul.invitee@example.com');

        $response = $this->actingAs($admin)->post(route('people.teachers.invitation.store', $teacher));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('teacher_invitation_url')
            ->assertRedirect(route('people.teachers.show', $teacher).'#account');

        $invitationUrl = (string) session('teacher_invitation_url');
        $token = Str::afterLast((string) parse_url($invitationUrl, PHP_URL_PATH), '/');

        Auth::logout();

        $this->get(route('teacher.invitations.show', ['token' => $token]))
            ->assertOk()
            ->assertSee($teacher->fullName())
            ->assertSee(__('people.teachers.invitations.form.title'));

        $this->post(route('teacher.invitations.store', ['token' => $token]), [
            'email' => 'paul.teacher.login@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $teacher->refresh()->load('user.roles', 'latestInvitation');

        $this->assertNotNull($teacher->user_id);
        $this->assertFalse($teacher->user?->is_active ?? true);
        $this->assertNull($teacher->user?->email_verified_at);
        $this->assertSame(TeacherInvitation::STATUS_COMPLETED, $teacher->latestInvitation?->status);
        $this->assertTrue($teacher->user?->roles->pluck('slug')->contains('enseignant') ?? false);
        $this->assertDatabaseHas('users', [
            'id' => $teacher->user_id,
            'email' => 'paul.teacher.login@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('people.dashboard'))
            ->assertOk()
            ->assertSee(__('people.dashboard.teacher_invitations.recent.actions.validate_signup'));

        $this->actingAs($admin)
            ->patch(route('people.teachers.invitation.validate', $teacher))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.teachers.show', $teacher).'#account');

        $teacher->refresh()->load('user.roles', 'latestInvitation');

        $this->assertTrue($teacher->user?->is_active ?? false);
        $this->assertNotNull($teacher->user?->email_verified_at);
        $this->assertSame(TeacherInvitation::STATUS_VALIDATED, $teacher->latestInvitation?->status);
        $this->assertDatabaseHas('teacher_invitations', [
            'id' => $teacher->latestInvitation?->id,
            'status' => TeacherInvitation::STATUS_VALIDATED,
        ]);
    }

    public function test_teacher_list_can_filter_pending_invitations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $pendingTeacher = $this->createPrimaryTeacher(
            $admin,
            $school,
            $academicYear,
            'pending.filter@example.com',
            'ENS-2026-PEND-001',
            'PEND-FILTER',
            'Alice',
            '001'
        );

        $otherTeacher = $this->createPrimaryTeacher(
            $admin,
            $school,
            $academicYear,
            'other.filter@example.com',
            'ENS-2026-OTHER-001',
            'OTHER-FILTER',
            'Bruno',
            '002'
        );

        $this->actingAs($admin)
            ->post(route('people.teachers.invitation.store', $pendingTeacher))
            ->assertSessionHasNoErrors();

        $filteredIds = app(TeacherService::class)
            ->indexQuery($school->id, ['invitation' => 'pending'])
            ->pluck('id')
            ->all();

        $this->assertContains($pendingTeacher->id, $filteredIds);
        $this->assertNotContains($otherTeacher->id, $filteredIds);

        $this->actingAs($admin)
            ->get(route('people.teachers.index', ['invitation' => 'pending', 'search' => 'PEND-FILTER']))
            ->assertOk()
            ->assertSee($pendingTeacher->fullName())
            ->assertSee(__('people.teachers.index.filters.pending_invitation'))
            ->assertDontSee($otherTeacher->fullName());
    }

    private function createPrimaryTeacher(
        User $actor,
        School $school,
        AcademicYear $academicYear,
        string $email,
        string $matricule = 'ENS-2026-INV-001',
        string $lastName = 'KOFFI',
        string $firstNames = 'Marie',
        string $codeSuffix = '001'
    ): Teacher
    {
        $cycle = AcademicCycle::query()->create([
            'name' => 'Cycle primaire '.$codeSuffix,
            'code' => 'CYCLE-PRIMAIRE-INV-'.$codeSuffix,
            'category' => AcademicCycleCategory::Primary->value,
            'description' => null,
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $level = AcademicLevel::query()->create([
            'cycle_id' => $cycle->id,
            'name' => 'CM1 '.$codeSuffix,
            'code' => 'CM1-INV-'.$codeSuffix,
            'short_name' => 'CM1-'.$codeSuffix,
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
            'name' => 'CM1 '.$codeSuffix.' A',
            'code' => 'CM1-A-INV-'.$codeSuffix,
            'capacity' => 40,
            'description' => null,
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        return Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'academic_cycle_id' => $cycle->id,
            'main_classroom_id' => $classroom->id,
            'main_subject_id' => null,
            'matricule' => $matricule,
            'last_name' => $lastName,
            'first_names' => $firstNames,
            'gender' => 'feminin',
            'phone' => '+229 61001010',
            'email' => $email,
            'address' => 'Cotonou',
            'specialty' => 'Pédagogie générale',
            'diploma' => 'Licence',
            'contract_type' => 'contractuel',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function adminUser(): User
    {
        return User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
    }

    private function defaultSchool(): School
    {
        return School::query()->where('is_default', true)->firstOrFail();
    }

    private function activeAcademicYear(): AcademicYear
    {
        return AcademicYear::query()->where('is_active', true)->firstOrFail();
    }
}

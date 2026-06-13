<?php

namespace Tests\Feature\People;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonAccountModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_student_account_with_generated_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-2001',
            'last_name' => 'AGBOSSOU',
            'first_names' => 'Daniel Junior',
            'gender' => 'masculin',
            'date_of_birth' => '2012-05-08',
            'place_of_birth' => 'Cotonou',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $expectedEmail = Str::lower('eleve-'.Str::slug($student->matricule, '-').'@'.Str::slug($school->code ?: 'ecole', '-').'.local');

        $response = $this->actingAs($admin)->post(route('people.students.account.store', $student));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'Mot de passe temporaire');
            })
            ->assertRedirect(route('people.students.show', $student).'#account');

        $student->refresh();
        $student->load('user.roles');

        $this->assertNotNull($student->user_id);
        $this->assertSame($expectedEmail, $student->user?->email);
        $this->assertTrue($student->user?->is_active ?? false);
        $this->assertTrue($student->user?->roles->pluck('slug')->contains('eleve') ?? false);
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'email' => $expectedEmail,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.created',
            'auditable_id' => $student->id,
        ]);

        $this->actingAs($admin)
            ->get(route('people.students.show', $student))
            ->assertOk()
            ->assertSee(__('people.accounts.student_title'))
            ->assertSee($expectedEmail);
    }

    public function test_administrator_can_link_existing_parent_account_by_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $parent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 91000001',
            'email' => 'alice.hounton@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $user = User::factory()->create([
            'name' => 'Alice Hounton',
            'email' => 'alice.hounton@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('people.parents.account.store', $parent));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'rattaché');
            })
            ->assertRedirect(route('people.parents.show', $parent).'#account');

        $parent->refresh();
        $parent->load('user.roles');

        $this->assertSame($user->id, $parent->user_id);
        $this->assertTrue($parent->user?->roles->pluck('slug')->contains('parent') ?? false);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.linked',
            'auditable_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->get(route('people.parents.show', $parent))
            ->assertOk()
            ->assertSee(__('people.accounts.parent_title'))
            ->assertSee($user->email);
    }

    public function test_administrator_can_reset_internal_parent_account_with_temporary_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $parent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'MODOU',
            'first_names' => 'Sara',
            'gender' => 'feminin',
            'phone' => '+229 91000002',
            'email' => null,
            'address' => 'Cotonou',
            'profession' => 'Gestionnaire',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $generatedEmail = Str::lower('parent-'.$parent->id.'@'.Str::slug($school->code ?: 'ecole', '-').'.local');

        $user = User::factory()->create([
            'name' => $parent->fullName(),
            'email' => $generatedEmail,
            'is_active' => true,
        ]);

        $parentRole = Role::query()->where('slug', 'parent')->firstOrFail();
        $user->roles()->attach($parentRole->id, ['assigned_at' => now()]);

        $parent->forceFill([
            'user_id' => $user->id,
            'updated_by' => $admin->id,
        ])->save();

        $originalPassword = $user->password;

        $response = $this->actingAs($admin)->post(route('people.parents.account.reset', $parent));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('people.parents.show', $parent).'#account');

        $user->refresh();

        $this->assertNotSame($originalPassword, $user->password);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.reset',
            'auditable_id' => $parent->id,
        ]);
    }

    public function test_administrator_can_suspend_reactivate_and_unlink_student_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-2002',
            'last_name' => 'SOGLO',
            'first_names' => 'Agnès',
            'gender' => 'feminin',
            'date_of_birth' => '2012-07-19',
            'place_of_birth' => 'Abomey',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $user = User::factory()->create([
            'name' => $student->fullName(),
            'email' => 'agnes.soglo@example.com',
            'is_active' => true,
        ]);

        $studentRole = Role::query()->where('slug', 'eleve')->firstOrFail();
        $user->roles()->attach($studentRole->id, ['assigned_at' => now()]);

        $student->forceFill([
            'user_id' => $user->id,
            'updated_by' => $admin->id,
        ])->save();

        $this->actingAs($admin)
            ->patch(route('people.students.account.toggle', $student))
            ->assertRedirect(route('people.students.show', $student).'#account');

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertNotEmpty($user->suspended_reason);
        $this->assertStringContainsString('Suspension rapide', $user->suspended_reason);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.suspended',
            'auditable_id' => $student->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('people.students.account.toggle', $student))
            ->assertRedirect(route('people.students.show', $student).'#account');

        $user->refresh();

        $this->assertTrue($user->is_active);
        $this->assertNull($user->suspended_reason);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.reactivated',
            'auditable_id' => $student->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('people.students.account.destroy', $student))
            ->assertRedirect(route('people.students.show', $student).'#account');

        $student->refresh();

        $this->assertNull($student->user_id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.unlinked',
            'auditable_id' => $student->id,
        ]);
    }

    public function test_administrator_can_create_staff_account_with_generated_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $staff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-1001',
            'last_name' => 'KONAN',
            'first_names' => 'Mariam',
            'gender' => 'feminin',
            'phone' => '+229 91003001',
            'email' => null,
            'address' => 'Cotonou',
            'position' => 'secretaire',
            'system_role' => 'secretaire',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $expectedEmail = Str::lower('personnel-'.Str::slug($staff->matricule, '-').'@'.Str::slug($school->code ?: 'ecole', '-').'.local');

        $response = $this->actingAs($admin)->post(route('people.staff.account.store', $staff));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'Mot de passe temporaire');
            })
            ->assertRedirect(route('people.staff.show', $staff).'#account');

        $staff->refresh();
        $staff->load('user.roles');

        $this->assertNotNull($staff->user_id);
        $this->assertSame($expectedEmail, $staff->user?->email);
        $this->assertTrue($staff->user?->roles->pluck('slug')->contains('secretaire') ?? false);
        $this->assertDatabaseHas('users', [
            'id' => $staff->user_id,
            'email' => $expectedEmail,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.account.created',
            'auditable_id' => $staff->id,
        ]);
    }

    public function test_administrator_can_create_staff_account_for_non_mapped_system_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $staff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-1002',
            'last_name' => 'HESSOU',
            'first_names' => 'Kevin',
            'gender' => 'masculin',
            'phone' => '+229 91003002',
            'email' => null,
            'address' => 'Cotonou',
            'position' => 'agent_technique',
            'system_role' => 'agent_technique',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('people.staff.account.store', $staff));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('people.staff.show', $staff).'#account');

        $staff->refresh();
        $staff->load('user.roles');

        $this->assertNotNull($staff->user_id);
        $this->assertTrue($staff->user?->roles->pluck('slug')->contains('personnel') ?? false);
        $this->assertDatabaseHas('roles', [
            'slug' => 'personnel',
        ]);
    }

    public function test_user_without_permission_cannot_create_student_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-2003',
            'last_name' => 'KOFFI',
            'first_names' => 'Sarah',
            'gender' => 'feminin',
            'date_of_birth' => '2013-02-12',
            'place_of_birth' => 'Lomé',
            'nationality' => 'Togolaise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('people.students.account.store', $student))
            ->assertForbidden();
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
        return AcademicYear::query()->active()->firstOrFail();
    }
}

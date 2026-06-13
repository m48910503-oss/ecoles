<?php

namespace Tests\Feature\People;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\People\StudentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_people_permission_cannot_access_student_list(): void
    {
        $this->seed(DatabaseSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->get(route('people.students.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_student_with_parent_link(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();
        $expectedMatricule = app(StudentService::class)->nextMatricule($school->id);

        $response = $this->actingAs($admin)->post(route('people.students.store'), [
            'last_name' => 'AGBOSSOU',
            'first_names' => 'Daniel Junior',
            'gender' => 'masculin',
            'date_of_birth' => '2012-05-08',
            'place_of_birth' => 'Cotonou',
            'nationality' => 'Béninoise',
            'status' => 'preinscrit',
            'entry_academic_year_id' => $academicYear->id,
            'guardian_last_name' => 'AGBOSSOU',
            'guardian_first_names' => 'Alice',
            'guardian_gender' => 'feminin',
            'guardian_phone' => '+229 90000000',
            'guardian_email' => 'alice.agbossou@example.com',
            'guardian_address' => 'Cotonou',
            'guardian_profession' => 'Commerçante',
            'guardian_relationship_type' => 'mere',
            'guardian_is_primary_contact' => true,
            'guardian_is_financial_responsible' => true,
            'guardian_can_receive_notifications' => true,
            'guardian_can_pickup_child' => true,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.students.index'));

        $student = Student::query()->where('matricule', $expectedMatricule)->firstOrFail();
        $guardian = Guardian::query()->where('email', 'alice.agbossou@example.com')->firstOrFail();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_id' => $school->id,
            'matricule' => $expectedMatricule,
            'last_name' => 'AGBOSSOU',
        ]);

        $this->assertDatabaseHas('parents', [
            'id' => $guardian->id,
            'school_id' => $school->id,
            'email' => 'alice.agbossou@example.com',
        ]);

        $this->assertDatabaseHas('student_parent', [
            'student_id' => $student->id,
            'parent_id' => $guardian->id,
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'student.created',
            'auditable_id' => $student->id,
        ]);
    }

    public function test_parent_can_view_linked_student_but_not_unlinked_student(): void
    {
        $this->seed(DatabaseSeeder::class);

        $parentRole = Role::query()->where('slug', 'parent')->firstOrFail();
        $parentUser = User::factory()->create(['is_active' => true]);
        $parentUser->roles()->attach($parentRole->id, ['assigned_at' => now()]);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $linkedGuardian = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => $parentUser->id,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 91000000',
            'email' => 'alice.hounton@example.com',
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
            'matricule' => 'ELV-2026-9991',
            'last_name' => 'HOUNTON',
            'first_names' => 'Marc',
            'gender' => 'masculin',
            'date_of_birth' => '2012-03-14',
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

        $linkedStudent->parents()->attach($linkedGuardian->id, [
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $this->actingAs($parentUser)
            ->get(route('people.students.show', $linkedStudent))
            ->assertOk()
            ->assertSee($linkedStudent->fullName());

        $otherStudent = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9992',
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
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $this->actingAs($parentUser)
            ->get(route('people.students.show', $otherStudent))
            ->assertForbidden();
    }

    public function test_parent_cannot_remove_the_last_primary_contact(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9993',
            'last_name' => 'ADEMI',
            'first_names' => 'Kossi',
            'gender' => 'masculin',
            'date_of_birth' => '2011-11-21',
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

        $guardian = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'ADEMI',
            'first_names' => 'Marie',
            'gender' => 'feminin',
            'phone' => '+229 92000000',
            'email' => 'marie.ademi@example.com',
            'address' => 'Cotonou',
            'profession' => 'Fonctionnaire',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $student->parents()->attach($guardian->id, [
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => false,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('people.students.parents.store', $student), [
            'parent_id' => $guardian->id,
            'relationship_type' => 'mere',
            'is_primary_contact' => false,
            'is_financial_responsible' => false,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
        ]);

        $response->assertSessionHasErrors('parent_id');

        $this->assertDatabaseHas('student_parent', [
            'student_id' => $student->id,
            'parent_id' => $guardian->id,
            'is_primary_contact' => true,
        ]);
    }

    public function test_quick_suspend_sets_a_reason_for_the_student(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-9994',
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

        $this->actingAs($admin)
            ->patch(route('people.students.toggle-status', $student))
            ->assertRedirect();

        $student->refresh();

        $this->assertSame('suspendu', $student->status);
        $this->assertNotNull($student->status_reason);
        $this->assertStringContainsString('Suspension rapide', $student->status_reason);
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

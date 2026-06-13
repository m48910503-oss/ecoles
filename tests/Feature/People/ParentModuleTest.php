<?php

namespace Tests\Feature\People;

use App\Models\Guardian;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_people_permission_cannot_access_parent_list(): void
    {
        $this->seed(DatabaseSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->get(route('people.parents.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_and_list_parents(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $response = $this->actingAs($admin)->post(route('people.parents.store'), [
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 91000001',
            'email' => 'alice.hounton@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => 'Dossier initial.',
        ]);

        $response->assertSessionHasNoErrors();

        $parent = Guardian::query()->where('email', 'alice.hounton@example.com')->firstOrFail();

        $response->assertRedirect(route('people.parents.show', $parent));

        $this->assertDatabaseHas('parents', [
            'id' => $parent->id,
            'school_id' => $school->id,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'phone' => '+229 91000001',
            'email' => 'alice.hounton@example.com',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'parent.created',
            'auditable_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->get(route('people.parents.index'))
            ->assertOk()
            ->assertSee('HOUNTON')
            ->assertSee('Alice');
    }

    public function test_administrator_can_update_parent_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $parent = Guardian::query()->create([
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

        $response = $this->actingAs($admin)->put(route('people.parents.update', $parent), [
            'last_name' => 'ADEMI',
            'first_names' => 'Marie',
            'gender' => 'feminin',
            'phone' => '+229 92000000',
            'email' => 'marie.ademi@example.com',
            'address' => 'Cotonou',
            'profession' => 'Fonctionnaire principale',
            'status' => 'suspendu',
            'notes' => 'Mise à jour du dossier.',
        ]);

        $response->assertSessionHasNoErrors();

        $parent->refresh();

        $this->assertSame('suspendu', $parent->status);
        $this->assertSame('Fonctionnaire principale', $parent->profession);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'parent.updated',
            'auditable_id' => $parent->id,
        ]);
    }

    public function test_parent_can_view_own_profile_but_not_another_parent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $parentRole = Role::query()->where('slug', 'parent')->firstOrFail();
        $parentUser = User::factory()->create(['is_active' => true]);
        $parentUser->roles()->attach($parentRole->id, ['assigned_at' => now()]);

        $school = $this->defaultSchool();

        $ownParent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => $parentUser->id,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 93000000',
            'email' => 'alice.parent@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $otherParent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'KOFFI',
            'first_names' => 'Jean',
            'gender' => 'masculin',
            'phone' => '+229 93000001',
            'email' => 'jean.koffi@example.com',
            'address' => 'Porto-Novo',
            'profession' => 'Employé',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $parentUser->id,
            'updated_by' => $parentUser->id,
        ]);

        $this->actingAs($parentUser)
            ->get(route('people.parents.show', $ownParent))
            ->assertOk()
            ->assertSee($ownParent->fullName());

        $this->actingAs($parentUser)
            ->get(route('people.parents.show', $otherParent))
            ->assertForbidden();
    }

    public function test_duplicate_contact_is_blocked_when_creating_parent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'MODOU',
            'first_names' => 'Sara',
            'gender' => 'feminin',
            'phone' => '+229 94000000',
            'email' => 'sara.modou@example.com',
            'address' => 'Cotonou',
            'profession' => 'Gestionnaire',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('people.parents.store'), [
            'last_name' => 'MODOU',
            'first_names' => 'Sophie',
            'gender' => 'feminin',
            'phone' => '+229 94000000',
            'email' => 'sophie.modou@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('parents', 1);
    }

    public function test_administrator_can_link_student_from_parent_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $parent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 91000010',
            'email' => 'alice.hounton.10@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-0101',
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
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('people.parents.students.store', $parent), [
            'student_id' => $student->id,
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => 'Lien créé depuis la fiche parent.',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.parents.show', [
                'parent' => $parent,
                'student_id' => $student->id,
            ]));

        $this->assertDatabaseHas('student_parent', [
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'student.parent.linked',
            'auditable_id' => $student->id,
        ]);
    }

    public function test_administrator_can_update_student_relation_from_parent_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $parent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'KOFFI',
            'first_names' => 'Marie',
            'gender' => 'feminin',
            'phone' => '+229 91000011',
            'email' => 'marie.koffi.11@example.com',
            'address' => 'Cotonou',
            'profession' => 'Fonctionnaire',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-0102',
            'last_name' => 'KOFFI',
            'first_names' => 'Sarah',
            'gender' => 'feminin',
            'date_of_birth' => '2011-09-03',
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

        $student->parents()->attach($parent->id, [
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => false,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('people.parents.students.store', $parent), [
            'student_id' => $student->id,
            'relationship_type' => 'tuteur_legal',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => false,
            'can_pickup_child' => false,
            'notes' => 'Relation mise à jour depuis la fiche parent.',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.parents.show', [
                'parent' => $parent,
                'student_id' => $student->id,
            ]));

        $this->assertDatabaseHas('student_parent', [
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'relationship_type' => 'tuteur_legal',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => false,
            'can_pickup_child' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'student.parent.updated',
            'auditable_id' => $student->id,
        ]);
    }

    public function test_administrator_can_unlink_student_from_parent_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $primaryParent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'ADJAH',
            'first_names' => 'Aline',
            'gender' => 'feminin',
            'phone' => '+229 91000012',
            'email' => 'aline.adjah.12@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $parentToRemove = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'ADJAH',
            'first_names' => 'Koffi',
            'gender' => 'masculin',
            'phone' => '+229 91000013',
            'email' => 'koffi.adjah.13@example.com',
            'address' => 'Porto-Novo',
            'profession' => 'Ingénieur',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-0103',
            'last_name' => 'ADJAH',
            'first_names' => 'Ruth',
            'gender' => 'feminin',
            'date_of_birth' => '2010-12-01',
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

        $student->parents()->attach($primaryParent->id, [
            'relationship_type' => 'mere',
            'is_primary_contact' => true,
            'is_financial_responsible' => true,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $student->parents()->attach($parentToRemove->id, [
            'relationship_type' => 'autre',
            'is_primary_contact' => false,
            'is_financial_responsible' => false,
            'can_receive_notifications' => true,
            'can_pickup_child' => true,
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('people.parents.students.destroy', [
            'parent' => $parentToRemove,
            'student' => $student,
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.parents.show', $parentToRemove));

        $this->assertDatabaseMissing('student_parent', [
            'student_id' => $student->id,
            'parent_id' => $parentToRemove->id,
        ]);

        $this->assertDatabaseHas('student_parent', [
            'student_id' => $student->id,
            'parent_id' => $primaryParent->id,
            'is_primary_contact' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'student.parent.unlinked',
            'auditable_id' => $student->id,
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
        return AcademicYear::query()->active()->firstOrFail();
    }
}

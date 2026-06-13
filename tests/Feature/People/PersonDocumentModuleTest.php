<?php

namespace Tests\Feature\People;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\PersonDocument;
use App\Models\Role;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonDocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_attach_and_download_a_student_document(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $admin = $this->adminUser();
        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-0201',
            'last_name' => 'AGBO',
            'first_names' => 'Daniel',
            'gender' => 'masculin',
            'date_of_birth' => '2012-01-10',
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

        $file = UploadedFile::fake()->create('acte-naissance.pdf', 256, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('people.students.documents.store', $student), [
            'document_type' => 'birth_certificate',
            'document_file' => $file,
            'notes' => 'Document d’état civil.',
            'is_sensitive' => true,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.students.show', $student).'#documents');

        $document = PersonDocument::query()->firstOrFail();

        $this->assertDatabaseHas('person_documents', [
            'id' => $document->id,
            'school_id' => $school->id,
            'documentable_type' => Student::class,
            'documentable_id' => $student->id,
            'document_type' => 'birth_certificate',
            'original_name' => 'acte-naissance.pdf',
            'is_sensitive' => true,
        ]);

        Storage::disk('local')->assertExists($document->file_path);

        $downloadResponse = $this->actingAs($admin)->get(route('people.documents.download', $document));

        $downloadResponse
            ->assertOk()
            ->assertDownload('acte-naissance.pdf');

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.document.attached',
            'auditable_id' => $document->id,
        ]);
    }

    public function test_administrator_can_attach_and_remove_a_parent_document(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $parent = Guardian::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 91000020',
            'email' => 'alice.hounton.20@example.com',
            'address' => 'Cotonou',
            'profession' => 'Commerçante',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $file = UploadedFile::fake()->create('piece-identite.pdf', 256, 'application/pdf');

        $this->actingAs($admin)->post(route('people.parents.documents.store', $parent), [
            'document_type' => 'identity_card',
            'document_file' => $file,
            'notes' => 'Pièce justificative.',
            'is_sensitive' => true,
        ])->assertSessionHasNoErrors();

        $document = PersonDocument::query()->firstOrFail();
        $storedPath = $document->file_path;

        Storage::disk('local')->assertExists($storedPath);

        $response = $this->actingAs($admin)->delete(route('people.documents.destroy', $document));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.parents.show', $parent).'#documents');

        $this->assertSoftDeleted('person_documents', [
            'id' => $document->id,
        ]);

        Storage::disk('local')->assertMissing($storedPath);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.document.removed',
            'auditable_id' => $document->id,
        ]);
    }

    public function test_administrator_can_attach_and_remove_a_staff_document(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $staff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-2001',
            'last_name' => 'KOFFI',
            'first_names' => 'Mariam',
            'gender' => 'feminin',
            'phone' => '+229 91004001',
            'email' => 'mariam.koffi.2@example.com',
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

        $file = UploadedFile::fake()->create('piece-identite.pdf', 256, 'application/pdf');

        $this->actingAs($admin)->post(route('people.staff.documents.store', $staff), [
            'document_type' => 'identity_card',
            'document_file' => $file,
            'notes' => 'Pièce justificative du personnel.',
            'is_sensitive' => true,
        ])->assertSessionHasNoErrors();

        $document = PersonDocument::query()->firstOrFail();
        $storedPath = $document->file_path;

        Storage::disk('local')->assertExists($storedPath);

        $response = $this->actingAs($admin)->delete(route('people.documents.destroy', $document));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.staff.show', $staff).'#documents');

        $this->assertSoftDeleted('person_documents', [
            'id' => $document->id,
        ]);

        Storage::disk('local')->assertMissing($storedPath);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'person.document.removed',
            'auditable_id' => $document->id,
        ]);
    }

    public function test_user_without_document_permission_cannot_attach_a_document(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $school = $this->defaultSchool();
        $academicYear = $this->activeAcademicYear();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ELV-2026-0202',
            'last_name' => 'KOFFI',
            'first_names' => 'Sarah',
            'gender' => 'feminin',
            'date_of_birth' => '2013-06-15',
            'place_of_birth' => 'Porto-Novo',
            'nationality' => 'Béninoise',
            'photo_path' => null,
            'academic_classroom_id' => null,
            'entry_academic_year_id' => $academicYear->id,
            'status' => 'actif',
            'status_reason' => null,
            'notes' => null,
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->post(route('people.students.documents.store', $student), [
            'document_type' => 'photo',
            'document_file' => UploadedFile::fake()->create('photo.jpg', 200, 'image/jpeg'),
            'notes' => 'Photo de profil.',
            'is_sensitive' => false,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('person_documents', 0);
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

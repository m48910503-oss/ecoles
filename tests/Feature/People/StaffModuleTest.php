<?php

namespace Tests\Feature\People;

use App\Models\Staff;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_access_staff_list(): void
    {
        $this->seed(DatabaseSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $this->actingAs($teacher)
            ->get(route('people.staff.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_and_list_staff(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $response = $this->actingAs($admin)->post(route('people.staff.store'), [
            'last_name' => 'KOFFI',
            'first_names' => 'Mariam',
            'gender' => 'feminin',
            'phone' => '+229 91001010',
            'email' => 'mariam.koffi@example.com',
            'address' => 'Cotonou',
            'position' => 'secretaire',
            'system_role' => 'secretaire',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => 'Nouvelle secrétaire administrative.',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $staff = Staff::query()->firstOrFail();

        $this->assertSame($school->id, $staff->school_id);
        $this->assertSame('KOFFI', $staff->last_name);
        $this->assertSame('Mariam', $staff->first_names);
        $this->assertSame('secretaire', $staff->position);
        $this->assertSame('secretaire', $staff->system_role);
        $this->assertSame('contractuel', $staff->contract_type);
        $this->assertSame('actif', $staff->status);
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'matricule' => $staff->matricule,
            'email' => 'mariam.koffi@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('people.staff.index'))
            ->assertOk()
            ->assertSee('KOFFI')
            ->assertSee(__('people.staff.index.title'));
    }

    public function test_administrator_can_update_staff_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $staff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-9001',
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice',
            'gender' => 'feminin',
            'phone' => '+229 91002020',
            'email' => 'alice.hounton@example.com',
            'address' => 'Porto-Novo',
            'position' => 'agent_administratif',
            'system_role' => 'autre',
            'contract_type' => 'titulaire',
            'service_start_date' => '2023-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put(route('people.staff.update', $staff), [
            'last_name' => 'HOUNTON',
            'first_names' => 'Alice Marie',
            'gender' => 'feminin',
            'phone' => '+229 91002021',
            'email' => 'alice.marie@example.com',
            'address' => 'Porto-Novo',
            'position' => 'secretaire',
            'system_role' => 'secretaire',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-01-15',
            'status' => 'actif',
            'notes' => 'Mise à jour du dossier administratif.',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('people.staff.show', $staff));

        $staff->refresh();

        $this->assertSame('Alice Marie', $staff->first_names);
        $this->assertSame('+229 91002021', $staff->phone);
        $this->assertSame('alice.marie@example.com', $staff->email);
        $this->assertSame('secretaire', $staff->position);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'staff.updated',
            'auditable_id' => $staff->id,
        ]);
    }

    public function test_administrator_can_suspend_and_reactivate_staff(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        $staff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-9002',
            'last_name' => 'DOSSOU',
            'first_names' => 'Paul',
            'gender' => 'masculin',
            'phone' => '+229 91002022',
            'email' => 'paul.dossou@example.com',
            'address' => 'Cotonou',
            'position' => 'comptable',
            'system_role' => 'comptable',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-02-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('people.staff.toggle-status', $staff))
            ->assertRedirect();

        $staff->refresh();
        $this->assertSame('suspendu', $staff->status);

        $this->actingAs($admin)
            ->patch(route('people.staff.toggle-status', $staff))
            ->assertRedirect();

        $staff->refresh();
        $this->assertSame('actif', $staff->status);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'people',
            'action' => 'staff.reactivated',
            'auditable_id' => $staff->id,
        ]);
    }

    public function test_duplicate_contact_is_blocked_when_creating_staff(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->adminUser();
        $school = $this->defaultSchool();

        Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-9003',
            'last_name' => 'ADJAYI',
            'first_names' => 'Claire',
            'gender' => 'feminin',
            'phone' => '+229 91002023',
            'email' => 'claire.adjayi@example.com',
            'address' => 'Cotonou',
            'position' => 'agent_administratif',
            'system_role' => 'autre',
            'contract_type' => 'contractuel',
            'service_start_date' => '2023-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('people.staff.store'), [
            'last_name' => 'ADJAYI',
            'first_names' => 'Claire',
            'gender' => 'feminin',
            'phone' => '+229 91002023',
            'email' => 'claire.adjayi@example.com',
            'address' => 'Cotonou',
            'position' => 'secretaire',
            'system_role' => 'secretaire',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => null,
        ]);

        $response
            ->assertSessionHasErrors(['email', 'phone']);
    }

    private function adminUser(): User
    {
        return User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
    }

    private function defaultSchool(): School
    {
        return School::query()->where('is_default', true)->firstOrFail();
    }
}

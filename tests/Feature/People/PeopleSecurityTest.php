<?php

namespace Tests\Feature\People;

use App\Models\Role;
use App\Models\School;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_people_dashboard_permission_cannot_access_people_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->get(route('people.dashboard'))
            ->assertForbidden();
    }

    public function test_staff_can_view_own_profile_but_not_another_staff_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $school = $this->defaultSchool();

        $ownStaff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'matricule' => 'PERS-2026-9901',
            'last_name' => 'MGBE',
            'first_names' => 'Laura',
            'gender' => 'feminin',
            'phone' => '+229 91009901',
            'email' => 'laura.mgbe@example.com',
            'address' => 'Cotonou',
            'position' => 'secretaire',
            'system_role' => 'autre',
            'contract_type' => 'contractuel',
            'service_start_date' => '2024-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $otherStaff = Staff::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'PERS-2026-9902',
            'last_name' => 'ADJAHO',
            'first_names' => 'Paul',
            'gender' => 'masculin',
            'phone' => '+229 91009902',
            'email' => 'paul.adjaho@example.com',
            'address' => 'Porto-Novo',
            'position' => 'comptable',
            'system_role' => 'autre',
            'contract_type' => 'titulaire',
            'service_start_date' => '2023-09-01',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('people.staff.show', $ownStaff))
            ->assertOk()
            ->assertSee($ownStaff->fullName());

        $this->actingAs($user)
            ->get(route('people.staff.show', $otherStaff))
            ->assertForbidden();
    }

    public function test_teacher_can_view_own_profile_but_not_another_teacher_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $school = $this->defaultSchool();

        $ownTeacher = Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'matricule' => 'ENS-2026-9901',
            'last_name' => 'YAO',
            'first_names' => 'Nadia',
            'gender' => 'feminin',
            'phone' => '+229 91019901',
            'email' => 'nadia.yao@example.com',
            'address' => 'Cotonou',
            'specialty' => 'Mathématiques',
            'diploma' => 'Licence',
            'contract_type' => 'contractuel',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $otherTeacher = Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => null,
            'matricule' => 'ENS-2026-9902',
            'last_name' => 'KOUASSI',
            'first_names' => 'Jean',
            'gender' => 'masculin',
            'phone' => '+229 91019902',
            'email' => 'jean.kouassi@example.com',
            'address' => 'Porto-Novo',
            'specialty' => 'SVT',
            'diploma' => 'Master',
            'contract_type' => 'titulaire',
            'status' => 'actif',
            'notes' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('people.teachers.show', $ownTeacher))
            ->assertOk()
            ->assertSee($ownTeacher->fullName());

        $this->actingAs($user)
            ->get(route('people.teachers.show', $otherTeacher))
            ->assertForbidden();
    }

    private function defaultSchool(): School
    {
        return School::query()->where('is_default', true)->firstOrFail();
    }
}

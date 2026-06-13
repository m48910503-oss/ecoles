<?php

namespace Tests\Feature\Core;

use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_an_additional_school(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $academicYear = AcademicYear::query()->active()->firstOrFail();
        $academicYear->update(['multi_school_enabled' => true]);

        $response = $this->actingAs($admin)->post(route('core.schools.store'), [
            'name' => 'Lycée du Nord',
            'email' => 'nord@ecoles.test',
            'phone' => '+22901010101',
            'location' => 'Parakou',
            'is_active' => '1',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.schools.index'));

        $this->assertDatabaseHas('schools', [
            'name' => 'Lycée du Nord',
            'address' => 'Parakou',
        ]);
    }

    public function test_user_without_school_permission_cannot_access_school_index(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $permission = Permission::query()->where('name', 'core.dashboard.view')->firstOrFail();
        $role = Role::query()->create([
            'name' => 'Lecture tableau',
            'slug' => 'lecture-tableau',
            'description' => 'Accès limité.',
            'is_system' => false,
            'is_optional' => true,
            'is_active' => true,
        ]);
        $role->permissions()->sync([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->get(route('core.schools.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_switch_current_school_when_multi_mode_is_active(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $academicYear = AcademicYear::query()->active()->firstOrFail();
        $academicYear->update(['multi_school_enabled' => true]);

        $school = School::query()->create([
            'code' => 'COLLEGE-SUD',
            'name' => 'Collège du Sud',
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('core.schools.switch', $school));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('current_school_id', $school->id);
    }

    public function test_school_must_be_inactive_before_delete(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $school = School::query()->create([
            'code' => 'COLLEGE-OUEST',
            'name' => 'Collège Ouest',
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('core.schools.destroy', $school));

        $response->assertSessionHasErrors('school');
        $this->assertDatabaseHas('schools', ['id' => $school->id]);
    }

    public function test_administrator_can_delete_inactive_school(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $school = School::query()->create([
            'code' => 'COLLEGE-OUEST',
            'name' => 'Collège Ouest',
            'is_active' => false,
            'is_default' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('core.schools.destroy', $school));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.schools.index'));

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }
}

<?php

namespace Tests\Feature\Core;

use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Core\SchoolService;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiSchoolActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_seed_creates_default_school_in_mono_mode(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $defaultSchool = School::query()->where('is_default', true)->first();
        $activeAcademicYear = AcademicYear::query()->active()->firstOrFail();

        $this->assertNotNull($defaultSchool);
        $this->assertSame('ERP Scolaire', $defaultSchool->name);
        $this->assertFalse($activeAcademicYear->multi_school_enabled);
        $this->assertFalse(app(SchoolService::class)->isMultiSchoolEnabled());
    }

    public function test_administrator_can_enable_multi_school_mode_on_active_academic_year(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $academicYear = AcademicYear::query()->active()->firstOrFail();

        $response = $this->actingAs($admin)->put(route('core.academic-years.update', $academicYear), [
            'start_date' => $academicYear->start_date?->format('Y-m-d'),
            'end_date' => $academicYear->end_date?->format('Y-m-d'),
            'is_active' => '1',
            'enrollment_open' => $academicYear->enrollment_open ? '1' : '0',
            'multi_school_enabled' => '1',
            'notes' => $academicYear->notes,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.academic-years.index'));

        $this->assertTrue($academicYear->fresh()->multi_school_enabled);
        $this->assertTrue(app(SchoolService::class)->isMultiSchoolEnabled());
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'core',
            'action' => 'academic_year.activated',
        ]);
    }

    public function test_user_without_multi_school_permission_cannot_toggle_mode_on_academic_year(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $academicYear = AcademicYear::query()->active()->firstOrFail();
        $permission = Permission::query()->where('name', 'core.academic_years.update')->firstOrFail();
        $role = Role::query()->create([
            'name' => 'Gestion année limitée',
            'slug' => 'gestion-annee-limitee',
            'description' => 'Peut modifier une année sans gérer le mode multi.',
            'is_system' => false,
            'is_optional' => true,
            'is_active' => true,
        ]);
        $role->permissions()->sync([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $response = $this->actingAs($user)->put(route('core.academic-years.update', $academicYear), [
            'start_date' => $academicYear->start_date?->format('Y-m-d'),
            'end_date' => $academicYear->end_date?->format('Y-m-d'),
            'is_active' => '1',
            'enrollment_open' => $academicYear->enrollment_open ? '1' : '0',
            'multi_school_enabled' => '1',
            'notes' => $academicYear->notes,
        ]);

        $response->assertForbidden();
        $this->assertFalse($academicYear->fresh()->multi_school_enabled);
    }

    public function test_updating_general_settings_keeps_default_school_synced_in_mono_mode(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $activeAcademicYear = AcademicYear::query()->active()->firstOrFail();

        $response = $this->actingAs($admin)->put(route('core.settings.update'), [
            'general' => [
                'school_name' => 'Collège Horizon',
                'school_phone' => '+22901020304',
                'support_email' => 'contact@horizon.test',
            ],
            'security' => [
                'session_timeout_minutes' => 120,
                'password_min_length' => 12,
                'allow_public_registration' => false,
            ],
            'finance' => [
                'minimum_initial_payment' => 0,
                'financial_blocking_enabled' => false,
            ],
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.settings.edit'));

        $this->assertDatabaseHas('schools', [
            'is_default' => true,
            'name' => 'Collège Horizon',
            'phone' => '+22901020304',
            'email' => 'contact@horizon.test',
        ]);
        $this->assertFalse($activeAcademicYear->fresh()->multi_school_enabled);
    }
}

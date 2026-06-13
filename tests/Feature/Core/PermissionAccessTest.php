<?php

namespace Tests\Feature\Core;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_access_protected_core_route(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $response = $this->actingAs($user)->get(route('core.users.index'));

        $response->assertForbidden();
    }

    public function test_administrator_can_access_protected_core_route(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('core.users.index'));

        $response->assertOk();
    }

    public function test_user_without_role_management_permission_cannot_access_permission_catalog(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($teacherRole->id, ['assigned_at' => now()]);

        $response = $this->actingAs($user)->get(route('core.permissions.index'));

        $response->assertForbidden();
    }

    public function test_administrator_can_access_permission_catalog(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('core.permissions.index'));

        $response->assertOk()->assertSee('Permissions');
    }
}

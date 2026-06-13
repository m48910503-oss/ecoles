<?php

namespace Tests\Feature\Core;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_is_audited(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('core.users.store'), [
            'name' => 'Enseignant Test',
            'email' => 'enseignant.test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role_ids' => [$teacherRole->id],
            'is_active' => true,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'enseignant.test@example.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'core',
            'action' => 'user.created',
        ]);
    }
}

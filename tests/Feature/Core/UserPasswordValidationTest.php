<?php

namespace Tests\Feature\Core;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPasswordValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_shows_translated_password_validation_message(): void
    {
        $this->seed(CoreSystemSeeder::class);
        app()->setLocale('fr');

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $teacherRole = Role::query()->where('slug', 'enseignant')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('core.users.store'), [
            'name' => 'Enseignant Validation',
            'email' => 'enseignant.validation@example.com',
            'password' => 'lowercase123!',
            'password_confirmation' => 'lowercase123!',
            'role_ids' => [$teacherRole->id],
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors([
            'password' => 'Le champ mot de passe doit contenir au moins une majuscule et une minuscule.',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'enseignant.validation@example.com',
        ]);
    }
}

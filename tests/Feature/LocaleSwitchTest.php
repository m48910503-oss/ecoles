<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_locale_and_see_dashboard_in_english(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->from(route('dashboard'))
            ->post(route('locale.switch', ['locale' => 'en']));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame('en', session('locale'));
        $this->assertSame('en', $admin->fresh()->preferred_locale);

        $dashboard = $this
            ->withSession([])
            ->actingAs($admin->fresh())
            ->get(route('dashboard'));

        $dashboard
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Total revenue')
            ->assertSee('View all');

        $usersPage = $this
            ->withSession([])
            ->actingAs($admin->fresh())
            ->get(route('core.users.index'));

        $usersPage
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Create a user');
    }

    public function test_invalid_locale_returns_not_found(): void
    {
        $response = $this->post(route('locale.switch', ['locale' => 'es']));

        $response->assertNotFound();
    }
}

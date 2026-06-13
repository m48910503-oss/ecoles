<?php

namespace Tests\Feature\Core;

use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_academic_year_cannot_be_created_as_active_while_another_year_is_active(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $currentActiveYear = AcademicYear::query()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('core.academic-years.store'), [
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
            'enrollment_open' => true,
            'configure_now' => false,
            'notes' => 'Nouvelle année académique.',
        ]);

        $response
            ->assertSessionHasErrors('is_active');

        $this->assertDatabaseMissing('academic_years', [
            'code' => '2026-2027',
        ]);

        $this->assertDatabaseHas('academic_years', [
            'id' => $currentActiveYear->id,
            'is_active' => true,
        ]);

        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
    }

    public function test_active_multi_school_year_cannot_be_switched_back_to_mono(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $academicYear = AcademicYear::query()->active()->firstOrFail();
        $academicYear->update(['multi_school_enabled' => true]);

        $response = $this->actingAs($admin)->put(route('core.academic-years.update', $academicYear), [
            'start_date' => $academicYear->start_date?->format('Y-m-d'),
            'end_date' => $academicYear->end_date?->format('Y-m-d'),
            'is_active' => '1',
            'enrollment_open' => $academicYear->enrollment_open ? '1' : '0',
            'multi_school_enabled' => '0',
            'notes' => $academicYear->notes,
        ]);

        $response->assertSessionHasErrors('multi_school_enabled');
        $this->assertTrue($academicYear->fresh()->multi_school_enabled);
    }

    public function test_administrator_can_delete_inactive_academic_year(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $academicYear = AcademicYear::query()->create([
            'name' => '2023-2024',
            'code' => '2023-2024',
            'start_date' => '2023-09-01',
            'end_date' => '2024-07-31',
            'is_active' => false,
            'enrollment_open' => false,
            'multi_school_enabled' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('core.academic-years.destroy', $academicYear));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.academic-years.index'));

        $this->assertDatabaseMissing('academic_years', ['id' => $academicYear->id]);
    }

    public function test_administrator_can_delete_active_academic_year(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $admin = User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
        $activeAcademicYear = AcademicYear::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('core.academic-years.index'))
            ->assertOk()
            ->assertSee(__('core.common.actions.delete'))
            ->assertSee(__('core.common.feedback.warning_title'))
            ->assertSee(__('core.academic_years.delete_active_warning'));

        $response = $this->actingAs($admin)->delete(route('core.academic-years.destroy', $activeAcademicYear));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.academic-years.index'));

        $this->assertDatabaseMissing('academic_years', ['id' => $activeAcademicYear->id]);
        $this->assertSame(0, AcademicYear::query()->where('is_active', true)->count());
    }
}

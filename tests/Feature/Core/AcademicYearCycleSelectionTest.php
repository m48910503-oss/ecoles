<?php

namespace Tests\Feature\Core;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\SchoolDayType;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use App\Models\SchoolDayConfiguration;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcademicYearCycleSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_starts_on_the_first_step_of_the_wizard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->actingAs($this->admin())->get(route('core.academic-years.create'));

        $response
            ->assertOk()
            ->assertSee(__('core.academic_years.wizard.title'))
            ->assertSee(__('core.academic_years.wizard.steps.general.title'))
            ->assertSee(__('core.academic_years.wizard.steps.cycles.title'))
            ->assertSee(__('core.academic_years.wizard.steps.activation.title'))
            ->assertSee(__('core.academic_years.wizard.steps.configuration.title'))
            ->assertSee('name="start_date"', false)
            ->assertSee('name="end_date"', false)
            ->assertSee(__('core.common.actions.next'))
            ->assertDontSee('name="cycle_ids[]"', false);
    }

    public function test_create_route_can_reopen_step_one_from_the_wizard_state(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('core.academic-years.create.step1.store'), [
                'start_date' => '2026-09-01',
                'end_date' => '2027-07-31',
                'notes' => 'Année de test.',
            ])
            ->assertRedirect(route('core.academic-years.create.step2'));

        $response = $this->actingAs($admin)
            ->get(route('core.academic-years.create', ['wizard_step' => 1]));

        $response
            ->assertOk()
            ->assertSee(__('core.academic_years.wizard.steps.general.title'))
            ->assertSee('name="start_date"', false)
            ->assertSee('name="end_date"', false)
            ->assertDontSee('name="cycle_ids[]"', false);
    }

    public function test_step_two_requires_at_least_one_cycle_when_cycles_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('core.academic-years.create.step1.store'), [
                'start_date' => '2026-09-01',
                'end_date' => '2027-07-31',
                'notes' => 'Année sans cycle.',
            ])
            ->assertRedirect(route('core.academic-years.create.step2'));

        $response = $this->actingAs($admin)
            ->from(route('core.academic-years.create.step2'))
            ->post(route('core.academic-years.create.step2.store'), []);

        $response
            ->assertRedirect(route('core.academic-years.create.step2'))
            ->assertSessionHasErrors([
                'cycle_ids' => __('core.academic_years.validation.cycles_required'),
            ]);

        $this->assertDatabaseMissing('academic_years', [
            'code' => '2026-2027',
        ]);
    }

    public function test_wizard_creates_the_year_persists_the_cycles_and_syncs_configuration(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('core.academic-years.create.step1.store'), [
                'start_date' => '2026-09-01',
                'end_date' => '2027-07-31',
                'notes' => 'Année académique de test.',
            ])
            ->assertRedirect(route('core.academic-years.create.step2'));

        $this->actingAs($admin)
            ->get(route('core.academic-years.create.step2'))
            ->assertOk()
            ->assertSee(__('core.academic_years.form.cycles_title'))
            ->assertSee('name="cycle_ids[]"', false)
            ->assertSee($primaryCycle->name)
            ->assertSee($secondaryCycle->name)
            ->assertSee(route('core.academic-years.create', ['wizard_step' => 1]), false);

        $this->actingAs($admin)
            ->post(route('core.academic-years.create.step2.store'), [
                'cycle_ids' => [$primaryCycle->id, $secondaryCycle->id],
            ])
            ->assertRedirect(route('core.academic-years.create.step3'));

        $this->actingAs($admin)
            ->get(route('core.academic-years.create.step3'))
            ->assertOk()
            ->assertSee(__('core.academic_years.form.activation_title'))
            ->assertSee('name="is_active"', false)
            ->assertSee('name="enrollment_open"', false)
            ->assertSee(__('core.common.actions.previous'));

        $this->actingAs($admin)
            ->post(route('core.academic-years.create.step3.store'), [
                'is_active' => false,
                'enrollment_open' => true,
            ])
            ->assertRedirect(route('core.academic-years.create.step4'));

        $this->actingAs($admin)
            ->get(route('core.academic-years.create.step4'))
            ->assertOk()
            ->assertSee(__('core.academic_years.form.configuration_title'))
            ->assertSee('name="configure_now"', false)
            ->assertSee(__('core.common.actions.previous'))
            ->assertSee(__('core.academic_years.form.submit_create'));

        $this->actingAs($admin)
            ->from(route('core.academic-years.create.step4'))
            ->post(route('core.academic-years.store'), [
                'configure_now' => true,
                'period_type' => AcademicPeriodType::Trimester->value,
                'school_day_type' => SchoolDayType::Continuous->value,
                'start_time' => '07:30',
                'end_time' => '15:30',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.academic-years.index'));

        $academicYear = AcademicYear::query()->where('code', '2026-2027')->firstOrFail();
        $academicYear->load(['cycles', 'levels', 'series']);

        $this->assertEqualsCanonicalizing(
            [$primaryCycle->id, $secondaryCycle->id],
            $academicYear->cycles->pluck('id')->all()
        );
        $this->assertCount(0, $academicYear->levels);
        $this->assertCount(0, $academicYear->series);

        $configuration = AcademicConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', 'global')
            ->whereNull('cycle_id')
            ->firstOrFail();

        $dayConfiguration = SchoolDayConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', 'global')
            ->whereNull('cycle_id')
            ->firstOrFail();

        $this->assertSame(AcademicPeriodType::Trimester, $configuration->period_type);
        $this->assertSame(3, $configuration->period_count);
        $this->assertSame(SchoolDayType::Continuous, $configuration->school_day_type);
        $this->assertSame(SchoolDayType::Continuous, $dayConfiguration->day_type);
        $this->assertSame('07:30:00', Carbon::parse($dayConfiguration->start_time)->format('H:i:s'));
        $this->assertSame('15:30:00', Carbon::parse($dayConfiguration->end_time)->format('H:i:s'));
    }

    public function test_update_syncs_the_cycle_selection_and_the_show_page_reflects_it(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $preschoolCycle = AcademicCycle::query()->where('code', 'MATERNELLE')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $academicYear = AcademicYear::query()->create([
            'name' => '2024-2025',
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'is_active' => false,
            'enrollment_open' => true,
            'multi_school_enabled' => false,
            'notes' => 'Année académique à synchroniser.',
        ]);

        $academicYear->cycles()->attach([$preschoolCycle->id, $primaryCycle->id]);

        $response = $this->actingAs($admin)
            ->from(route('core.academic-years.edit', $academicYear))
            ->put(route('core.academic-years.update', $academicYear), [
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-31',
                'is_active' => false,
                'enrollment_open' => true,
                'configure_now' => false,
                'cycle_ids' => [$primaryCycle->id, $secondaryCycle->id],
                'notes' => 'Année académique mise à jour.',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('core.academic-years.index'));

        $academicYear->refresh();

        $this->assertEqualsCanonicalizing(
            [$primaryCycle->id, $secondaryCycle->id],
            $academicYear->cycles->pluck('id')->all()
        );

        $this->assertDatabaseMissing('academic_year_cycles', [
            'academic_year_id' => $academicYear->id,
            'academic_cycle_id' => $preschoolCycle->id,
        ]);

        $showResponse = $this->actingAs($admin)->get(route('core.academic-years.show', $academicYear));

        $showResponse
            ->assertOk()
            ->assertSee(__('core.academic_years.show.cycle_management_title'))
            ->assertSee(__('core.academic_years.show.cycle_management_open_page_action'))
            ->assertSee('Primaire')
            ->assertSee('Secondaire');
    }

    private function admin(): User
    {
        return User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
    }
}

<?php

namespace Tests\Feature\Core;

use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicPeriod;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicYearCycleConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cycle_configuration_page_displays_calendar_workflow_actions_for_existing_calendar(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $calendar = AcademicCalendar::query()->create([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $academicYear->id,
            'name' => $academicYear->name.' - '.$cycle->name,
            'description' => 'Calendrier primaire.',
            'scope_type' => 'cycle',
            'cycle_id' => $cycle->id,
            'period_type' => 'trimester',
            'period_count' => 3,
            'is_active' => true,
            'validated_at' => now(),
            'validated_by' => $admin->id,
            'created_by' => $admin->id,
        ]);
        $configuration = AcademicConfiguration::query()->create([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $academicYear->id,
            'scope_type' => 'cycle',
            'cycle_id' => $cycle->id,
            'period_type' => 'trimester',
            'period_count' => 3,
            'school_day_type' => 'continuous',
            'is_active' => true,
            'is_locked' => false,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        AcademicPeriod::query()->create([
            'school_id' => $this->defaultSchool()->id,
            'academic_calendar_id' => $calendar->id,
            'academic_configuration_id' => $configuration->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'start_date' => $academicYear->start_date,
            'end_date' => $academicYear->start_date?->copy()->addMonths(2),
            'is_active' => true,
            'is_locked' => false,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->assertOk()
            ->assertSee(__('core.academic_years.show.cycle_calendar_title'))
            ->assertSee($calendar->name)
            ->assertSee(route('academic.calendars.show', $calendar), false)
            ->assertSee('academic_calendar_id='.$calendar->id, false)
            ->assertSee('group=primary', false)
            ->assertSee('target_type=cycle', false)
            ->assertSee('target_id='.$cycle->id, false)
            ->assertSee('target_cycle_id='.$cycle->id, false);
    }

    public function test_calendar_create_form_prefills_cycle_context_from_year_cycle_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('academic.calendars.create', [
                'academic_year_id' => $academicYear->id,
                'group' => 'primary',
                'scope_type' => 'cycle',
                'cycle_id' => $cycle->id,
                'from_periods' => 1,
            ]))
            ->assertOk()
            ->assertSee($academicYear->name.' - '.$cycle->name)
            ->assertSee('name="from_periods"', false);
    }

    public function test_cycle_configuration_page_can_activate_a_level_and_a_series_for_a_new_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $level = AcademicLevel::query()
            ->where('cycle_id', $cycle->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->firstOrFail();
        $series = AcademicSeries::query()
            ->where('cycle_id', $cycle->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->firstOrFail();

        $academicYear = AcademicYear::query()->create([
            'name' => '2026-2027',
            'code' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_active' => false,
            'enrollment_open' => true,
            'multi_school_enabled' => false,
            'notes' => 'Année de configuration annuelle.',
        ]);
        $academicYear->cycles()->attach($cycle->id);

        $this->assertDatabaseMissing('academic_year_levels', [
            'academic_year_id' => $academicYear->id,
            'academic_level_id' => $level->id,
        ]);
        $this->assertDatabaseMissing('academic_year_series', [
            'academic_year_id' => $academicYear->id,
            'academic_series_id' => $series->id,
        ]);

        $this->actingAs($admin)
            ->get(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->assertOk()
            ->assertSee(__('core.academic_years.show.annual_levels_title'))
            ->assertSee(__('core.academic_years.show.annual_series_title'))
            ->assertSee($level->name)
            ->assertSee($series->name);

        $this->actingAs($admin)
            ->from(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->patch(route('core.academic-years.cycles.levels.toggle-active', [
                'academic_year' => $academicYear,
                'cycle' => $cycle,
                'level' => $level,
            ]))
            ->assertRedirect(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->assertSessionHas('success', __('core.academic_years.messages.level_enabled_for_year'));

        $this->actingAs($admin)
            ->from(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->patch(route('core.academic-years.cycles.series.toggle-active', [
                'academic_year' => $academicYear,
                'cycle' => $cycle,
                'series' => $series,
            ]))
            ->assertRedirect(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->assertSessionHas('success', __('core.academic_years.messages.series_enabled_for_year'));

        $this->assertDatabaseHas('academic_year_levels', [
            'academic_year_id' => $academicYear->id,
            'academic_level_id' => $level->id,
        ]);
        $this->assertDatabaseHas('academic_year_series', [
            'academic_year_id' => $academicYear->id,
            'academic_series_id' => $series->id,
        ]);
    }

    public function test_level_cannot_be_removed_from_year_when_a_class_already_uses_it(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();
        $cycle = AcademicCycle::query()->findOrFail($level->cycle_id);

        $this->assertDatabaseHas('academic_year_levels', [
            'academic_year_id' => $academicYear->id,
            'academic_level_id' => $level->id,
        ]);

        AcademicClassroom::query()->create([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
            'series_id' => null,
            'name' => 'CM2 - TEST',
            'code' => 'CM2-TEST-'.Str::upper(Str::random(4)),
            'capacity' => 40,
            'description' => 'Classe de blocage.',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle]))
            ->patch(route('core.academic-years.cycles.levels.toggle-active', [
                'academic_year' => $academicYear,
                'cycle' => $cycle,
                'level' => $level,
            ]))
            ->assertSessionHasErrors([
                'level' => __('core.academic_years.validation.level_deactivation_blocked'),
            ]);

        $this->assertDatabaseHas('academic_year_levels', [
            'academic_year_id' => $academicYear->id,
            'academic_level_id' => $level->id,
        ]);
    }

    public function test_classroom_creation_rejects_a_level_not_enabled_for_the_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();

        $academicYear->levels()->detach($level->id);

        $this->actingAs($admin)
            ->from(route('academic.classes.create', ['academic_year_id' => $academicYear->id]))
            ->post(route('academic.classes.store'), [
                'academic_year_id' => $academicYear->id,
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'custom_name' => '0',
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors([
                'level_id' => __('academic.classes.validation.level_not_available_for_year'),
            ]);
    }

    public function test_classroom_creation_rejects_a_series_not_enabled_for_the_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $level = AcademicLevel::query()->where('code', 'SECONDE')->firstOrFail();
        $series = $level->series()->where('is_active', true)->orderBy('sort_order')->firstOrFail();

        $academicYear->series()->detach($series->id);

        $this->actingAs($admin)
            ->from(route('academic.classes.create', ['academic_year_id' => $academicYear->id]))
            ->post(route('academic.classes.store'), [
                'academic_year_id' => $academicYear->id,
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'series_id' => $series->id,
                'custom_name' => '0',
                'section_number' => 1,
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors([
                'series_id' => __('academic.classes.validation.series_not_available_for_year'),
            ]);
    }

    private function admin(): User
    {
        return User::query()->where('email', 'admin@ecoles.test')->firstOrFail();
    }

    private function activeYear(): AcademicYear
    {
        return AcademicYear::query()->active()->firstOrFail();
    }

    private function defaultSchool(): School
    {
        return School::query()->where('is_default', true)->firstOrFail();
    }
}

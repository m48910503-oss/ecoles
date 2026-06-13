<?php

namespace Tests\Feature\Academic;

use App\Events\CalendarEventPublished;
use App\Models\AcademicCalendar;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicClassroom;
use App\Models\AcademicCoefficient;
use App\Models\AcademicConductSetting;
use App\Models\AcademicLevel;
use App\Models\AcademicPeriod;
use App\Models\AcademicSeries;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_access_academic_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $role = Role::query()->create([
            'name' => 'Lecture simple',
            'slug' => 'lecture-simple',
            'description' => 'Accès limité.',
            'is_system' => false,
            'is_optional' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->get(route('academic.dashboard'))
            ->assertForbidden();
    }

    public function test_administrator_can_open_academic_module_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('academic.dashboard'))
            ->assertOk()
            ->assertSee('Organisation scolaire');

        $this->actingAs($admin)->get(route('academic.configuration.index'))
            ->assertOk()
            ->assertSee('Année scolaire');

        $this->actingAs($admin)->get(route('academic.cycles.index'))
            ->assertOk()
            ->assertSee('Cycles scolaires')
            ->assertSee(__('academic.cycles.index.description'));

        $this->actingAs($admin)->get(route('academic.levels.index'))
            ->assertOk()
            ->assertSee('Niveaux');

        $this->actingAs($admin)->get(route('academic.series.index'))
            ->assertOk()
            ->assertSee('Séries');

        $this->actingAs($admin)->get(route('academic.classes.index'))
            ->assertOk()
            ->assertSee('Classes');

        $this->actingAs($admin)->get(route('academic.subjects.index'))
            ->assertOk()
            ->assertSee('Matières');

        $this->actingAs($admin)->get(route('academic.coefficients.index'))
            ->assertOk()
            ->assertSee('Coefficients');

        $this->actingAs($admin)->get(route('academic.conduct.index'))
            ->assertOk()
            ->assertSee('Conduite');

        $this->actingAs($admin)->get(route('academic.calendars.periods.index'))
            ->assertOk()
            ->assertSee(__('academic.calendars.periods.title'));

        $this->actingAs($admin)->get(route('academic.calendars.index'))
            ->assertOk()
            ->assertSee('Calendriers');

        $this->actingAs($admin)->get(route('academic.events.index'))
            ->assertOk()
            ->assertSee('Événements');

        $this->actingAs($admin)->get(route('core.academic-years.show', $this->activeYear()))
            ->assertOk()
            ->assertSee($this->activeYear()->name);
    }

    public function test_subject_create_form_shows_governance_toggles_without_code_and_sort_order_fields(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('academic.subjects.create'))
            ->assertOk()
            ->assertDontSee('name="code"', false)
            ->assertDontSee('name="sort_order"', false)
            ->assertSee('name="is_optional_by_default"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('name="is_conduct"', false)
            ->assertSee(__('academic.subjects.form.category_placeholder'))
            ->assertSee(__('academic.subjects.form.details_description'))
            ->assertSee(__('academic.subjects.form.governance_description'));
    }

    public function test_subject_can_be_created_without_manual_code_or_sort_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $firstName = 'Matière pilote '.Str::upper(Str::random(4));
        $secondName = 'Matière pilote '.Str::upper(Str::random(4));
        $firstExpectedCode = Str::upper(Str::substr(Str::slug($firstName, '-'), 0, 80));
        $secondExpectedCode = Str::upper(Str::substr(Str::slug($secondName, '-'), 0, 80));

        $this->actingAs($admin)
            ->from(route('academic.subjects.create'))
            ->post(route('academic.subjects.store'), [
                'name' => $firstName,
                'short_name' => 'MP1',
                'category' => 'language',
                'description' => 'Matière de test',
                'is_conduct' => '0',
            ])
            ->assertRedirect(route('academic.subjects.index'));

        $firstSubject = AcademicSubject::query()->where('name', $firstName)->firstOrFail();

        $this->assertSame($firstExpectedCode, $firstSubject->code);
        $this->assertGreaterThan(0, $firstSubject->sort_order);
        $this->assertFalse($firstSubject->is_optional_by_default);
        $this->assertTrue($firstSubject->is_active);

        $this->actingAs($admin)
            ->from(route('academic.subjects.create'))
            ->post(route('academic.subjects.store'), [
                'name' => $secondName,
                'short_name' => 'MP2',
                'category' => 'science',
                'description' => 'Matière de test',
                'is_conduct' => '0',
            ])
            ->assertRedirect(route('academic.subjects.index'));

        $secondSubject = AcademicSubject::query()->where('name', $secondName)->firstOrFail();

        $this->assertSame($secondExpectedCode, $secondSubject->code);
        $this->assertSame($firstSubject->sort_order + 1, $secondSubject->sort_order);
        $this->assertFalse($secondSubject->is_optional_by_default);
        $this->assertTrue($secondSubject->is_active);
    }

    public function test_subject_can_set_optional_and_active_flags(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $name = 'Matière optionnelle '.Str::upper(Str::random(4));

        $this->actingAs($admin)
            ->from(route('academic.subjects.create'))
            ->post(route('academic.subjects.store'), [
                'name' => $name,
                'short_name' => 'MOP',
                'category' => 'language',
                'description' => 'Matière de test',
                'is_optional_by_default' => '1',
                'is_conduct' => '0',
                'is_active' => '0',
            ])
            ->assertRedirect(route('academic.subjects.index'));

        $subject = AcademicSubject::query()->where('name', $name)->firstOrFail();

        $this->assertTrue($subject->is_optional_by_default);
        $this->assertFalse($subject->is_active);
    }

    public function test_subject_can_be_created_without_category(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $name = 'Matière sans catégorie '.Str::upper(Str::random(4));

        $this->actingAs($admin)
            ->from(route('academic.subjects.create'))
            ->post(route('academic.subjects.store'), [
                'name' => $name,
                'short_name' => 'MSC',
                'description' => 'Matière de test',
                'is_conduct' => '0',
            ])
            ->assertRedirect(route('academic.subjects.index'));

        $subject = AcademicSubject::query()->where('name', $name)->firstOrFail();

        $this->assertNull($subject->category);
    }

    public function test_only_one_subject_can_be_marked_as_conduct_per_school(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        AcademicSubject::query()->where('is_conduct', true)->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.subjects.create'))
            ->post(route('academic.subjects.store'), [
                'name' => 'Nouvelle matière conduite',
                'short_name' => 'NMC',
                'category' => 'life_skills',
                'description' => 'Matière de test',
                'is_conduct' => '1',
            ])
            ->assertSessionHasErrors('is_conduct');
    }

    public function test_duplicate_academic_configuration_scope_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $year = $this->activeYear();

        $payload = $this->configurationFormPayload($year);

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), $payload)
            ->assertRedirect(route('academic.configuration.index'));

        $this->actingAs($admin)
            ->from(route('academic.configuration.create'))
            ->post(route('academic.configuration.store'), $payload)
            ->assertSessionHasErrors('scope_type');
    }

    public function test_level_cannot_use_series_outside_upper_secondary_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.levels.create'))
            ->post(route('academic.levels.store'), [
                'cycle_id' => $primaryCycle->id,
                'name' => 'Niveau pilote',
                'code' => 'NIV-PILOTE',
                'short_name' => 'NP',
                'sort_order' => 99,
                'uses_series' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('uses_series');
    }

    public function test_level_create_form_shows_series_selection_without_code_or_order_fields(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('academic.levels.create'))
            ->assertOk()
            ->assertDontSee('name="code"')
            ->assertDontSee('name="sort_order"')
            ->assertSee('name="series_ids[]"', false)
            ->assertSee(__('academic.levels.form.series_title'));
    }

    public function test_level_can_be_created_with_applicable_series(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $seriesIds = AcademicSeries::query()
            ->where('cycle_id', $cycle->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('id')
            ->take(2)
            ->all();

        $code = 'NIV-SERIE-'.Str::upper(Str::random(4));

        $this->actingAs($admin)
            ->from(route('academic.levels.create'))
            ->post(route('academic.levels.store'), [
                'cycle_id' => $cycle->id,
                'name' => 'Niveau séries',
                'code' => $code,
                'short_name' => 'NS',
                'description' => 'Niveau de test',
                'sort_order' => 42,
                'uses_series' => '1',
                'series_ids' => $seriesIds,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.levels.index'));

        $level = AcademicLevel::query()->where('code', $code)->firstOrFail()->load('series');

        $this->assertEqualsCanonicalizing($seriesIds, $level->series->pluck('id')->all());
    }

    public function test_level_requires_at_least_one_series_when_using_series(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.levels.create'))
            ->post(route('academic.levels.store'), [
                'cycle_id' => $cycle->id,
                'name' => 'Niveau sans série',
                'code' => 'NIV-SANS-SERIE-'.Str::upper(Str::random(4)),
                'short_name' => 'NSS',
                'description' => 'Niveau de test',
                'sort_order' => 43,
                'uses_series' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('series_ids');
    }

    public function test_level_rejects_series_from_another_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $otherCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $wrongSeries = AcademicSeries::query()->create([
            'cycle_id' => $otherCycle->id,
            'name' => 'Série hors cycle',
            'code' => 'SERIE-HORS-CYCLE-'.Str::upper(Str::random(4)),
            'short_name' => 'HC',
            'description' => 'Série de test',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.levels.create'))
            ->post(route('academic.levels.store'), [
                'cycle_id' => $cycle->id,
                'name' => 'Niveau incompatible',
                'code' => 'NIV-INCOMPATIBLE-'.Str::upper(Str::random(4)),
                'short_name' => 'NI',
                'description' => 'Niveau de test',
                'sort_order' => 44,
                'uses_series' => '1',
                'series_ids' => [$wrongSeries->id],
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('series_ids');
    }

    public function test_level_update_is_blocked_when_removing_series_used_by_classrooms(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $level = AcademicLevel::query()->where('code', 'TERMINALE')->firstOrFail();
        $series = AcademicSeries::query()
            ->where('cycle_id', $cycle->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $linkedSeries = $series->firstOrFail();
        $remainingSeriesIds = $series->pluck('id')->skip(1)->values()->all();

        AcademicClassroom::query()->create([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $this->activeYear()->id,
            'level_id' => $level->id,
            'series_id' => $linkedSeries->id,
            'name' => 'Terminale bloquée',
            'code' => 'TERM-BLOQUEE-'.Str::upper(Str::random(4)),
            'capacity' => 40,
            'description' => 'Classe de test',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.levels.edit', $level))
            ->put(route('academic.levels.update', $level), [
                'cycle_id' => $cycle->id,
                'name' => $level->name,
                'code' => $level->code,
                'short_name' => $level->short_name,
                'description' => $level->description,
                'sort_order' => $level->sort_order,
                'uses_series' => '1',
                'series_ids' => $remainingSeriesIds,
                'is_active' => $level->is_active ? '1' : '0',
            ])
            ->assertSessionHasErrors('series_ids');
    }

    public function test_series_requires_upper_secondary_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.series.create'))
            ->post(route('academic.series.store'), [
                'cycle_id' => $primaryCycle->id,
                'name' => 'Série pilote',
                'short_name' => 'SP',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('cycle_id');

        $this->assertDatabaseMissing('academic_series', [
            'name' => 'Série pilote',
            'cycle_id' => $primaryCycle->id,
        ]);
    }

    public function test_series_is_created_without_manual_code_or_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $name = 'Série pilote';
        $expectedCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));

        $this->actingAs($admin)
            ->get(route('academic.series.create'))
            ->assertOk()
            ->assertSee(__('academic.series.form.description'))
            ->assertDontSee('name="code"')
            ->assertDontSee('name="sort_order"');

        $this->actingAs($admin)
            ->from(route('academic.series.create'))
            ->post(route('academic.series.store'), [
                'cycle_id' => $cycle->id,
                'name' => $name,
                'short_name' => 'SP',
                'description' => 'Série de test',
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.series.index'));

        $series = AcademicSeries::query()->where('name', $name)->firstOrFail();

        $this->assertSame($expectedCode, $series->code);
        $this->assertGreaterThan(0, $series->sort_order);
    }

    public function test_series_delete_button_is_visible_and_unused_series_can_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $series = $this->createSeries();

        $this->actingAs($admin)
            ->get(route('academic.series.index'))
            ->assertOk()
            ->assertSee(__('core.common.actions.delete'));

        $this->actingAs($admin)
            ->from(route('academic.series.index'))
            ->delete(route('academic.series.destroy', $series))
            ->assertRedirect(route('academic.series.index'))
            ->assertSessionHas('success', __('academic.series.messages.deleted'));

        $this->assertDatabaseMissing('academic_series', ['id' => $series->id]);
    }

    public function test_series_delete_is_blocked_when_dependencies_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'TERMINALE')->firstOrFail();
        $series = $this->createSeries();

        $this->createClassroom([
            'level_id' => $level->id,
            'series_id' => $series->id,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.series.show', $series))
            ->delete(route('academic.series.destroy', $series))
            ->assertSessionHasErrors('series');

        $this->assertDatabaseHas('academic_series', ['id' => $series->id]);
    }

    public function test_level_delete_is_blocked_when_dependencies_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();

        $this->createClassroom([
            'level_id' => $level->id,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.levels.show', $level))
            ->delete(route('academic.levels.destroy', $level))
            ->assertSessionHasErrors('level');

        $this->assertDatabaseHas('academic_levels', ['id' => $level->id]);
    }

    public function test_unused_level_can_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $level = AcademicLevel::query()->create([
            'cycle_id' => $cycle->id,
            'name' => 'Niveau jetable',
            'code' => 'NIV-JETABLE',
            'short_name' => 'NJ',
            'description' => null,
            'sort_order' => 99,
            'uses_series' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.levels.show', $level))
            ->delete(route('academic.levels.destroy', $level))
            ->assertRedirect(route('academic.levels.index'))
            ->assertSessionHas('success', __('academic.levels.messages.deleted'));

        $this->assertDatabaseMissing('academic_levels', ['id' => $level->id]);
    }

    public function test_classroom_delete_is_blocked_when_calendars_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $classroom = $this->createClassroom();

        $this->createCalendar([
            'scope_type' => 'class',
            'class_id' => $classroom->id,
            'name' => 'Calendrier '.$classroom->name,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.classes.show', $classroom))
            ->delete(route('academic.classes.destroy', $classroom))
            ->assertSessionHasErrors('classroom');

        $this->assertDatabaseHas('academic_classrooms', ['id' => $classroom->id]);
    }

    public function test_unused_classroom_can_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $classroom = $this->createClassroom();

        $this->actingAs($admin)
            ->from(route('academic.classes.show', $classroom))
            ->delete(route('academic.classes.destroy', $classroom))
            ->assertRedirect(route('academic.classes.index'))
            ->assertSessionHas('success', __('academic.classes.messages.deleted'));

        $this->assertDatabaseMissing('academic_classrooms', ['id' => $classroom->id]);
    }

    public function test_cycle_cannot_be_deactivated_while_active_levels_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.cycles.show', $cycle))
            ->patch(route('academic.cycles.toggle-active', $cycle))
            ->assertSessionHasErrors('cycle');
    }

    public function test_cycle_is_created_without_manual_code_and_gets_an_internal_identifier(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $name = 'Cycle pilote';
        $expectedCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));

        $this->actingAs($admin)
            ->get(route('academic.cycles.create'))
            ->assertOk()
            ->assertSee(__('academic.cycles.form.description'))
            ->assertDontSee('Code du cycle')
            ->assertDontSee('name="category"')
            ->assertDontSee('name="sort_order"');

        $this->actingAs($admin)
            ->from(route('academic.cycles.create'))
            ->post(route('academic.cycles.store'), [
                'name' => $name,
                'description' => 'Cycle de test',
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.cycles.index'));

        $cycle = AcademicCycle::query()->where('name', $name)->firstOrFail();

        $this->assertSame($expectedCode, $cycle->code);
        $this->assertNotNull($cycle->category);
        $this->assertGreaterThan(0, $cycle->sort_order);
    }

    public function test_cycle_show_page_highlights_the_reference_chain(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('academic.cycles.show', $cycle))
            ->assertOk()
            ->assertSee(__('academic.cycles.show.summary_title'))
            ->assertSee(__('academic.common.reference_id'))
            ->assertSee(__('academic.common.status'));
    }

    public function test_cycle_delete_button_is_visible_and_unused_cycle_can_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $name = 'Cycle à supprimer';

        $this->actingAs($admin)
            ->get(route('academic.cycles.index'))
            ->assertOk()
            ->assertSee(__('core.common.actions.delete'));

        $this->actingAs($admin)
            ->from(route('academic.cycles.create'))
            ->post(route('academic.cycles.store'), [
                'name' => $name,
                'description' => 'Cycle temporaire',
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.cycles.index'));

        $cycle = AcademicCycle::query()->where('name', $name)->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.cycles.index'))
            ->delete(route('academic.cycles.destroy', $cycle))
            ->assertRedirect(route('academic.cycles.index'))
            ->assertSessionHas('success', __('academic.cycles.messages.deleted'));

        $this->assertDatabaseMissing('academic_cycles', ['id' => $cycle->id]);
    }

    public function test_cycle_delete_is_blocked_when_dependencies_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.cycles.show', $cycle))
            ->delete(route('academic.cycles.destroy', $cycle))
            ->assertSessionHasErrors('cycle');

        $this->assertDatabaseHas('academic_cycles', ['id' => $cycle->id]);
    }

    public function test_level_scope_calendar_accepts_existing_level(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('academic.calendars.store'), [
                'academic_year_id' => $this->activeYear()->id,
                'name' => 'Calendrier niveau CM2',
                'description' => 'Calendrier de niveau',
                'scope_type' => 'level',
                'level_id' => $level->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.calendars.index'));

        $this->assertDatabaseHas('academic_calendars', [
            'name' => 'Calendrier niveau CM2',
            'level_id' => $level->id,
        ]);
    }

    public function test_level_scope_calendar_rejects_level_not_enabled_for_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();
        $academicYear = AcademicYear::query()->create([
            'name' => '2027-2028',
            'code' => 'AY-TEST-'.Str::upper(Str::random(6)),
            'start_date' => '2027-09-01',
            'end_date' => '2028-06-30',
            'is_active' => false,
            'enrollment_open' => false,
            'multi_school_enabled' => false,
        ]);

        $academicYear->cycles()->attach($level->cycle_id);

        $this->actingAs($admin)
            ->from(route('academic.calendars.create'))
            ->post(route('academic.calendars.store'), [
                'academic_year_id' => $academicYear->id,
                'name' => 'Calendrier niveau hors activation',
                'description' => 'Calendrier de niveau',
                'scope_type' => 'level',
                'level_id' => $level->id,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('level_id');
    }

    public function test_classroom_create_form_shows_naming_controls_without_year_or_code_fields(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('academic.classes.create'))
            ->assertOk()
            ->assertSee('name="academic_year_id"', false)
            ->assertDontSee('name="code"', false)
            ->assertSee('name="cycle_id"', false)
            ->assertSee('name="level_id"', false)
            ->assertSee('name="series_id"', false)
            ->assertSee('name="custom_name"', false)
            ->assertDontSee('name="section_letter"', false)
            ->assertSee('name="section_number"', false)
            ->assertSee(__('academic.classes.form.level_cycle_prompt'))
            ->assertSee(__('academic.classes.form.custom_name_label'))
            ->assertSee(__('academic.classes.form.section_auto_hint'))
            ->assertSee(__('academic.classes.form.generated_name_label'));
    }

    public function test_classroom_requires_series_when_level_uses_series(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'TERMINALE')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'custom_name' => '0',
                'section_number' => 1,
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('series_id');
    }

    public function test_classroom_rejects_series_not_linked_to_the_selected_level(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'TERMINALE')->firstOrFail();
        $otherSeries = AcademicSeries::query()->create([
            'cycle_id' => $level->cycle_id,
            'name' => 'Série hors niveau',
            'code' => 'SERIE-HORS-NIVEAU-'.Str::upper(Str::random(4)),
            'short_name' => 'HN',
            'description' => 'Série volontairement non rattachée au niveau.',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'series_id' => $otherSeries->id,
                'custom_name' => '0',
                'section_number' => 1,
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('series_id');
    }

    public function test_classroom_rejects_level_outside_selected_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $cycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $level = AcademicLevel::query()->where('code', 'TERMINALE')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $cycle->id,
                'level_id' => $level->id,
                'custom_name' => '0',
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('level_id');
    }

    public function test_classroom_can_be_created_with_automatic_letter_name(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();
        $firstExpectedName = 'CM2';
        $firstExpectedCode = Str::upper(Str::substr(Str::slug($firstExpectedName, '-'), 0, 80));
        $secondExpectedName = 'CM2 - B';
        $secondExpectedCode = Str::upper(Str::substr(Str::slug($secondExpectedName, '-'), 0, 80));

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'custom_name' => '0',
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.classes.index'));

        $firstClassroom = AcademicClassroom::query()->where('name', $firstExpectedName)->firstOrFail();

        $this->assertSame($this->activeYear()->id, $firstClassroom->academic_year_id);
        $this->assertSame($firstExpectedCode, $firstClassroom->code);

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'custom_name' => '0',
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.classes.index'));

        $secondClassroom = AcademicClassroom::query()->where('name', $secondExpectedName)->firstOrFail();

        $this->assertSame($this->activeYear()->id, $secondClassroom->academic_year_id);
        $this->assertSame($secondExpectedCode, $secondClassroom->code);
    }

    public function test_classroom_can_be_created_with_automatic_number_name(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'SECONDE')->firstOrFail();
        $series = AcademicSeries::query()->where('code', 'SERIE-D')->firstOrFail();
        $firstExpectedName = '2nde D';
        $firstExpectedCode = Str::upper(Str::substr(Str::slug($firstExpectedName, '-'), 0, 80));
        $secondExpectedName = '2nde D2';
        $secondExpectedCode = Str::upper(Str::substr(Str::slug($secondExpectedName, '-'), 0, 80));

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'series_id' => $series->id,
                'custom_name' => '0',
                'section_number' => 1,
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.classes.index'));

        $firstClassroom = AcademicClassroom::query()->where('name', $firstExpectedName)->firstOrFail();

        $this->assertSame($this->activeYear()->id, $firstClassroom->academic_year_id);
        $this->assertSame($firstExpectedCode, $firstClassroom->code);

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'series_id' => $series->id,
                'custom_name' => '0',
                'section_number' => 1,
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.classes.index'));

        $secondClassroom = AcademicClassroom::query()->where('name', $secondExpectedName)->firstOrFail();

        $this->assertSame($this->activeYear()->id, $secondClassroom->academic_year_id);
        $this->assertSame($secondExpectedCode, $secondClassroom->code);
    }

    public function test_classroom_can_use_a_custom_name(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();
        $name = 'Classe projet';
        $expectedCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));

        $this->actingAs($admin)
            ->from(route('academic.classes.create'))
            ->post(route('academic.classes.store'), [
                'cycle_id' => $level->cycle_id,
                'level_id' => $level->id,
                'custom_name' => '1',
                'name' => $name,
                'capacity' => 40,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.classes.index'));

        $classroom = AcademicClassroom::query()->where('name', $name)->firstOrFail();

        $this->assertSame($this->activeYear()->id, $classroom->academic_year_id);
        $this->assertSame($expectedCode, $classroom->code);
    }

    public function test_class_scope_calendar_accepts_existing_classroom(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $classroom = $this->createClassroom();

        $this->actingAs($admin)
            ->post(route('academic.calendars.store'), [
                'academic_year_id' => $classroom->academic_year_id,
                'name' => 'Calendrier '.$classroom->name,
                'description' => 'Calendrier classe',
                'scope_type' => 'class',
                'class_id' => $classroom->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic.calendars.index'));

        $this->assertDatabaseHas('academic_calendars', [
            'name' => 'Calendrier '.$classroom->name,
            'class_id' => $classroom->id,
        ]);
    }

    public function test_creating_academic_configuration_auto_generates_periods_and_a_global_calendar(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $preschoolCycle = AcademicCycle::query()->where('code', 'MATERNELLE')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), $this->configurationFormPayload($academicYear))
            ->assertRedirect(route('academic.configuration.index'));

        $configuration = AcademicConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('school_id', $this->defaultSchool()->id)
            ->firstOrFail();

        $periods = AcademicPeriod::query()
            ->where('academic_configuration_id', $configuration->id)
            ->orderBy('position')
            ->get();

        $this->assertCount(3, $periods);
        $this->assertSame('Trimestre 1', $periods->first()->name);

        $calendar = AcademicCalendar::query()
            ->where('school_id', $this->defaultSchool()->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', 'global')
            ->firstOrFail();

        $this->assertSame($academicYear->name, $calendar->name);
        $this->assertNull($calendar->validated_at);
    }

    public function test_cycle_group_configuration_generates_a_shared_calendar_for_selected_cycles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $preschoolCycle = AcademicCycle::query()->where('code', 'MATERNELLE')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $payload = array_merge($this->configurationFormPayload($academicYear), [
            'scope_type' => 'cycle',
            'cycle_ids' => [$preschoolCycle->id, $primaryCycle->id],
        ]);

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), $payload)
            ->assertRedirect(route('academic.configuration.index'));

        $configuration = AcademicConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('school_id', $this->defaultSchool()->id)
            ->where('scope_type', 'cycle')
            ->latest('id')
            ->firstOrFail()
            ->load('coveredCycles');

        $this->assertNull($configuration->cycle_id);
        $this->assertSame(
            'cycle-group:'.collect([$preschoolCycle->id, $primaryCycle->id])->sort()->implode('-'),
            $configuration->scope_signature
        );
        $this->assertEqualsCanonicalizing(
            [$preschoolCycle->id, $primaryCycle->id],
            $configuration->coveredCycles->pluck('id')->all()
        );

        $calendar = AcademicCalendar::query()
            ->where('academic_configuration_id', $configuration->id)
            ->firstOrFail()
            ->load('coveredCycles');

        $this->assertSame('cycle', $calendar->scope_type->value);
        $this->assertNull($calendar->cycle_id);
        $this->assertEqualsCanonicalizing(
            [$preschoolCycle->id, $primaryCycle->id],
            $calendar->coveredCycles->pluck('id')->all()
        );
        $this->assertStringContainsString($preschoolCycle->name, $calendar->name);
        $this->assertStringContainsString($primaryCycle->name, $calendar->name);
    }

    public function test_cycle_group_configuration_rejects_overlapping_cycles_for_the_same_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $preschoolCycle = AcademicCycle::query()->where('code', 'MATERNELLE')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), array_merge($this->configurationFormPayload($academicYear), [
                'scope_type' => 'cycle',
                'cycle_ids' => [$preschoolCycle->id, $primaryCycle->id],
            ]))
            ->assertRedirect(route('academic.configuration.index'));

        $this->actingAs($admin)
            ->from(route('academic.configuration.create'))
            ->post(route('academic.configuration.store'), array_merge($this->configurationFormPayload($academicYear), [
                'scope_type' => 'cycle',
                'cycle_ids' => [$primaryCycle->id, $secondaryCycle->id],
            ]))
            ->assertSessionHasErrors('cycle_ids');
    }

    public function test_cycle_group_calendar_event_rejects_target_cycle_outside_the_group(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $preschoolCycle = AcademicCycle::query()->where('code', 'MATERNELLE')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), array_merge($this->configurationFormPayload($academicYear), [
                'scope_type' => 'cycle',
                'cycle_ids' => [$preschoolCycle->id, $primaryCycle->id],
            ]))
            ->assertRedirect(route('academic.configuration.index'));

        $calendar = AcademicCalendar::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', 'cycle')
            ->whereNotNull('academic_configuration_id')
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.events.create'))
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Réunion hors groupe',
                'event_type' => 'reunion',
                'start_datetime' => '2025-10-05 15:00:00',
                'end_datetime' => '2025-10-05 17:00:00',
                'location' => 'Salle des professeurs',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'cycle',
                'target_id' => $secondaryCycle->id,
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertSessionHasErrors('target_id');
    }

    public function test_updating_periods_validates_the_auto_generated_school_calendar_and_updates_the_list(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $schoolId = $this->defaultSchool()->id;

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), $this->configurationFormPayload($academicYear))
            ->assertRedirect(route('academic.configuration.index'));

        $configuration = AcademicConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $periods = AcademicPeriod::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('school_id', $schoolId)
            ->orderBy('position')
            ->get();

        $this->assertCount(3, $periods);

        $payload = $periods->map(static function (AcademicPeriod $period): array {
            return [
                'start_date' => $period->start_date->copy()->addDay()->format('Y-m-d'),
                'end_date' => $period->end_date->copy()->subDay()->format('Y-m-d'),
            ];
        })->values()->all();

        $this->actingAs($admin)
            ->put(route('academic.calendars.periods.update', $academicYear), [
                'school_id' => $schoolId,
                'periods' => $payload,
            ])
            ->assertRedirect(route('academic.calendars.periods.index', [
                'academic_year_id' => $academicYear->id,
                'school_id' => $schoolId,
            ]));

        $calendar = AcademicCalendar::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', 'global')
            ->firstOrFail();

        $this->assertNotNull($calendar->validated_at);
        $this->assertSame($admin->id, $calendar->validated_by);
        $this->assertSame($academicYear->name, $calendar->name);

        $this->actingAs($admin)
            ->get(route('academic.calendars.index'))
            ->assertOk()
            ->assertSee(__('academic.calendars.index.validated'))
            ->assertSee($calendar->name);
    }

    public function test_academic_year_hub_displays_configuration_and_summary(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $preschoolCycle = AcademicCycle::query()->where('code', 'MATERNELLE')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('academic.configuration.store'), $this->configurationFormPayload($academicYear))
            ->assertRedirect(route('academic.configuration.index'));

        $configuration = AcademicConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('school_id', $this->defaultSchool()->id)
            ->firstOrFail();

        $periods = AcademicPeriod::query()
            ->where('academic_configuration_id', $configuration->id)
            ->orderBy('position')
            ->get();

        $calendar = AcademicCalendar::query()
            ->where('school_id', $this->defaultSchool()->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', 'global')
            ->with('creator')
            ->firstOrFail();

        $response = $this->actingAs($admin)->get(route('core.academic-years.show', $academicYear));

        $response
            ->assertOk()
            ->assertSee($academicYear->name)
            ->assertSee($this->defaultSchool()->name)
            ->assertSee(__('core.academic_years.show.overview_title'))
            ->assertSee(__('core.academic_years.show.year_status_label'))
            ->assertSee(__('core.academic_years.show.progress_title'))
            ->assertSee(__('core.academic_years.show.calendar_status_label'))
            ->assertSee(__('core.academic_years.show.journey_title'))
            ->assertSee(__('core.academic_years.show.next_step_label'))
            ->assertSee(__('core.academic_years.show.cycle_management_title'))
            ->assertSee(__('academic.shared.cycle_groups.preschool'))
            ->assertSee(__('academic.shared.cycle_groups.primary'))
            ->assertSee(__('academic.shared.cycle_groups.secondary'))
            ->assertSee(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $preschoolCycle]), false)
            ->assertSee(route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $primaryCycle]), false)
            ->assertSee(route('core.academic-years.sectors.show', ['academic_year' => $academicYear, 'group' => 'secondary']), false)
            ->assertSee(__('core.academic_years.show.cycle_management_open_page_action'))
            ->assertSee(__('core.common.actions.delete'))
            ->assertSee(__('core.common.feedback.warning_title'))
            ->assertSee(__('core.academic_years.delete_active_warning'))
            ->assertDontSee(__('core.academic_years.show.calendar_title'))
            ->assertDontSee(__('core.academic_years.show.periods_title'))
            ->assertDontSee($periods->first()->name);
    }

    public function test_academic_year_sector_page_displays_sector_calendars_and_cycles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $this->createCalendar([
            'name' => 'Calendrier secondaire de test',
            'scope_type' => 'cycle',
            'cycle_id' => $secondaryCycle->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->actingAs($admin)->get(route('core.academic-years.sectors.show', [
            'academic_year' => $academicYear,
            'group' => 'secondary',
        ]));

        $response
            ->assertOk()
            ->assertSee(__('core.academic_years.show.sector_actions_title'))
            ->assertSee(__('core.academic_years.show.sector_actions_create_calendar_title'))
            ->assertSee(__('core.academic_years.show.sector_actions_manage_periods_title'))
            ->assertSee(__('core.academic_years.show.sector_actions_create_event_title'))
            ->assertSee(__('core.academic_years.show.sector_actions_manage_cycles_title'))
            ->assertSee(e(route('academic.calendars.create', [
                'academic_year_id' => $academicYear->id,
                'group' => 'secondary',
                'scope_type' => 'cycle',
            ])), false)
            ->assertSee(e(route('academic.calendars.periods.index', [
                'academic_year_id' => $academicYear->id,
                'group' => 'secondary',
            ])), false)
            ->assertSee(e(route('academic.events.create', [
                'academic_year_id' => $academicYear->id,
                'group' => 'secondary',
            ])), false)
            ->assertSee(__('academic.shared.cycle_groups.secondary'))
            ->assertSee(__('core.academic_years.show.cycle_management_cycles_label'))
            ->assertSee(__('core.academic_years.show.cycle_management_calendars_label'))
            ->assertSee(__('core.academic_years.show.cycle_management_manage_action'))
            ->assertSee(__('academic.cycles.show.levels'))
            ->assertSee('Secondaire premier cycle')
            ->assertSee('Secondaire second cycle')
            ->assertSee('Calendrier secondaire de test')
            ->assertSee(route('academic.cycles.index', ['group' => 'secondary']), false);
    }

    public function test_academic_calendar_create_form_filters_cycles_by_sector_group(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('academic.calendars.create', [
            'group' => 'secondary',
            'scope_type' => 'cycle',
        ]));

        $response
            ->assertOk()
            ->assertSee(__('academic.calendars.form.group_context', ['group' => __('academic.shared.cycle_groups.secondary')]))
            ->assertSee('Secondaire premier cycle')
            ->assertSee('Secondaire second cycle')
            ->assertDontSee('MATERNELLE')
            ->assertDontSee('PRIMAIRE');
    }

    public function test_academic_event_create_form_filters_calendars_by_sector_group(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->createCalendar([
            'name' => 'Calendrier primaire de test',
            'scope_type' => 'cycle',
            'cycle_id' => $primaryCycle->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->createCalendar([
            'name' => 'Calendrier secondaire de test',
            'scope_type' => 'cycle',
            'cycle_id' => $secondaryCycle->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->actingAs($admin)->get(route('academic.events.create', [
            'group' => 'secondary',
            'academic_year_id' => $academicYear->id,
        ]));

        $response
            ->assertOk()
            ->assertSee(__('academic.events.form.group_context', ['group' => __('academic.shared.cycle_groups.secondary')]))
            ->assertSee('Calendrier secondaire de test')
            ->assertDontSee('Calendrier primaire de test');
    }

    public function test_academic_calendar_periods_page_filters_calendars_by_sector_group(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $academicYear = $this->activeYear();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();

        $this->createCalendar([
            'name' => 'Calendrier primaire de test',
            'scope_type' => 'cycle',
            'cycle_id' => $primaryCycle->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->createCalendar([
            'name' => 'Calendrier secondaire de test',
            'scope_type' => 'cycle',
            'cycle_id' => $secondaryCycle->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->actingAs($admin)->get(route('academic.calendars.periods.index', [
            'academic_year_id' => $academicYear->id,
            'group' => 'secondary',
        ]));

        $response
            ->assertOk()
            ->assertSee('Calendrier secondaire de test')
            ->assertDontSee('Calendrier primaire de test');
    }

    public function test_cycle_index_can_be_filtered_by_management_group(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('academic.cycles.index', ['group' => 'secondary']))
            ->assertOk()
            ->assertSee(__('academic.cycles.index.group_label'))
            ->assertSee(__('academic.shared.cycle_groups.secondary'))
            ->assertSee('SECONDAIRE-1')
            ->assertSee('SECONDAIRE-2')
            ->assertDontSee('MATERNELLE')
            ->assertDontSee('PRIMAIRE');
    }

    public function test_class_target_event_accepts_existing_classroom(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $classroom = $this->createClassroom();
        $calendar = $this->createCalendar([
            'academic_year_id' => $classroom->academic_year_id,
            'scope_type' => 'class',
            'class_id' => $classroom->id,
        ]);

        $this->actingAs($admin)
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Rattrapage ciblé',
                'event_type' => 'rattrapage',
                'start_datetime' => '2025-10-05 15:00:00',
                'end_datetime' => '2025-10-05 17:00:00',
                'location' => 'Salle 3A',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'class',
                'target_id' => $classroom->id,
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertRedirect(route('academic.events.index'));

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'Rattrapage ciblé',
            'target_type' => 'class',
            'target_id' => $classroom->id,
        ]);
    }

    public function test_cycle_target_event_rejects_cycle_not_enabled_for_calendar_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $primaryCycle = AcademicCycle::query()->where('code', 'PRIMAIRE')->firstOrFail();
        $secondaryCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();
        $academicYear = AcademicYear::query()->create([
            'name' => '2028-2029',
            'code' => 'AY-EVT-'.Str::upper(Str::random(6)),
            'start_date' => '2028-09-01',
            'end_date' => '2029-06-30',
            'is_active' => false,
            'enrollment_open' => false,
            'multi_school_enabled' => false,
        ]);

        $academicYear->cycles()->attach($primaryCycle->id);

        $calendar = $this->createCalendar([
            'academic_year_id' => $academicYear->id,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.events.create'))
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Événement cycle hors année',
                'event_type' => 'reunion',
                'start_datetime' => '2028-10-05 15:00:00',
                'end_datetime' => '2028-10-05 17:00:00',
                'location' => 'Salle des professeurs',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'cycle',
                'target_id' => $secondaryCycle->id,
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertSessionHasErrors('target_id');
    }

    public function test_level_target_event_rejects_level_not_enabled_for_calendar_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();
        $academicYear = AcademicYear::query()->create([
            'name' => '2029-2030',
            'code' => 'AY-LVL-'.Str::upper(Str::random(6)),
            'start_date' => '2029-09-01',
            'end_date' => '2030-06-30',
            'is_active' => false,
            'enrollment_open' => false,
            'multi_school_enabled' => false,
        ]);

        $academicYear->cycles()->attach($level->cycle_id);

        $calendar = $this->createCalendar([
            'academic_year_id' => $academicYear->id,
        ]);

        $this->actingAs($admin)
            ->from(route('academic.events.create'))
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Événement niveau hors année',
                'event_type' => 'reunion',
                'start_datetime' => '2029-10-05 15:00:00',
                'end_datetime' => '2029-10-05 17:00:00',
                'location' => 'Salle des professeurs',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'level',
                'target_id' => $level->id,
                'target_cycle_id' => $level->cycle_id,
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertSessionHasErrors('target_id');
    }

    public function test_full_day_event_accepts_single_date_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $calendar = $this->createCalendar();

        $this->actingAs($admin)
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Journée de congé',
                'event_type' => 'conge',
                'event_date' => '2025-10-05',
                'is_full_day' => '1',
                'visibility' => 'public',
                'target_type' => 'global',
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertRedirect(route('academic.events.index'));

        $event = CalendarEvent::query()->where('title', 'Journée de congé')->firstOrFail();

        $this->assertTrue($event->is_full_day);
        $this->assertSame('2025-10-05', $event->start_datetime->toDateString());
        $this->assertSame('2025-10-05', $event->end_datetime->toDateString());
        $this->assertNull($event->location);
    }

    public function test_reunion_event_requires_location(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $calendar = $this->createCalendar();

        $this->actingAs($admin)
            ->from(route('academic.events.create'))
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Réunion sans lieu',
                'event_type' => 'reunion',
                'start_datetime' => '2025-10-05 15:00:00',
                'end_datetime' => '2025-10-05 17:00:00',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'global',
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertSessionHasErrors('location');
    }

    public function test_conduct_setting_requires_conduct_subject(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $subject = AcademicSubject::query()->where('is_conduct', false)->firstOrFail();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.conduct.create'))
            ->post(route('academic.conduct.store'), [
                'academic_year_id' => $this->activeYear()->id,
                'level_id' => $level->id,
                'academic_subject_id' => $subject->id,
                'base_score' => 20,
                'deductions_from_sanctions_only' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('academic_subject_id');
    }

    public function test_coefficient_series_must_match_level_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'TERMINALE')->firstOrFail();
        $wrongCycle = AcademicCycle::query()->where('code', 'SECONDAIRE-1')->firstOrFail();
        $wrongSeries = AcademicSeries::query()->create([
            'cycle_id' => $wrongCycle->id,
            'name' => 'Série test incompatible',
            'code' => 'SERIE-TEST-INCOMPATIBLE',
            'short_name' => 'STI',
            'description' => 'Série de test volontairement rattachée au mauvais cycle.',
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $subject = AcademicSubject::query()->where('code', 'MATH')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('academic.coefficients.create'))
            ->post(route('academic.coefficients.store'), [
                'academic_year_id' => $this->activeYear()->id,
                'level_id' => $level->id,
                'series_id' => $wrongSeries->id,
                'academic_subject_id' => $subject->id,
                'coefficient' => 4,
                'is_optional' => '0',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('series_id');
    }

    public function test_coefficient_duplicate_assignment_is_blocked(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();
        $subject = AcademicSubject::query()->where('code', 'MATH')->firstOrFail();
        $payload = [
            'academic_year_id' => $this->activeYear()->id,
            'level_id' => $level->id,
            'academic_subject_id' => $subject->id,
            'coefficient' => 4,
            'is_optional' => '0',
            'is_active' => '1',
        ];

        $this->actingAs($admin)
            ->post(route('academic.coefficients.store'), $payload)
            ->assertRedirect(route('academic.coefficients.index'));

        $this->actingAs($admin)
            ->from(route('academic.coefficients.create'))
            ->post(route('academic.coefficients.store'), $payload)
            ->assertSessionHasErrors('academic_subject_id');
    }

    public function test_academic_periods_cannot_overlap(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $configuration = $this->createConfiguration();
        $academicYear = $configuration->academicYear;

        $this->actingAs($admin)
            ->post(route('academic.calendars.periods.generate'), [
                'academic_year_id' => $academicYear->id,
            ])
            ->assertRedirect(route('academic.calendars.periods.index', ['academic_year_id' => $academicYear->id]));

        $this->actingAs($admin)
            ->from(route('academic.calendars.periods.index', ['academic_year_id' => $academicYear->id]))
            ->put(route('academic.calendars.periods.update', $academicYear), [
                'periods' => [
                    ['start_date' => '2025-09-01', 'end_date' => '2025-11-30'],
                    ['start_date' => '2025-11-15', 'end_date' => '2026-02-15'],
                    ['start_date' => '2026-04-15', 'end_date' => '2026-07-15'],
                ],
            ])
            ->assertSessionHasErrors('periods.1.start_date');
    }

    public function test_locked_period_configuration_cannot_be_updated_by_non_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $director = $this->userWithRole('directeur');
        $configuration = $this->createConfiguration();
        $period = AcademicPeriod::query()->create([
            'school_id' => $configuration->school_id,
            'academic_configuration_id' => $configuration->id,
            'academic_year_id' => $configuration->academic_year_id,
            'name' => 'Premier trimestre',
            'position' => 1,
            'start_date' => '2025-09-01',
            'end_date' => '2025-11-30',
            'is_active' => true,
            'is_locked' => true,
            'created_by' => $director->id,
            'updated_by' => $director->id,
        ]);

        $this->actingAs($director)
            ->from(route('academic.periods.edit', $period))
            ->put(route('academic.periods.update', $period), [
                'academic_configuration_id' => $configuration->id,
                'name' => 'Premier trimestre ajusté',
                'position' => 1,
                'start_date' => '2025-09-01',
                'end_date' => '2025-11-30',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('period');
    }

    public function test_discontinuous_day_must_have_valid_hours(): void
    {
        $this->seed(DatabaseSeeder::class);

        $year = $this->activeYear();
        $payload = [
            'academic_year_id' => $year->id,
            'scope_type' => 'global',
            'day_type' => 'discontinuous',
            'morning_start_time' => '08:00',
            'morning_end_time' => '12:00',
            'afternoon_start_time' => '11:00',
            'afternoon_end_time' => '16:00',
            'is_active' => true,
        ];

        $request = new \App\Http\Requests\Academic\StoreSchoolDayConfigurationRequest();
        $request->merge($payload);

        $validator = Validator::make($payload, $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('afternoon_start_time', $validator->errors()->messages());
    }

    public function test_inactive_calendar_cannot_receive_events(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $calendar = $this->createCalendar(['is_active' => false]);

        $this->actingAs($admin)
            ->from(route('academic.events.create'))
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Réunion pédagogique',
                'event_type' => 'reunion',
                'start_datetime' => '2025-10-05 15:00:00',
                'end_datetime' => '2025-10-05 17:00:00',
                'location' => 'Salle des professeurs',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'global',
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertSessionHasErrors('academic_calendar_id');
    }

    public function test_event_end_date_must_be_after_start_date(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $calendar = $this->createCalendar();

        $this->actingAs($admin)
            ->from(route('academic.events.create'))
            ->post(route('academic.events.store'), [
                'academic_calendar_id' => $calendar->id,
                'title' => 'Composition',
                'event_type' => 'composition',
                'start_datetime' => '2025-12-05 08:00:00',
                'end_datetime' => '2025-12-04 10:00:00',
                'location' => 'Salle 1',
                'is_full_day' => '0',
                'visibility' => 'public',
                'target_type' => 'global',
                'status' => 'pending_validation',
                'requires_validation' => '0',
            ])
            ->assertSessionHasErrors('end_datetime');
    }

    public function test_cancelled_event_must_have_cancellation_reason(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $event = $this->createEvent(['status' => 'published', 'published_by' => $admin->id, 'published_at' => now()]);

        $this->actingAs($admin)
            ->from(route('academic.events.show', $event))
            ->post(route('academic.events.cancel', $event), [])
            ->assertSessionHasErrors('cancellation_reason');
    }

    public function test_parent_cannot_view_internal_event(): void
    {
        $this->seed(DatabaseSeeder::class);

        $parent = $this->userWithRole('parent');
        $event = $this->createEvent([
            'status' => 'published',
            'visibility' => 'internal',
            'target_type' => 'parents',
            'published_by' => $this->admin()->id,
            'published_at' => now(),
        ]);

        $this->actingAs($parent)
            ->get(route('academic.events.show', $event))
            ->assertForbidden();
    }

    public function test_student_only_sees_published_targeted_events(): void
    {
        $this->seed(DatabaseSeeder::class);

        $student = $this->userWithRole('eleve');
        $admin = $this->admin();

        $globalEvent = $this->createEvent([
            'title' => 'Rentrée générale',
            'status' => 'published',
            'visibility' => 'public',
            'target_type' => 'global',
            'published_by' => $admin->id,
            'published_at' => now(),
        ]);

        $studentEvent = $this->createEvent([
            'title' => 'Message aux élèves',
            'status' => 'published',
            'visibility' => 'public',
            'target_type' => 'students',
            'published_by' => $admin->id,
            'published_at' => now(),
        ]);

        $parentEvent = $this->createEvent([
            'title' => 'Message aux parents',
            'status' => 'published',
            'visibility' => 'public',
            'target_type' => 'parents',
            'published_by' => $admin->id,
            'published_at' => now(),
        ]);

        $draftEvent = $this->createEvent([
            'title' => 'Brouillon élèves',
            'status' => 'draft',
            'visibility' => 'public',
            'target_type' => 'students',
        ]);

        $internalEvent = $this->createEvent([
            'title' => 'Interne élèves',
            'status' => 'published',
            'visibility' => 'internal',
            'target_type' => 'students',
            'published_by' => $admin->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('academic.events.index'));

        $response
            ->assertOk()
            ->assertSee($globalEvent->title)
            ->assertSee($studentEvent->title)
            ->assertDontSee($parentEvent->title)
            ->assertDontSee($draftEvent->title)
            ->assertDontSee($internalEvent->title);
    }

    public function test_event_publication_is_audited_and_dispatched(): void
    {
        $this->seed(DatabaseSeeder::class);

        Event::fake([CalendarEventPublished::class]);

        $admin = $this->admin();
        $event = $this->createEvent(['status' => 'validated']);

        $this->actingAs($admin)
            ->post(route('academic.events.publish', $event))
            ->assertSessionHasNoErrors();

        Event::assertDispatched(CalendarEventPublished::class);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'academic',
            'action' => 'calendar_event.published',
            'auditable_type' => CalendarEvent::class,
            'auditable_id' => $event->id,
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

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::query()->where('slug', $slug)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user->fresh(['roles']);
    }

    private function createConfiguration(array $overrides = []): AcademicConfiguration
    {
        $admin = $this->admin();

        return AcademicConfiguration::query()->create(array_merge([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $this->activeYear()->id,
            'period_type' => 'trimester',
            'period_count' => 3,
            'school_day_type' => 'continuous',
            'is_active' => true,
            'is_locked' => false,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function configurationFormPayload(AcademicYear $year): array
    {
        return [
            'academic_year_id' => $year->id,
            'period_type' => 'trimester',
            'school_day_type' => 'continuous',
            'start_time' => '07:30',
            'end_time' => '13:30',
            'break_start_time' => '10:00',
            'break_end_time' => '10:15',
            'is_active' => '1',
            'is_locked' => '0',
        ];
    }

    private function createCalendar(array $overrides = []): AcademicCalendar
    {
        $admin = $this->admin();

        return AcademicCalendar::query()->create(array_merge([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $this->activeYear()->id,
            'name' => 'Calendrier général '.fake()->unique()->word(),
            'description' => 'Calendrier de test',
            'scope_type' => 'global',
            'is_active' => true,
            'created_by' => $admin->id,
        ], $overrides));
    }

    private function createClassroom(array $overrides = []): AcademicClassroom
    {
        $admin = $this->admin();
        $level = AcademicLevel::query()->where('code', 'CM2')->firstOrFail();

        return AcademicClassroom::query()->create(array_merge([
            'school_id' => $this->defaultSchool()->id,
            'academic_year_id' => $this->activeYear()->id,
            'level_id' => $level->id,
            'series_id' => null,
            'name' => 'CM2 - A',
            'code' => 'CM2-A',
            'capacity' => 45,
            'description' => 'Classe de test',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $overrides));
    }

    private function createSeries(array $overrides = []): AcademicSeries
    {
        $cycle = AcademicCycle::query()->where('code', 'SECONDAIRE-2')->firstOrFail();

        $name = $overrides['name'] ?? 'Série '.fake()->unique()->word();
        $baseCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));
        $baseCode = $baseCode !== '' ? $baseCode : 'SERIE';

        return AcademicSeries::query()->create(array_merge([
            'cycle_id' => $cycle->id,
            'name' => $name,
            'code' => $baseCode,
            'short_name' => 'SP',
            'description' => 'Série de test',
            'sort_order' => ((int) AcademicSeries::query()->max('sort_order')) + 1,
            'is_active' => true,
        ], $overrides));
    }

    private function createEvent(array $overrides = []): CalendarEvent
    {
        $admin = $this->admin();
        $calendar = $overrides['academic_calendar_id'] ?? $this->createCalendar()->id;

        return CalendarEvent::query()->create(array_merge([
            'school_id' => $this->defaultSchool()->id,
            'academic_calendar_id' => $calendar,
            'title' => 'Événement '.fake()->unique()->word(),
            'description' => 'Événement de test',
            'event_type' => 'reunion',
            'start_datetime' => '2025-10-05 15:00:00',
            'end_datetime' => '2025-10-05 17:00:00',
            'is_full_day' => false,
            'location' => 'Salle polyvalente',
            'target_type' => 'global',
            'target_id' => null,
            'status' => 'draft',
            'visibility' => 'public',
            'requires_validation' => false,
            'created_by' => $admin->id,
        ], $overrides));
    }
}

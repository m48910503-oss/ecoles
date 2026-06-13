<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicLevelRequest;
use App\Http\Requests\Academic\UpdateAcademicLevelRequest;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Services\Academic\AcademicLevelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicLevelController extends Controller
{
    public function __construct(
        private readonly AcademicLevelService $academicLevelService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicLevel::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $cycleId = $request->integer('cycle_id') ?: null;
        $usesSeries = trim((string) $request->string('uses_series'));

        $baseQuery = AcademicLevel::query()
            ->with(['cycle'])
            ->withCount(['calendars', 'classrooms', 'series'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($cycleId !== null, fn ($query) => $query->where('cycle_id', $cycleId))
            ->when($usesSeries === 'yes', fn ($query) => $query->where('uses_series', true))
            ->when($usesSeries === 'no', fn ($query) => $query->where('uses_series', false));

        return view('academic.levels.index', [
            'levels' => (clone $baseQuery)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'cycleId' => $cycleId,
            'usesSeries' => $usesSeries,
            'cycles' => AcademicCycle::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'seriesOptions' => AcademicSeries::query()
                ->with('cycle')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'with_series' => (clone $baseQuery)->where('uses_series', true)->count(),
                'referenced' => (clone $baseQuery)->where(function ($query): void {
                    $query
                        ->has('calendars')
                        ->orHas('classrooms')
                        ->orHas('coefficients')
                        ->orHas('conductSettings');
                })->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AcademicLevel::class);

        return view('academic.levels.form', [
            'level' => new AcademicLevel(),
            'isEdit' => false,
            'cycles' => AcademicCycle::query()->orderBy('sort_order')->orderBy('name')->get(),
            'seriesOptions' => AcademicSeries::query()
                ->with('cycle')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
        ]);
    }

    public function store(StoreAcademicLevelRequest $request): RedirectResponse
    {
        $this->academicLevelService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.levels.index')
            ->with('success', __('academic.levels.messages.created'));
    }

    public function show(AcademicLevel $level): View
    {
        $this->authorize('view', $level);

        return view('academic.levels.show', [
            'level' => $level->load(['cycle', 'calendars', 'series'])->loadCount(['calendars', 'classrooms', 'series']),
        ]);
    }

    public function edit(AcademicLevel $level): View
    {
        $this->authorize('update', $level);

        return view('academic.levels.form', [
            'level' => $level->load(['cycle', 'series']),
            'isEdit' => true,
            'cycles' => AcademicCycle::query()->orderBy('sort_order')->orderBy('name')->get(),
            'seriesOptions' => AcademicSeries::query()
                ->with('cycle')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
        ]);
    }

    public function update(UpdateAcademicLevelRequest $request, AcademicLevel $level): RedirectResponse
    {
        $this->academicLevelService->update($level, $request->validated(), $request->user());

        return redirect()
            ->route('academic.levels.index')
            ->with('success', __('academic.levels.messages.updated'));
    }

    public function destroy(Request $request, AcademicLevel $level): RedirectResponse
    {
        $this->authorize('delete', $level);

        $this->academicLevelService->delete($level, $request->user());

        return redirect()
            ->route('academic.levels.index')
            ->with('success', __('academic.levels.messages.deleted'));
    }

    public function toggleActive(Request $request, AcademicLevel $level): RedirectResponse
    {
        $this->authorize('disable', $level);

        $this->academicLevelService->toggleActive($level, $request->user());

        return back()->with('success', $level->fresh()->is_active
            ? __('academic.levels.messages.enabled')
            : __('academic.levels.messages.disabled'));
    }
}

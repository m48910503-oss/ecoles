<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicCycleRequest;
use App\Http\Requests\Academic\UpdateAcademicCycleRequest;
use App\Models\AcademicCycle;
use App\Services\Academic\AcademicCycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCycleController extends Controller
{
    public function __construct(
        private readonly AcademicCycleService $academicCycleService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicCycle::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $group = trim((string) $request->string('group'));
        $groupOptions = AcademicCycleCategory::managementGroups();
        $group = array_key_exists($group, $groupOptions) ? $group : '';

        $baseQuery = AcademicCycle::query()
            ->withCount(['levels', 'series', 'calendars', 'schoolDayConfigurations'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($group !== '', function ($query) use ($group): void {
                $query->whereIn('category', AcademicCycleCategory::managementGroupCategories($group));
            });

        return view('academic.cycles.index', [
            'cycles' => (clone $baseQuery)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'group' => $group,
            'groupOptions' => $groupOptions,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'supports_series' => (clone $baseQuery)->where('category', AcademicCycleCategory::UpperSecondary->value)->count(),
                'referenced' => (clone $baseQuery)->where(function ($query): void {
                    $query
                        ->has('levels')
                        ->orHas('series')
                        ->orHas('calendars')
                        ->orHas('schoolDayConfigurations');
                })->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AcademicCycle::class);

        return view('academic.cycles.form', [
            'cycle' => new AcademicCycle(),
            'isEdit' => false,
        ]);
    }

    public function store(StoreAcademicCycleRequest $request): RedirectResponse
    {
        $this->academicCycleService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.cycles.index')
            ->with('success', __('academic.cycles.messages.created'));
    }

    public function show(AcademicCycle $cycle): View
    {
        $this->authorize('view', $cycle);

        return view('academic.cycles.show', [
            'cycle' => $cycle->load(['levels', 'series', 'calendars', 'schoolDayConfigurations']),
        ]);
    }

    public function edit(AcademicCycle $cycle): View
    {
        $this->authorize('update', $cycle);

        return view('academic.cycles.form', [
            'cycle' => $cycle,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateAcademicCycleRequest $request, AcademicCycle $cycle): RedirectResponse
    {
        $this->academicCycleService->update($cycle, $request->validated(), $request->user());

        return redirect()
            ->route('academic.cycles.index')
            ->with('success', __('academic.cycles.messages.updated'));
    }

    public function destroy(AcademicCycle $cycle): RedirectResponse
    {
        $this->authorize('delete', $cycle);

        $this->academicCycleService->delete($cycle, request()->user());

        return redirect()
            ->route('academic.cycles.index')
            ->with('success', __('academic.cycles.messages.deleted'));
    }

    public function toggleActive(Request $request, AcademicCycle $cycle): RedirectResponse
    {
        $this->authorize('disable', $cycle);

        $this->academicCycleService->toggleActive($cycle, $request->user());

        return back()->with('success', $cycle->fresh()->is_active
            ? __('academic.cycles.messages.enabled')
            : __('academic.cycles.messages.disabled'));
    }
}

<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicSeriesRequest;
use App\Http\Requests\Academic\UpdateAcademicSeriesRequest;
use App\Models\AcademicClassroom;
use App\Models\AcademicCoefficient;
use App\Models\AcademicCycle;
use App\Models\AcademicSeries;
use App\Services\Academic\AcademicSeriesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicSeriesController extends Controller
{
    public function __construct(
        private readonly AcademicSeriesService $academicSeriesService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicSeries::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $cycleId = $request->integer('cycle_id') ?: null;

        $baseQuery = AcademicSeries::query()
            ->with('cycle')
            ->withCount(['classrooms', 'coefficients'])
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
            ->when($cycleId !== null, fn ($query) => $query->where('cycle_id', $cycleId));

        return view('academic.series.index', [
            'seriesCollection' => (clone $baseQuery)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'cycleId' => $cycleId,
            'cycles' => AcademicCycle::query()
                ->where('category', AcademicCycleCategory::UpperSecondary->value)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'classrooms' => AcademicClassroom::query()->whereNotNull('series_id')->count(),
                'coefficients' => AcademicCoefficient::query()->whereNotNull('series_id')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AcademicSeries::class);

        return view('academic.series.form', [
            'series' => new AcademicSeries(),
            'isEdit' => false,
            'cycles' => AcademicCycle::query()
                ->where('category', AcademicCycleCategory::UpperSecondary->value)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreAcademicSeriesRequest $request): RedirectResponse
    {
        $this->academicSeriesService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.series.index')
            ->with('success', __('academic.series.messages.created'));
    }

    public function show(AcademicSeries $series): View
    {
        $this->authorize('view', $series);

        return view('academic.series.show', [
            'series' => $series->load('cycle')->loadCount(['classrooms', 'coefficients']),
        ]);
    }

    public function edit(AcademicSeries $series): View
    {
        $this->authorize('update', $series);

        return view('academic.series.form', [
            'series' => $series->load('cycle'),
            'isEdit' => true,
            'cycles' => AcademicCycle::query()
                ->where('category', AcademicCycleCategory::UpperSecondary->value)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateAcademicSeriesRequest $request, AcademicSeries $series): RedirectResponse
    {
        $this->academicSeriesService->update($series, $request->validated(), $request->user());

        return redirect()
            ->route('academic.series.index')
            ->with('success', __('academic.series.messages.updated'));
    }

    public function destroy(Request $request, AcademicSeries $series): RedirectResponse
    {
        $this->authorize('delete', $series);

        $this->academicSeriesService->delete($series, $request->user());

        return redirect()
            ->route('academic.series.index')
            ->with('success', __('academic.series.messages.deleted'));
    }

    public function toggleActive(Request $request, AcademicSeries $series): RedirectResponse
    {
        $this->authorize('disable', $series);

        $this->academicSeriesService->toggleActive($series, $request->user());

        return back()->with('success', $series->fresh()->is_active
            ? __('academic.series.messages.enabled')
            : __('academic.series.messages.disabled'));
    }
}

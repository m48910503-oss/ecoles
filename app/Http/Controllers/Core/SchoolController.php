<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\StoreSchoolRequest;
use App\Http\Requests\Core\SwitchSchoolRequest;
use App\Http\Requests\Core\UpdateSchoolRequest;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\Core\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(
        private readonly SchoolService $schoolService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', School::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        $schools = School::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('core.schools.index', [
            'schools' => $schools,
            'search' => $search,
            'status' => $status,
            'stats' => $this->schoolService->stats(),
            'multiSchool' => $this->schoolService->multiSchoolStatus(),
            'activeAcademicYear' => AcademicYear::query()->active()->first(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', School::class);

        abort_unless($this->schoolService->isMultiSchoolEnabled(), 403);

        return view('core.schools.form', [
            'school' => new School(['is_active' => true]),
            'isEdit' => false,
        ]);
    }

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $this->schoolService->create($request->validated(), $request->user());

        return redirect()
            ->route('core.schools.index')
            ->with('success', __('core.schools.messages.created'));
    }

    public function edit(School $school): View
    {
        $this->authorize('update', $school);

        return view('core.schools.form', [
            'school' => $school,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        $this->schoolService->update($school, $request->validated(), $request->user());

        return redirect()
            ->route('core.schools.index')
            ->with('success', __('core.schools.messages.updated'));
    }

    public function switchCurrent(SwitchSchoolRequest $request, School $school): RedirectResponse
    {
        $this->schoolService->switchCurrentSchool($school, $request->user());

        return back()->with('success', __('core.schools.messages.switched', ['name' => $school->name]));
    }

    public function destroy(School $school): RedirectResponse
    {
        $this->authorize('delete', $school);

        $this->schoolService->delete($school, request()->user());

        return redirect()
            ->route('core.schools.index')
            ->with('success', __('core.schools.messages.deleted'));
    }
}

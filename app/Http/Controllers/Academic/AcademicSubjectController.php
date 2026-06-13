<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\AcademicSubjectCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicSubjectRequest;
use App\Http\Requests\Academic\UpdateAcademicSubjectRequest;
use App\Models\AcademicSubject;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\AcademicSubjectService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicSubjectController extends Controller
{
    public function __construct(
        private readonly AcademicSubjectService $academicSubjectService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicSubject::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $category = trim((string) $request->string('category'));
        $optional = trim((string) $request->string('optional'));
        $conduct = trim((string) $request->string('conduct'));
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = AcademicSubject::query()
            ->with(['school'])
            ->withCount(['coefficients', 'conductSettings'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
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
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($optional === 'yes', fn ($query) => $query->where('is_optional_by_default', true))
            ->when($optional === 'no', fn ($query) => $query->where('is_optional_by_default', false))
            ->when($conduct === 'yes', fn ($query) => $query->where('is_conduct', true))
            ->when($conduct === 'no', fn ($query) => $query->where('is_conduct', false));

        return view('academic.subjects.index', [
            'subjects' => (clone $baseQuery)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'category' => $category,
            'optional' => $optional,
            'conduct' => $conduct,
            'schoolId' => $schoolId,
            'canChooseSchool' => $canChooseSchool,
            'schools' => $schoolOptions,
            'categoryOptions' => AcademicSubjectCategory::cases(),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'optional' => (clone $baseQuery)->where('is_optional_by_default', true)->count(),
                'conduct' => (clone $baseQuery)->where('is_conduct', true)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicSubject::class);

        return view('academic.subjects.form', $this->formData($request, new AcademicSubject(), false));
    }

    public function store(StoreAcademicSubjectRequest $request): RedirectResponse
    {
        $this->academicSubjectService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.subjects.index')
            ->with('success', __('academic.subjects.messages.created'));
    }

    public function show(AcademicSubject $subject): View
    {
        $this->authorize('view', $subject);

        return view('academic.subjects.show', [
            'subject' => $subject->load(['school', 'creator', 'updater', 'coefficients.level', 'conductSettings.level']),
        ]);
    }

    public function edit(Request $request, AcademicSubject $subject): View
    {
        $this->authorize('update', $subject);

        return view('academic.subjects.form', $this->formData($request, $subject, true));
    }

    public function update(UpdateAcademicSubjectRequest $request, AcademicSubject $subject): RedirectResponse
    {
        $this->academicSubjectService->update($subject, $request->validated(), $request->user());

        return redirect()
            ->route('academic.subjects.index')
            ->with('success', __('academic.subjects.messages.updated'));
    }

    public function toggleActive(Request $request, AcademicSubject $subject): RedirectResponse
    {
        $this->authorize('disable', $subject);

        $this->academicSubjectService->toggleActive($subject, $request->user());

        return back()->with('success', $subject->fresh()->is_active
            ? __('academic.subjects.messages.enabled')
            : __('academic.subjects.messages.disabled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, AcademicSubject $subject, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);

        return [
            'subject' => $subject,
            'isEdit' => $isEdit,
            'schools' => $schoolOptions,
            'canChooseSchool' => $this->canChooseSchool($request, $schoolOptions->count()),
            'currentSchoolId' => $subject->school_id ?: $this->schoolContext->current()->id,
            'categoryOptions' => AcademicSubjectCategory::cases(),
        ];
    }

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}

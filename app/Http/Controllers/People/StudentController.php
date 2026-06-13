<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreStudentRequest;
use App\Http\Requests\People\UpdateStudentRequest;
use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\Core\SchoolContext;
use App\Services\People\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $schoolId = $this->schoolContext->current()->id;
        $search = trim((string) $request->string('search'));
        $cycleId = $request->integer('cycle_id') ?: null;
        $levelId = $request->integer('level_id') ?: null;
        $classroomId = $request->integer('academic_classroom_id') ?: null;
        $gender = trim((string) $request->string('gender'));
        $status = trim((string) $request->string('status'));

        $baseQuery = Student::query()
            ->with(['currentClassroom.level.cycle', 'parents'])
            ->withCount('parents')
            ->forSchool($schoolId)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('matricule', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhereHas('parents', function ($parentQuery) use ($search): void {
                            $parentQuery->where(function ($relationQuery) use ($search): void {
                                $relationQuery
                                    ->where('last_name', 'like', "%{$search}%")
                                    ->orWhere('first_names', 'like', "%{$search}%");
                            });
                        });
                });
            })
            ->when($cycleId !== null, fn ($query) => $query->whereHas('currentClassroom.level.cycle', fn ($relationQuery) => $relationQuery->whereKey($cycleId)))
            ->when($levelId !== null, fn ($query) => $query->whereHas('currentClassroom.level', fn ($relationQuery) => $relationQuery->whereKey($levelId)))
            ->when($classroomId !== null, fn ($query) => $query->where('academic_classroom_id', $classroomId))
            ->when($gender !== '', fn ($query) => $query->where('gender', $gender))
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        return view('people.students.index', [
            'students' => (clone $baseQuery)
                ->orderBy('last_name')
                ->orderBy('first_names')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'cycleId' => $cycleId,
            'levelId' => $levelId,
            'classroomId' => $classroomId,
            'gender' => $gender,
            'status' => $status,
            'cycles' => AcademicCycle::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'cycle_id', 'name']),
            'classrooms' => AcademicClassroom::query()->with(['level.cycle'])->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'total' => Student::query()->forSchool($schoolId)->count(),
                'active' => Student::query()->forSchool($schoolId)->active()->count(),
                'without_parents' => Student::query()->forSchool($schoolId)->withoutParents()->count(),
                'suspended' => Student::query()->forSchool($schoolId)->where('status', 'suspendu')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Student::class);

        return view('people.students.form', $this->formData($request, new Student(['status' => 'preinscrit']), false));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->studentService->create($request->validated(), $request->user());

        return redirect()
            ->route('people.students.index')
            ->with('success', __('people.students.messages.created'));
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        $student->load([
            'school',
            'currentClassroom.level.cycle',
            'entryAcademicYear',
            'parents',
            'user.roles',
            'documents' => fn ($query) => $query->with('uploader')->latest(),
        ]);
        $parents = $student->parents->sortByDesc(fn (Guardian $guardian) => (bool) $guardian->pivot->is_primary_contact)->values();
        $historyLogs = $this->studentService->historyFor($student);

        return view('people.students.show', [
            'student' => $student,
            'parents' => $parents,
            'documents' => $student->documents,
            'availableGuardians' => Guardian::query()
                ->forSchool((int) $student->school_id)
                ->orderBy('last_name')
                ->orderBy('first_names')
                ->get(['id', 'last_name', 'first_names', 'phone', 'email', 'gender', 'status']),
            'historyLogs' => $historyLogs,
            'canViewSensitive' => $this->authorizeForViewSensitive($student),
        ]);
    }

    public function edit(Request $request, Student $student): View
    {
        $this->authorize('update', $student);

        return view('people.students.form', $this->formData($request, $student->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear']), true));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->studentService->update($student, $request->validated(), $request->user());

        return redirect()
            ->route('people.students.index')
            ->with('success', __('people.students.messages.updated'));
    }

    public function toggleStatus(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('disable', $student);

        $this->studentService->toggleStatus($student, $request->user());

        return back()->with('success', $student->fresh()->status === 'actif'
            ? __('people.students.messages.reactivated')
            : __('people.students.messages.suspended'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, Student $student, bool $isEdit): array
    {
        $schoolId = $this->schoolContext->current()->id;
        $activeYear = AcademicYear::query()->active()->first();

        return [
            'student' => $student,
            'isEdit' => $isEdit,
            'nextMatricule' => $isEdit ? $student->matricule : $this->studentService->nextMatricule($schoolId),
            'cycles' => AcademicCycle::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'cycle_id', 'name']),
            'classrooms' => AcademicClassroom::query()->with(['level.cycle'])->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->orderByDesc('id')->get(['id', 'name']),
            'activeYearId' => $activeYear?->id,
            'existingGuardians' => Guardian::query()
                ->forSchool($schoolId)
                ->orderBy('last_name')
                ->orderBy('first_names')
                ->get(['id', 'last_name', 'first_names', 'phone', 'email', 'gender', 'status']),
            'genderOptions' => Student::genderOptions(),
            'statusOptions' => Student::statusOptions(),
            'relationshipOptions' => Student::relationshipOptions(),
            'guardianGenderOptions' => Guardian::genderOptions(),
        ];
    }

    private function authorizeForViewSensitive(Student $student): bool
    {
        return request()->user()?->can('viewSensitive', $student) ?? false;
    }
}

<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreTeacherRequest;
use App\Http\Requests\People\UpdateTeacherRequest;
use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Services\Core\SchoolContext;
use App\Services\People\TeacherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function __construct(
        private readonly TeacherService $teacherService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Teacher::class);

        $schoolId = $this->schoolContext->current()->id;
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $contract = trim((string) $request->string('contract'));
        $account = trim((string) $request->string('account'));
        $track = trim((string) $request->string('track'));
        $invitation = trim((string) $request->string('invitation'));
        $cycleId = $request->integer('cycle_id') ?: null;

        return view('people.teachers.index', [
            'teachers' => $this->teacherService->indexQuery($schoolId, [
                'search' => $search,
                'status' => $status,
                'contract' => $contract,
                'account' => $account,
                'track' => $track,
                'invitation' => $invitation,
                'cycle_id' => $cycleId,
            ])
                ->orderBy('last_name')
                ->orderBy('first_names')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'contract' => $contract,
            'account' => $account,
            'track' => $track,
            'invitation' => $invitation,
            'cycleId' => $cycleId,
            'statusOptions' => Teacher::statusOptions(),
            'contractOptions' => Teacher::contractOptions(),
            'accountOptions' => [
                'linked' => __('people.teachers.index.filters.linked_account'),
                'unlinked' => __('people.teachers.index.filters.unlinked_account'),
            ],
            'trackOptions' => [
                'primary' => __('people.teachers.options.tracks.primary'),
                'secondary' => __('people.teachers.options.tracks.secondary'),
                'unclassified' => __('people.teachers.options.tracks.unclassified'),
            ],
            'invitationOptions' => [
                'pending' => __('people.teachers.index.filters.pending_invitation'),
                'completed' => __('people.teachers.index.filters.completed_invitation'),
                'validated' => __('people.teachers.index.filters.validated_invitation'),
                'expired' => __('people.teachers.index.filters.expired_invitation'),
                'revoked' => __('people.teachers.index.filters.revoked_invitation'),
                'none' => __('people.teachers.index.filters.no_invitation'),
            ],
            'cycleOptions' => AcademicCycle::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'category']),
            'stats' => $this->teacherService->stats($schoolId),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Teacher::class);

        $schoolId = $this->schoolContext->current()->id;

        return view('people.teachers.form', $this->formData(new Teacher(['status' => 'actif', 'contract_type' => 'contractuel']), false, $schoolId));
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $teacher = $this->teacherService->create($request->validated(), $request->user());

        return redirect()
            ->route('people.teachers.show', $teacher)
            ->with('success', __('people.teachers.messages.created'));
    }

    public function show(Request $request, Teacher $teacher): View
    {
        $this->authorize('view', $teacher);
        abort_unless((int) $teacher->school_id === (int) $this->schoolContext->current()->id, 404);

        $teacher->load([
            'school',
            'academicCycle',
            'mainClassroom.level.cycle',
            'mainSubject',
            'latestInvitation',
            'user.roles',
            'assignments' => fn ($query) => $query->with(['subject', 'classroom.level.cycle', 'academicYear'])->orderByDesc('academic_year_id')->orderBy('academic_subject_id'),
            'documents' => fn ($query) => $query->with('uploader')->latest(),
        ]);

        $academicYear = AcademicYear::query()->active()->first();
        $backUrl = $request->user()?->can('viewAny', Teacher::class)
            ? route('people.teachers.index')
            : route('people.dashboard');

        return view('people.teachers.show', [
            'teacher' => $teacher,
            'backUrl' => $backUrl,
            'documents' => $teacher->documents,
            'historyLogs' => $this->teacherService->historyFor($teacher),
            'canViewHistory' => $request->user()?->can('history', $teacher) ?? false,
            'availableSubjects' => AcademicSubject::query()
                ->where('school_id', (int) $teacher->school_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'availableClassrooms' => AcademicClassroom::query()
                ->with(['level.cycle'])
                ->where('school_id', (int) $teacher->school_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'level_id']),
            'academicYears' => AcademicYear::query()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'name']),
            'activeYearId' => $academicYear?->id,
            'statusOptions' => Teacher::statusOptions(),
            'contractOptions' => Teacher::contractOptions(),
            'genderOptions' => Teacher::genderOptions(),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        $this->authorize('update', $teacher);
        abort_unless((int) $teacher->school_id === (int) $this->schoolContext->current()->id, 404);

        return view('people.teachers.form', $this->formData($teacher->load(['user', 'academicCycle', 'mainClassroom.level.cycle', 'mainSubject']), true, (int) $teacher->school_id));
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $this->teacherService->update($teacher, $request->validated(), $request->user());

        return redirect()
            ->route('people.teachers.show', $teacher)
            ->with('success', __('people.teachers.messages.updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Teacher $teacher, bool $isEdit, int $schoolId): array
    {
        $cycles = AcademicCycle::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $classrooms = AcademicClassroom::query()
            ->with(['level.cycle'])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'level_id']);

        $primaryClassrooms = $classrooms
            ->filter(function (AcademicClassroom $classroom): bool {
                return $classroom->level?->cycle?->category?->teacherTrack() === 'primary';
            })
            ->values();

        return [
            'teacher' => $teacher,
            'isEdit' => $isEdit,
            'nextMatricule' => $isEdit ? $teacher->matricule : $this->teacherService->nextMatricule($schoolId),
            'genderOptions' => Teacher::genderOptions(),
            'statusOptions' => Teacher::statusOptions(),
            'contractOptions' => Teacher::contractOptions(),
            'cycles' => $cycles,
            'cycleTrackMap' => $cycles->mapWithKeys(fn (AcademicCycle $cycle): array => [
                (string) $cycle->id => $cycle->category?->teacherTrack(),
            ])->all(),
            'primaryClassrooms' => $primaryClassrooms,
            'subjects' => AcademicSubject::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ];
    }
}

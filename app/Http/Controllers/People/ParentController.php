<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreParentRequest;
use App\Http\Requests\People\UpdateParentRequest;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\Core\SchoolContext;
use App\Services\People\ParentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function __construct(
        private readonly ParentService $parentService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Guardian::class);

        $schoolId = $this->schoolContext->current()->id;
        $search = trim((string) $request->string('search'));
        $gender = trim((string) $request->string('gender'));
        $status = trim((string) $request->string('status'));
        $account = trim((string) $request->string('account'));
        $children = trim((string) $request->string('children'));

        $parents = $this->parentService->indexQuery($schoolId, [
            'search' => $search,
            'gender' => $gender,
            'status' => $status,
            'account' => $account,
            'children' => $children,
        ])
            ->orderBy('last_name')
            ->orderBy('first_names')
            ->paginate(12)
            ->withQueryString();

        return view('people.parents.index', [
            'parents' => $parents,
            'search' => $search,
            'gender' => $gender,
            'status' => $status,
            'account' => $account,
            'children' => $children,
            'genderOptions' => Guardian::genderOptions(),
            'statusOptions' => Guardian::statusOptions(),
            'stats' => $this->parentService->stats($schoolId),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Guardian::class);

        return view('people.parents.form', [
            'parent' => new Guardian(['status' => 'actif']),
            'isEdit' => false,
            'genderOptions' => Guardian::genderOptions(),
            'statusOptions' => Guardian::statusOptions(),
        ]);
    }

    public function store(StoreParentRequest $request): RedirectResponse
    {
        $parent = $this->parentService->create($request->validated(), $request->user());

        return redirect()
            ->route('people.parents.show', $parent)
            ->with('success', __('people.parents.messages.created'));
    }

    public function show(Request $request, Guardian $parent): View
    {
        $this->authorize('view', $parent);

        $parent->load([
            'school',
            'user.roles',
            'students.currentClassroom.level.cycle',
            'documents' => fn ($query) => $query->with('uploader')->latest(),
        ]);

        $children = $parent->students->sortByDesc(fn ($student) => (bool) $student->pivot->is_primary_contact)->values();
        $selectedStudentId = $request->integer('student_id') ?: null;
        $selectedStudent = $selectedStudentId
            ? Student::query()
                ->forSchool((int) $parent->school_id)
                ->with(['currentClassroom.level.cycle'])
                ->find($selectedStudentId)
            : null;
        $selectedRelation = $selectedStudent
            ? $children->firstWhere('id', $selectedStudent->id)?->pivot
            : null;
        $linkedChildIds = $children->pluck('id')->all();
        $availableStudents = Student::query()
            ->forSchool((int) $parent->school_id)
            ->with(['currentClassroom.level.cycle'])
            ->when(! empty($linkedChildIds), fn ($query) => $query->whereNotIn('id', $linkedChildIds))
            ->orderBy('last_name')
            ->orderBy('first_names')
            ->get(['id', 'matricule', 'last_name', 'first_names', 'status', 'academic_classroom_id']);
        $historyLogs = $this->parentService->historyFor($parent);

        return view('people.parents.show', [
            'parent' => $parent,
            'children' => $children,
            'documents' => $parent->documents,
            'linkedChildren' => $children,
            'availableStudents' => $availableStudents,
            'selectedStudent' => $selectedStudent,
            'selectedRelation' => $selectedRelation,
            'selectedStudentId' => $selectedStudentId,
            'historyLogs' => $historyLogs,
            'canViewHistory' => request()->user()?->can('history', $parent) ?? false,
            'relationshipOptions' => \App\Models\Student::relationshipOptions(),
        ]);
    }

    public function edit(Guardian $parent): View
    {
        $this->authorize('update', $parent);

        return view('people.parents.form', [
            'parent' => $parent->load(['students', 'user']),
            'isEdit' => true,
            'genderOptions' => Guardian::genderOptions(),
            'statusOptions' => Guardian::statusOptions(),
        ]);
    }

    public function update(UpdateParentRequest $request, Guardian $parent): RedirectResponse
    {
        $this->parentService->update($parent, $request->validated(), $request->user());

        return redirect()
            ->route('people.parents.show', $parent)
            ->with('success', __('people.parents.messages.updated'));
    }
}

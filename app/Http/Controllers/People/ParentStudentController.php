<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\LinkStudentToParentRequest;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\People\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParentStudentController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService,
    ) {
    }

    public function store(LinkStudentToParentRequest $request, Guardian $parent): RedirectResponse
    {
        $student = Student::query()
            ->where('school_id', $parent->school_id)
            ->findOrFail((int) $request->validated('student_id'));

        $this->studentService->linkParent($student, array_merge($request->validated(), [
            'parent_id' => $parent->id,
        ]), $request->user(), 'student_id');

        return redirect()
            ->route('people.parents.show', [
                'parent' => $parent,
                'student_id' => $student->id,
            ])
            ->with('success', __('people.parents.messages.child_link_saved'));
    }

    public function destroy(Request $request, Guardian $parent, Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === (int) $parent->school_id, 404);

        $this->authorize('linkStudent', $parent);

        $this->studentService->unlinkParent($student, $parent, $request->user(), 'student_id');

        return redirect()
            ->route('people.parents.show', $parent)
            ->with('success', __('people.parents.messages.child_unlinked'));
    }
}

<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\LinkParentToStudentRequest;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\People\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentParentController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService,
    ) {
    }

    public function store(LinkParentToStudentRequest $request, Student $student): RedirectResponse
    {
        $this->studentService->linkParent($student, $request->validated(), $request->user());

        return back()->with('success', __('people.students.messages.parent_linked'));
    }

    public function destroy(Request $request, Student $student, Guardian $parent): RedirectResponse
    {
        $this->authorize('linkParent', $student);

        $this->studentService->unlinkParent($student, $parent, $request->user());

        return back()->with('success', __('people.students.messages.parent_unlinked'));
    }
}

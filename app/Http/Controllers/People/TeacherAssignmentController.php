<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreTeacherAssignmentRequest;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Services\People\TeacherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function __construct(
        private readonly TeacherService $teacherService,
    ) {
    }

    public function store(StoreTeacherAssignmentRequest $request, Teacher $teacher): RedirectResponse
    {
        $this->teacherService->assignSubject($teacher, $request->validated(), $request->user());

        return redirect()
            ->to(route('people.teachers.show', $teacher).'#assignments')
            ->with('success', __('people.teachers.messages.assignment_saved'));
    }

    public function destroy(Request $request, Teacher $teacher, TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $this->authorize('assignSubjects', $teacher);

        $this->teacherService->removeAssignment($teacher, $assignment, $request->user());

        return redirect()
            ->to(route('people.teachers.show', $teacher).'#assignments')
            ->with('success', __('people.teachers.messages.assignment_removed'));
    }
}

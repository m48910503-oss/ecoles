<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\People\PersonAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PersonAccountController extends Controller
{
    public function __construct(
        private readonly PersonAccountService $personAccountService,
    ) {
    }

    public function storeForStudent(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('createAccount', $student);

        $result = $this->personAccountService->createForStudent($student, $request->user());

        return $this->redirectToStudent($student, $result['mode'] === 'created'
            ? __('people.accounts.messages.created_with_password', ['password' => $result['temporary_password']])
            : __('people.accounts.messages.linked_existing'));
    }

    public function storeForParent(Request $request, Guardian $parent): RedirectResponse
    {
        $this->authorize('createAccount', $parent);

        $result = $this->personAccountService->createForParent($parent, $request->user());

        return $this->redirectToParent($parent, $result['mode'] === 'created'
            ? __('people.accounts.messages.created_with_password', ['password' => $result['temporary_password']])
            : __('people.accounts.messages.linked_existing'));
    }

    public function resetForStudent(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('resetAccount', $student);

        $result = $this->personAccountService->resetForStudent($student, $request->user());

        return $this->redirectToStudent($student, $result['mode'] === 'email'
            ? __('people.accounts.messages.reset_link_sent')
            : __('people.accounts.messages.reset_with_password', ['password' => $result['temporary_password']]));
    }

    public function resetForParent(Request $request, Guardian $parent): RedirectResponse
    {
        $this->authorize('resetAccount', $parent);

        $result = $this->personAccountService->resetForParent($parent, $request->user());

        return $this->redirectToParent($parent, $result['mode'] === 'email'
            ? __('people.accounts.messages.reset_link_sent')
            : __('people.accounts.messages.reset_with_password', ['password' => $result['temporary_password']]));
    }

    public function toggleForStudent(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('toggleAccount', $student);

        $user = $this->personAccountService->toggleForStudent($student, $request->user());

        return $this->redirectToStudent($student, $user->is_active
            ? __('people.accounts.messages.reactivated')
            : __('people.accounts.messages.suspended'));
    }

    public function toggleForParent(Request $request, Guardian $parent): RedirectResponse
    {
        $this->authorize('toggleAccount', $parent);

        $user = $this->personAccountService->toggleForParent($parent, $request->user());

        return $this->redirectToParent($parent, $user->is_active
            ? __('people.accounts.messages.reactivated')
            : __('people.accounts.messages.suspended'));
    }

    public function destroyForStudent(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('unlinkAccount', $student);

        $this->personAccountService->unlinkForStudent($student, $request->user());

        return $this->redirectToStudent($student, __('people.accounts.messages.unlinked'));
    }

    public function destroyForParent(Request $request, Guardian $parent): RedirectResponse
    {
        $this->authorize('unlinkAccount', $parent);

        $this->personAccountService->unlinkForParent($parent, $request->user());

        return $this->redirectToParent($parent, __('people.accounts.messages.unlinked'));
    }

    public function storeForTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorize('createAccount', $teacher);

        $result = $this->personAccountService->createForTeacher($teacher, $request->user());

        return $this->redirectToTeacher($teacher, $result['mode'] === 'created'
            ? __('people.accounts.messages.created_with_password', ['password' => $result['temporary_password']])
            : __('people.accounts.messages.linked_existing'));
    }

    public function resetForTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorize('resetAccount', $teacher);

        $result = $this->personAccountService->resetForTeacher($teacher, $request->user());

        return $this->redirectToTeacher($teacher, $result['mode'] === 'email'
            ? __('people.accounts.messages.reset_link_sent')
            : __('people.accounts.messages.reset_with_password', ['password' => $result['temporary_password']]));
    }

    public function toggleForTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorize('toggleAccount', $teacher);

        $user = $this->personAccountService->toggleForTeacher($teacher, $request->user());

        return $this->redirectToTeacher($teacher, $user->is_active
            ? __('people.accounts.messages.reactivated')
            : __('people.accounts.messages.suspended'));
    }

    public function destroyForTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorize('unlinkAccount', $teacher);

        $this->personAccountService->unlinkForTeacher($teacher, $request->user());

        return $this->redirectToTeacher($teacher, __('people.accounts.messages.unlinked'));
    }

    public function storeForStaff(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorize('createAccount', $staff);

        $result = $this->personAccountService->createForStaff($staff, $request->user());

        return $this->redirectToStaff($staff, $result['mode'] === 'created'
            ? __('people.accounts.messages.created_with_password', ['password' => $result['temporary_password']])
            : __('people.accounts.messages.linked_existing'));
    }

    public function resetForStaff(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorize('resetAccount', $staff);

        $result = $this->personAccountService->resetForStaff($staff, $request->user());

        return $this->redirectToStaff($staff, $result['mode'] === 'email'
            ? __('people.accounts.messages.reset_link_sent')
            : __('people.accounts.messages.reset_with_password', ['password' => $result['temporary_password']]));
    }

    public function toggleForStaff(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorize('toggleAccount', $staff);

        $user = $this->personAccountService->toggleForStaff($staff, $request->user());

        return $this->redirectToStaff($staff, $user->is_active
            ? __('people.accounts.messages.reactivated')
            : __('people.accounts.messages.suspended'));
    }

    public function destroyForStaff(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorize('unlinkAccount', $staff);

        $this->personAccountService->unlinkForStaff($staff, $request->user());

        return $this->redirectToStaff($staff, __('people.accounts.messages.unlinked'));
    }

    private function redirectToStudent(Student $student, string $message): RedirectResponse
    {
        return Redirect::to(route('people.students.show', $student).'#account')
            ->with('success', $message);
    }

    private function redirectToParent(Guardian $parent, string $message): RedirectResponse
    {
        return Redirect::to(route('people.parents.show', $parent).'#account')
            ->with('success', $message);
    }

    private function redirectToTeacher(Teacher $teacher, string $message): RedirectResponse
    {
        return Redirect::to(route('people.teachers.show', $teacher).'#account')
            ->with('success', $message);
    }

    private function redirectToStaff(Staff $staff, string $message): RedirectResponse
    {
        return Redirect::to(route('people.staff.show', $staff).'#account')
            ->with('success', $message);
    }
}

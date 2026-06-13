<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\People\TeacherInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class TeacherInvitationController extends Controller
{
    public function __construct(
        private readonly TeacherInvitationService $teacherInvitationService,
    ) {
    }

    public function store(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorize('inviteAccount', $teacher);

        $result = $this->teacherInvitationService->sendInvitation($teacher, $request->user());

        return Redirect::to(route('people.teachers.show', $teacher).'#account')
            ->with('success', __('people.teachers.invitations.messages.created'))
            ->with('teacher_invitation_url', $result['url']);
    }

    public function validateInvitation(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorize('validateInvitation', $teacher);

        $this->teacherInvitationService->validateInvitation($teacher, $request->user());

        return Redirect::to(route('people.teachers.show', $teacher).'#account')
            ->with('success', __('people.teachers.invitations.messages.validated'));
    }
}

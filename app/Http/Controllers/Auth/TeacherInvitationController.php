<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteTeacherInvitationRequest;
use App\Models\TeacherInvitation;
use App\Services\People\TeacherInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherInvitationController extends Controller
{
    public function __construct(
        private readonly TeacherInvitationService $teacherInvitationService,
    ) {
    }

    public function create(string $token): View
    {
        $invitation = $this->teacherInvitationService->findByToken($token);

        return view('auth.teacher-invitation', [
            'invitation' => $invitation,
            'token' => $token,
            'state' => $invitation?->displayStatus() ?? 'invalid',
        ]);
    }

    public function store(CompleteTeacherInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = $this->teacherInvitationService->findByToken($token);

        if ($invitation === null) {
            return redirect()
                ->route('teacher.invitations.show', ['token' => $token])
                ->with('error', __('people.teachers.invitations.validation.invalid_token'));
        }

        if ($invitation->isExpired()) {
            return redirect()
                ->route('teacher.invitations.show', ['token' => $token])
                ->with('error', __('people.teachers.invitations.validation.expired'));
        }

        if (! $invitation->isPending()) {
            return redirect()
                ->route('teacher.invitations.show', ['token' => $token])
                ->with('error', match ($invitation->displayStatus()) {
                    TeacherInvitation::STATUS_COMPLETED => __('people.teachers.invitations.validation.completed'),
                    TeacherInvitation::STATUS_VALIDATED => __('people.teachers.invitations.validation.validated'),
                    TeacherInvitation::STATUS_REVOKED => __('people.teachers.invitations.validation.revoked'),
                    default => __('people.teachers.invitations.validation.invalid_token'),
                });
        }

        $this->teacherInvitationService->completeInvitation($token, $request->validated());

        return redirect()
            ->route('login')
            ->with('success', __('people.teachers.invitations.messages.completed'));
    }
}

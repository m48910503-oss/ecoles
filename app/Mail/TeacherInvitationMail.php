<?php

namespace App\Mail;

use App\Models\TeacherInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly TeacherInvitation $invitation,
        public readonly string $url,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('people.teachers.invitations.mail_subject', [
                'name' => $this->invitation->teacher->fullName(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-invitation',
            with: [
                'invitation' => $this->invitation,
                'teacher' => $this->invitation->teacher,
                'url' => $this->url,
            ],
        );
    }
}

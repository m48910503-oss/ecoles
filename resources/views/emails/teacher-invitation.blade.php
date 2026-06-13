<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('people.teachers.invitations.mail_subject', ['name' => $teacher->fullName()]) }}</title>
    </head>
    <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
        <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;padding:32px;box-shadow:0 20px 50px rgba(15,23,42,.08);">
                <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:#059669;">
                    {{ __('people.teachers.invitations.mail_headline') }}
                </p>

                <h1 style="margin:0;font-size:28px;line-height:1.2;">
                    {{ __('people.teachers.invitations.mail_greeting', ['name' => $teacher->fullName()]) }}
                </h1>

                <p style="margin:20px 0 0;font-size:15px;line-height:1.8;color:#334155;">
                    {{ __('people.teachers.invitations.mail_intro') }}
                </p>

                <div style="margin:24px 0;padding:18px 20px;background:#f1f5f9;border-radius:18px;">
                    <p style="margin:0;font-size:13px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#64748b;">
                        {{ __('people.teachers.invitations.fields.expires_at') }}
                    </p>
                    <p style="margin:8px 0 0;font-size:15px;line-height:1.7;color:#0f172a;">
                        {{ $invitation->expires_at?->translatedFormat('d M Y H:i') ?? '—' }}
                    </p>
                </div>

                <div style="margin:28px 0 0;">
                    <a
                        href="{{ $url }}"
                        style="display:inline-block;background:#0ea5e9;color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:14px;font-weight:700;font-size:14px;"
                    >
                        {{ __('people.teachers.invitations.mail_cta') }}
                    </a>
                </div>

                <p style="margin:24px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                    {{ __('people.teachers.invitations.mail_fallback') }}
                </p>

                <p style="word-break:break-all;margin:10px 0 0;font-size:13px;line-height:1.8;color:#0f172a;">
                    {{ $url }}
                </p>

                <p style="margin:28px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                    {{ __('people.teachers.invitations.mail_security_note') }}
                </p>

                <p style="margin:28px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                    {{ __('people.teachers.invitations.mail_signature') }}<br>
                    {{ config('app.name') }}
                </p>
            </div>
        </div>
    </body>
</html>

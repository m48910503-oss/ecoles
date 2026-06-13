<?php

namespace App\Listeners;

use App\Services\Core\AuditLogService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function handle(Login $event): void
    {
        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()?->ip(),
        ])->save();

        $this->auditLogService->log(
            module: 'auth',
            action: 'login.success',
            auditable: $event->user,
            description: "Connexion réussie pour {$event->user->email}.",
            user: $event->user,
        );
    }
}

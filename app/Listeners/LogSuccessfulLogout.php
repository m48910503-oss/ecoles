<?php

namespace App\Listeners;

use App\Services\Core\AuditLogService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $this->auditLogService->log(
            module: 'auth',
            action: 'logout.success',
            auditable: $event->user,
            description: "Déconnexion de {$event->user->email}.",
            user: $event->user,
        );
    }
}

<?php

namespace App\Listeners;

use App\Services\Core\AuditLogService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'inconnu';

        $this->auditLogService->log(
            module: 'auth',
            action: 'login.failed',
            auditable: $event->user,
            description: "Échec de connexion pour {$email}.",
            user: $event->user,
        );
    }
}

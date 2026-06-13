<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $search = trim((string) $request->string('search'));
        $module = trim((string) $request->string('module'));

        $auditLogs = AuditLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%");
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('core.audit-logs.index', [
            'auditLogs' => $auditLogs,
            'search' => $search,
            'module' => $module,
            'modules' => AuditLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'stats' => [
                'total' => AuditLog::query()->count(),
                'today' => AuditLog::query()->whereDate('created_at', today())->count(),
                'last_7_days' => AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
                'modules' => AuditLog::query()->select('module')->distinct()->count('module'),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Permission::class);

        $search = trim((string) $request->string('search'));
        $module = trim((string) $request->string('module'));
        $coverage = trim((string) $request->string('coverage'));

        $permissions = Permission::query()
            ->with(['roles:id,name'])
            ->withCount('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%");
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($coverage === 'linked', fn ($query) => $query->has('roles'))
            ->when($coverage === 'unlinked', fn ($query) => $query->doesntHave('roles'))
            ->orderBy('module')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('core.permissions.index', [
            'permissions' => $permissions,
            'search' => $search,
            'module' => $module,
            'coverage' => $coverage,
            'modules' => Permission::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'stats' => [
                'total' => Permission::query()->count(),
                'modules' => Permission::query()->select('module')->distinct()->count('module'),
                'linked' => Permission::query()->has('roles')->count(),
                'unlinked' => Permission::query()->doesntHave('roles')->count(),
            ],
        ]);
    }
}

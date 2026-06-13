<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\StoreRoleRequest;
use App\Http\Requests\Core\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Core\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('core.roles.index', [
            'roles' => $roles,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'total' => Role::query()->count(),
                'active' => Role::query()->where('is_active', true)->count(),
                'system' => Role::query()->where('is_system', true)->count(),
                'optional' => Role::query()->where('is_optional', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('core.roles.form', [
            'roleModel' => new Role(),
            'permissions' => Permission::query()->orderBy('module')->orderBy('name')->get()->groupBy('module'),
            'selectedPermissionIds' => [],
            'isEdit' => false,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->create($request->validated(), $request->user());

        return redirect()
            ->route('core.roles.index')
            ->with('success', __('core.roles.messages.created'));
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('core.roles.form', [
            'roleModel' => $role->load('permissions'),
            'permissions' => Permission::query()->orderBy('module')->orderBy('name')->get()->groupBy('module'),
            'selectedPermissionIds' => $role->permissions->pluck('id')->all(),
            'isEdit' => true,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated(), $request->user());

        return redirect()
            ->route('core.roles.index')
            ->with('success', __('core.roles.messages.updated'));
    }
}

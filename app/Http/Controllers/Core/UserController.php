<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\StoreUserRequest;
use App\Http\Requests\Core\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Core\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $roleId = $request->integer('role_id') ?: null;

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'suspended', fn ($query) => $query->where('is_active', false))
            ->when($roleId !== null, function ($query) use ($roleId): void {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->whereKey($roleId));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('core.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'roleId' => $roleId,
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'suspended' => User::query()->where('is_active', false)->count(),
                'multi_role' => User::query()->has('roles', '>', 1)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('core.users.form', [
            'userModel' => new User(),
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedRoleIds' => [],
            'isEdit' => false,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated(), $request->user());

        return redirect()
            ->route('core.users.index')
            ->with('success', __('core.users.messages.created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('core.users.form', [
            'userModel' => $user->load('roles'),
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedRoleIds' => $user->roles->pluck('id')->all(),
            'isEdit' => true,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated(), $request->user());

        return redirect()
            ->route('core.users.index')
            ->with('success', __('core.users.messages.updated'));
    }
}

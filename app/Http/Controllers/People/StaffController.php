<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreStaffRequest;
use App\Http\Requests\People\UpdateStaffRequest;
use App\Models\Staff;
use App\Services\People\StaffService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $staffService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Staff::class);

        $schoolId = $this->schoolContext->current()->id;
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $contract = trim((string) $request->string('contract'));
        $account = trim((string) $request->string('account'));

        return view('people.staff.index', [
            'staffMembers' => $this->staffService->indexQuery($schoolId, [
                'search' => $search,
                'status' => $status,
                'contract' => $contract,
                'account' => $account,
            ])
                ->orderBy('last_name')
                ->orderBy('first_names')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'contract' => $contract,
            'account' => $account,
            'statusOptions' => Staff::statusOptions(),
            'contractOptions' => Staff::contractOptions(),
            'accountOptions' => [
                'linked' => __('people.staff.index.filters.linked_account'),
                'unlinked' => __('people.staff.index.filters.unlinked_account'),
            ],
            'stats' => $this->staffService->stats($schoolId),
            'positionOptions' => Staff::positionOptions(),
            'systemRoleOptions' => Staff::systemRoleOptions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Staff::class);

        $schoolId = $this->schoolContext->current()->id;

        return view('people.staff.form', $this->formData(new Staff(['status' => 'actif', 'contract_type' => 'contractuel']), false, $schoolId));
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $staff = $this->staffService->create($request->validated(), $request->user());

        return redirect()
            ->route('people.staff.show', $staff)
            ->with('success', __('people.staff.messages.created'));
    }

    public function show(Request $request, Staff $staff): View
    {
        $this->authorize('view', $staff);
        abort_unless((int) $staff->school_id === (int) $this->schoolContext->current()->id, 404);

        $staff->load([
            'school',
            'user.roles',
            'documents' => fn ($query) => $query->with('uploader')->latest(),
        ]);

        $backUrl = $request->user()?->can('viewAny', Staff::class)
            ? route('people.staff.index')
            : route('people.dashboard');

        return view('people.staff.show', [
            'staff' => $staff,
            'backUrl' => $backUrl,
            'documents' => $staff->documents,
            'historyLogs' => $this->staffService->historyFor($staff),
            'canViewHistory' => $request->user()?->can('history', $staff) ?? false,
            'statusOptions' => Staff::statusOptions(),
            'contractOptions' => Staff::contractOptions(),
            'genderOptions' => Staff::genderOptions(),
            'positionOptions' => Staff::positionOptions(),
            'systemRoleOptions' => Staff::systemRoleOptions(),
        ]);
    }

    public function edit(Staff $staff): View
    {
        $this->authorize('update', $staff);
        abort_unless((int) $staff->school_id === (int) $this->schoolContext->current()->id, 404);

        return view('people.staff.form', $this->formData($staff->load(['user']), true, (int) $staff->school_id));
    }

    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $this->staffService->update($staff, $request->validated(), $request->user());

        return redirect()
            ->route('people.staff.show', $staff)
            ->with('success', __('people.staff.messages.updated'));
    }

    public function toggleStatus(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorize('disable', $staff);

        $updated = $this->staffService->toggleStatus($staff, $request->user());

        return back()->with('success', $updated->fresh()->status === 'actif'
            ? __('people.staff.messages.reactivated')
            : __('people.staff.messages.suspended'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Staff $staff, bool $isEdit, int $schoolId): array
    {
        return [
            'staff' => $staff,
            'isEdit' => $isEdit,
            'nextMatricule' => $isEdit ? $staff->matricule : $this->staffService->nextMatricule($schoolId),
            'genderOptions' => Staff::genderOptions(),
            'statusOptions' => Staff::statusOptions(),
            'contractOptions' => Staff::contractOptions(),
            'positionOptions' => Staff::positionOptions(),
            'systemRoleOptions' => Staff::systemRoleOptions(),
        ];
    }
}

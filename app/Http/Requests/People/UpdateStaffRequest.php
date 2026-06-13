<?php

namespace App\Http\Requests\People;

use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = $this->route('staff');

        return $staff instanceof Staff
            ? ($this->user()?->can('update', $staff) ?? false)
            : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->filled('status') ? trim((string) $this->input('status')) : 'actif',
            'contract_type' => $this->filled('contract_type') ? trim((string) $this->input('contract_type')) : 'contractuel',
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'position' => $this->filled('position') ? trim((string) $this->input('position')) : null,
            'system_role' => $this->filled('system_role') ? trim((string) $this->input('system_role')) : 'autre',
            'service_start_date' => $this->filled('service_start_date') ? trim((string) $this->input('service_start_date')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_names' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(array_keys(Staff::genderOptions()))],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'position' => ['required', Rule::in(array_keys(Staff::positionOptions()))],
            'system_role' => ['required', Rule::in(array_keys(Staff::systemRoleOptions()))],
            'contract_type' => ['required', Rule::in(array_keys(Staff::contractOptions()))],
            'service_start_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Staff::statusOptions()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

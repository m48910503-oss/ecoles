<?php

namespace App\Http\Requests\Core;

use App\Models\SystemSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SystemSetting::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'security' => array_merge($this->input('security', []), [
                'allow_public_registration' => $this->boolean('security.allow_public_registration'),
            ]),
            'finance' => array_merge($this->input('finance', []), [
                'financial_blocking_enabled' => $this->boolean('finance.financial_blocking_enabled'),
            ]),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'general.school_name' => ['required', 'string', 'max:255'],
            'general.school_phone' => ['nullable', 'string', 'max:30'],
            'general.support_email' => ['nullable', 'email', 'max:255'],
            'security.session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'security.password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
            'security.allow_public_registration' => ['required', 'boolean'],
            'finance.minimum_initial_payment' => ['required', 'integer', 'min:0'],
            'finance.financial_blocking_enabled' => ['required', 'boolean'],
        ];
    }
}

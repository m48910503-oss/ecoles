<?php

namespace App\Http\Requests\Core;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;

class SwitchSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School && ($this->user()?->can('switch', $school) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

<?php

namespace App\Http\Requests\Academic;

use App\Models\CalendarEvent;
use Illuminate\Foundation\Http\FormRequest;

class PublishCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event ? ($this->user()?->can('publish', $event) ?? false) : false;
    }

    public function rules(): array
    {
        return [];
    }
}

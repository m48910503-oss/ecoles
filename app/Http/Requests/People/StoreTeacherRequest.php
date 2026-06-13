<?php

namespace App\Http\Requests\People;

use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicSubject;
use App\Models\Teacher;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Teacher::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'academic_cycle_id' => $this->filled('academic_cycle_id') ? (int) $this->input('academic_cycle_id') : null,
            'main_classroom_id' => $this->filled('main_classroom_id') ? (int) $this->input('main_classroom_id') : null,
            'main_subject_id' => $this->filled('main_subject_id') ? (int) $this->input('main_subject_id') : null,
            'status' => $this->filled('status') ? trim((string) $this->input('status')) : 'actif',
            'contract_type' => $this->filled('contract_type') ? trim((string) $this->input('contract_type')) : 'contractuel',
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'specialty' => $this->filled('specialty') ? trim((string) $this->input('specialty')) : null,
            'diploma' => $this->filled('diploma') ? trim((string) $this->input('diploma')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_cycle_id' => [
                'required',
                'integer',
                Rule::exists('academic_cycles', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'main_classroom_id' => ['nullable', 'integer', 'exists:academic_classrooms,id'],
            'main_subject_id' => ['nullable', 'integer', 'exists:academic_subjects,id'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_names' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(array_keys(Teacher::genderOptions()))],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'diploma' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['required', Rule::in(array_keys(Teacher::contractOptions()))],
            'status' => ['required', Rule::in(array_keys(Teacher::statusOptions()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schoolId = app(SchoolContext::class)->current()->id;
            $cycle = AcademicCycle::query()
                ->where('is_active', true)
                ->find($this->integer('academic_cycle_id'));

            if (! $cycle) {
                $validator->errors()->add('academic_cycle_id', __('people.teachers.validation.cycle_invalid'));

                return;
            }

            $track = $cycle->category?->teacherTrack();

            if ($track === 'primary') {
                if (! $this->filled('main_classroom_id')) {
                    $validator->errors()->add('main_classroom_id', __('people.teachers.validation.classroom_required'));

                    return;
                }

                $classroom = AcademicClassroom::query()
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->with('level.cycle')
                    ->find($this->integer('main_classroom_id'));

                if (! $classroom) {
                    $validator->errors()->add('main_classroom_id', __('people.teachers.validation.classroom_invalid'));

                    return;
                }

                if ((int) $classroom->level?->cycle_id !== (int) $cycle->id) {
                    $validator->errors()->add('main_classroom_id', __('people.teachers.validation.classroom_cycle_mismatch'));
                }
            }

            if ($track === 'secondary') {
                if (! $this->filled('main_subject_id')) {
                    $validator->errors()->add('main_subject_id', __('people.teachers.validation.subject_required'));

                    return;
                }

                $subject = AcademicSubject::query()
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->find($this->integer('main_subject_id'));

                if (! $subject) {
                    $validator->errors()->add('main_subject_id', __('people.teachers.validation.subject_invalid'));
                }
            }
        });
    }
}

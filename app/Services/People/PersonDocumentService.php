<?php

namespace App\Services\People;

use App\Models\Guardian;
use App\Models\PersonDocument;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Core\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonDocumentService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function attachToStudent(Student $student, array $validated, User $actor): PersonDocument
    {
        return $this->attach($student, $validated, $actor);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function attachToParent(Guardian $parent, array $validated, User $actor): PersonDocument
    {
        return $this->attach($parent, $validated, $actor);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function attachToTeacher(Teacher $teacher, array $validated, User $actor): PersonDocument
    {
        return $this->attach($teacher, $validated, $actor);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function attachToStaff(Staff $staff, array $validated, User $actor): PersonDocument
    {
        return $this->attach($staff, $validated, $actor);
    }

    public function remove(PersonDocument $document, User $actor): void
    {
        $document->loadMissing('documentable');
        $documentable = $document->documentable;
        $oldValues = $this->auditValues($document);
        $filePath = $document->file_path;

        DB::transaction(function () use ($document, $documentable, $oldValues, $actor): void {
            $document->delete();

            $this->auditLogService->log(
                module: 'people',
                action: 'person.document.removed',
                auditable: $document,
                oldValues: $oldValues,
                newValues: [
                    'document_deleted' => true,
                ],
                description: 'Suppression du document '.$document->documentTypeLabel().' pour le dossier '.($documentable?->fullName() ?? 'inconnu').'.',
                user: $actor,
            );
        });

        Storage::disk('local')->delete($filePath);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function attach(Student|Guardian|Staff|Teacher $person, array $validated, User $actor): PersonDocument
    {
        $file = Arr::get($validated, 'document_file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'document_file' => __('people.documents.validation.file_required'),
            ]);
        }

        $path = $this->storeFile($person, $file);

        try {
            $document = DB::transaction(function () use ($person, $validated, $actor, $file, $path): PersonDocument {
                $document = $person->documents()->create([
                    'school_id' => (int) $person->school_id,
                    'document_type' => (string) Arr::get($validated, 'document_type'),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => (int) $file->getSize(),
                    'is_sensitive' => (bool) Arr::get($validated, 'is_sensitive', true),
                    'notes' => Arr::get($validated, 'notes') ?: null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $document->load(['uploader:id,name', 'documentable']);

                $this->auditLogService->log(
                    module: 'people',
                    action: 'person.document.attached',
                    auditable: $document,
                    newValues: $this->auditValues($document),
                    description: 'Ajout du document '.$document->documentTypeLabel().' au dossier '.($person->fullName()).'.',
                    user: $actor,
                );

                return $document;
            });
        } catch (Throwable $throwable) {
            Storage::disk('local')->delete($path);

            throw $throwable;
        }

        return $document;
    }

    private function storeFile(Student|Guardian|Staff|Teacher $person, UploadedFile $file): string
    {
        $folder = $person instanceof Student ? 'students' : ($person instanceof Guardian ? 'parents' : ($person instanceof Staff ? 'staff' : 'teachers'));
        $directory = "people/documents/{$person->school_id}/{$folder}/{$person->id}";
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.strtolower($extension);

        $storedPath = $file->storeAs($directory, $filename, 'local');

        if (! is_string($storedPath) || $storedPath === '') {
            throw ValidationException::withMessages([
                'document_file' => __('people.documents.validation.upload_failed'),
            ]);
        }

        return $storedPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(PersonDocument $document): array
    {
        return [
            'id' => $document->id,
            'school_id' => $document->school_id,
            'documentable_type' => $document->documentable_type,
            'documentable_id' => $document->documentable_id,
            'document_type' => $document->document_type,
            'original_name' => $document->original_name,
            'file_path' => $document->file_path,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'is_sensitive' => $document->is_sensitive,
            'notes' => $document->notes,
        ];
    }
}

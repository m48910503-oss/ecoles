<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonDocumentRequest;
use App\Models\Guardian;
use App\Models\PersonDocument;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Core\SchoolContext;
use App\Services\People\PersonDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonDocumentController extends Controller
{
    public function __construct(
        private readonly PersonDocumentService $personDocumentService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function storeForStudent(StorePersonDocumentRequest $request, Student $student): RedirectResponse
    {
        $this->personDocumentService->attachToStudent($student, $request->validated(), $request->user());

        return redirect()
            ->to(route('people.students.show', $student).'#documents')
            ->with('success', __('people.documents.messages.attached'));
    }

    public function storeForParent(StorePersonDocumentRequest $request, Guardian $parent): RedirectResponse
    {
        $this->personDocumentService->attachToParent($parent, $request->validated(), $request->user());

        return redirect()
            ->to(route('people.parents.show', $parent).'#documents')
            ->with('success', __('people.documents.messages.attached'));
    }

    public function storeForTeacher(StorePersonDocumentRequest $request, Teacher $teacher): RedirectResponse
    {
        $this->personDocumentService->attachToTeacher($teacher, $request->validated(), $request->user());

        return redirect()
            ->to(route('people.teachers.show', $teacher).'#documents')
            ->with('success', __('people.documents.messages.attached'));
    }

    public function storeForStaff(StorePersonDocumentRequest $request, Staff $staff): RedirectResponse
    {
        $this->personDocumentService->attachToStaff($staff, $request->validated(), $request->user());

        return redirect()
            ->to(route('people.staff.show', $staff).'#documents')
            ->with('success', __('people.documents.messages.attached'));
    }

    public function download(Request $request, PersonDocument $document): StreamedResponse
    {
        $document->loadMissing('documentable');
        $this->authorizeDocumentAccess($request, $document, 'viewDocuments');

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function destroy(Request $request, PersonDocument $document): RedirectResponse
    {
        $document->loadMissing('documentable');
        $this->authorizeDocumentAccess($request, $document, 'removeDocument');

        $documentable = $document->documentable;
        $this->personDocumentService->remove($document, $request->user());

        return redirect()
            ->to($this->redirectRouteFor($documentable).'#documents')
            ->with('success', __('people.documents.messages.removed'));
    }

    private function authorizeDocumentAccess(Request $request, PersonDocument $document, string $ability): void
    {
        $documentable = $document->documentable;

        abort_unless($documentable instanceof Student || $documentable instanceof Guardian || $documentable instanceof Staff || $documentable instanceof Teacher, 404);
        abort_unless((int) $document->school_id === (int) $this->schoolContext->current()->id, 404);
        abort_unless($request->user()?->can($ability, $documentable) ?? false, 403);
    }

    private function redirectRouteFor(Student|Guardian|Staff|Teacher $documentable): string
    {
        return route(match (true) {
            $documentable instanceof Student => 'people.students.show',
            $documentable instanceof Guardian => 'people.parents.show',
            $documentable instanceof Staff => 'people.staff.show',
            default => 'people.teachers.show',
        }, $documentable);
    }
}

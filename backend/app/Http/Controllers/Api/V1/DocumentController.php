<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\SaveProfilePhotoAction;
use App\Actions\Documents\StoreDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Requests\Documents\StoreProfilePhotoRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Candidate;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Candidate $candidate): AnonymousResourceCollection
    {
        return DocumentResource::collection(
            $candidate->documents()->latest()->get()
        );
    }

    public function store(
        StoreDocumentRequest $request,
        Candidate $candidate,
        StoreDocumentAction $action
    ): JsonResponse {
        $document = $action->execute(
            $candidate,
            $request->file('file'),
            $request->string('type')->toString(),
            $request->user()
        );

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    /**
     * Zapis wyciętego (CropperJS) zdjęcia profilowego.
     */
    public function storeProfilePhoto(
        StoreProfilePhotoRequest $request,
        Candidate $candidate,
        SaveProfilePhotoAction $action
    ): JsonResponse {
        $document = $action->execute($candidate, $request->file('photo'), $request->user());

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    /**
     * Pobranie dokumentu: najpierw tymczasowy signed URL (gdy dysk wspiera),
     * w razie braku wsparcia fallback do stream download. Zawsze audytowane
     * i uwierzytelnione (dokumenty nigdy nie są publiczne — RODO).
     */
    public function download(Candidate $candidate, Document $document): StreamedResponse|RedirectResponse
    {
        abort_unless($document->candidate_id === $candidate->id, 404);

        $document->logActivity('downloaded');

        $disk = Storage::disk($document->disk);

        try {
            $url = $disk->temporaryUrl($document->path, now()->addMinutes(5));

            return redirect($url);
        } catch (\Throwable) {
            // MEGA S3 / dysk lokalny bez wsparcia signed URL → strumień.
            return $disk->download($document->path, $document->original_name);
        }
    }

    public function destroy(Candidate $candidate, Document $document): JsonResponse
    {
        abort_unless($document->candidate_id === $candidate->id, 404);

        $document->delete();

        return response()->json(['message' => 'Dokument usunięty.']);
    }

    /**
     * Usunięcie zdjęcia profilowego kandydata.
     */
    public function destroyProfilePhoto(Candidate $candidate): JsonResponse
    {
        $photoId = $candidate->profile_photo_id;

        $candidate->forceFill(['profile_photo_id' => null])->save();

        if ($photoId) {
            Document::where('id', $photoId)->delete();
        }

        return response()->json(['message' => 'Zdjęcie profilowe usunięte.']);
    }
}

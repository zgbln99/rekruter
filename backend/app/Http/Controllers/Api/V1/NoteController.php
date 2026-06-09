<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Osobisty notatnik rekrutera — operuje wyłącznie na notatkach zalogowanego
 * użytkownika (prywatne). Tenant pilnuje globalny scope.
 */
class NoteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notes = $this->own($request)
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        return NoteResource::collection($notes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateNote($request);

        $note = Note::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'pinned' => $data['pinned'] ?? false,
            'color' => $data['color'] ?? null,
        ]);

        return (new NoteResource($note))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $note): NoteResource
    {
        $model = $this->own($request)->findOrFail($note);
        $model->update($this->validateNote($request));

        return new NoteResource($model->refresh());
    }

    public function destroy(Request $request, string $note): JsonResponse
    {
        $this->own($request)->findOrFail($note)->delete();

        return response()->json(['message' => 'Notatka usunięta.']);
    }

    /** Zapytanie ograniczone do notatek bieżącego użytkownika. */
    private function own(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return Note::query()->where('user_id', $request->user()->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateNote(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
            'body' => ['nullable', 'string', 'max:20000'],
            'pinned' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
    }
}

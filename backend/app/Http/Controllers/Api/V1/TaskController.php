<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * Lista zadań. Domyślnie: moje, otwarte. Filtry `scope` i `filter`.
     *
     * scope:  me (domyślnie) | all
     * filter: today | overdue | upcoming | open (domyślnie today)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Task::query()->with('candidate');

        if ($request->string('scope')->toString() !== 'all') {
            $query->where('assigned_to', $request->user()->id);
        }

        $filter = $request->string('filter')->toString() ?: 'today';

        match ($filter) {
            'overdue' => $query->where('status', TaskStatus::Open)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()->startOfDay()),
            'upcoming' => $query->where('status', TaskStatus::Open)
                ->where('due_at', '>', now()->endOfDay()),
            'open' => $query->where('status', TaskStatus::Open),
            // „today" = otwarte i zaległe do końca dzisiejszego dnia.
            default => $query->where('status', TaskStatus::Open)
                ->where(function ($q) {
                    $q->whereNull('due_at')->orWhere('due_at', '<=', now()->endOfDay());
                }),
        };

        return TaskResource::collection(
            $query->orderByRaw('due_at asc nulls last')->get()
        );
    }

    /** Utworzenie zadania / przypomnienia dla kandydata. */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();

        $assignee = $data['assigned_to'] ?? $request->user()->id;

        $task = Task::create([
            'candidate_id' => $data['candidate_id'],
            'assigned_to' => $assignee,
            'created_by' => $request->user()->id,
            'type' => $data['type'] ?? 'follow_up',
            'status' => 'open',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]);

        // Push do osoby przypisanej (jeśli to ktoś inny niż twórca).
        if ($assignee !== $request->user()->id) {
            $target = \App\Models\User::find($assignee);
            if ($target) {
                app(\App\Support\Push\WebPushService::class)
                    ->sendToUser($target, 'Nowe zadanie', $task->title, $task->candidate_id ? '/candidates/'.$task->candidate_id : '/');
            }
        }

        return (new TaskResource($task->load('candidate')))->response()->setStatusCode(201);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $data = $request->validated();
        // Przejście statusu na „done" ustawia znacznik wykonania.
        if (isset($data['status'])) {
            $status = TaskStatus::from($data['status']);
            $task->status = $status;
            $task->completed_at = $status === TaskStatus::Done ? now() : null;
        }

        foreach (['title', 'description', 'due_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $task->{$field} = $data[$field];
            }
        }

        $task->save();

        return new TaskResource($task->fresh('candidate'));
    }
}

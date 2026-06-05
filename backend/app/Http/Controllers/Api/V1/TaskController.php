<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
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

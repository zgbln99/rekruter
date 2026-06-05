<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pipeline\AddCandidateToPipelineAction;
use App\Actions\Pipeline\MoveApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Applications\MoveApplicationRequest;
use App\Http\Requests\Applications\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\JsonResponse;

class ApplicationController extends Controller
{
    public function store(
        StoreApplicationRequest $request,
        AddCandidateToPipelineAction $action
    ): JsonResponse {
        $application = $action->execute($request->validated());

        return (new ApplicationResource($application->load('candidate')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        MoveApplicationRequest $request,
        Application $application,
        MoveApplicationAction $action
    ): ApplicationResource {
        $action->execute(
            $application,
            $request->string('status')->toString(),
            $request->has('position') ? $request->integer('position') : null
        );

        if ($request->filled('notes')) {
            $application->update(['notes' => $request->string('notes')->toString()]);
        }

        return new ApplicationResource($application->fresh('candidate'));
    }

    public function destroy(Application $application): JsonResponse
    {
        $application->delete();

        return response()->json(['message' => 'Kandydat usunięty z pipeline.']);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Contacts\LogContactAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreContactLogRequest;
use App\Http\Resources\ContactLogResource;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactLogController extends Controller
{
    public function index(Candidate $candidate): AnonymousResourceCollection
    {
        return ContactLogResource::collection(
            $candidate->contactLogs()->with('user')->latest('contacted_at')->get()
        );
    }

    public function store(
        StoreContactLogRequest $request,
        Candidate $candidate,
        LogContactAction $action
    ): JsonResponse {
        $contact = $action->execute($candidate, $request->validated(), $request->user());

        return (new ContactLogResource($contact->load('user')))
            ->response()
            ->setStatusCode(201);
    }
}

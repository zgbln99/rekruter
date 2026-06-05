<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\DashboardStatsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStatsAction $action): JsonResponse
    {
        return response()->json($action->execute($request->user()));
    }
}

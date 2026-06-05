<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InstallmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlacementInstallmentResource;
use App\Models\PlacementInstallment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Edycja rat rozliczenia — tylko administrator (dane finansowe agencji).
 */
class PlacementInstallmentController extends Controller
{
    public function update(Request $request, PlacementInstallment $placementInstallment): PlacementInstallmentResource
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(InstallmentStatus::class)],
            'due_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'invoiced_at' => ['sometimes', 'nullable', 'date'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ]);

        // Automatyczne znaczniki dat przy zmianie statusu (jeśli nie podano ręcznie).
        if (($validated['status'] ?? null) === InstallmentStatus::Invoiced->value
            && empty($validated['invoiced_at']) && ! $placementInstallment->invoiced_at) {
            $validated['invoiced_at'] = now()->toDateString();
        }
        if (($validated['status'] ?? null) === InstallmentStatus::Paid->value
            && empty($validated['paid_at']) && ! $placementInstallment->paid_at) {
            $validated['paid_at'] = now()->toDateString();
        }

        $placementInstallment->update($validated);

        return new PlacementInstallmentResource($placementInstallment);
    }
}

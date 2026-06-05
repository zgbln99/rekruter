<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PlacementInstallment
 */
class PlacementInstallmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'placement_id' => $this->placement_id,
            'sequence' => $this->sequence,
            'due_date' => optional($this->due_date)->toDateString(),
            'amount' => $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'invoiced_at' => optional($this->invoiced_at)->toDateString(),
            'paid_at' => optional($this->paid_at)->toDateString(),
        ];
    }
}

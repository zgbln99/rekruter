<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ContactLog
 */
class ContactLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'outcome' => $this->outcome->value,
            'outcome_label' => $this->outcome->label(),
            'note' => $this->note,
            'contacted_at' => $this->contacted_at?->toIso8601String(),
            'next_contact_at' => $this->next_contact_at?->toIso8601String(),
            'task_id' => $this->task_id,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

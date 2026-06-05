<?php

namespace App\Actions\Rodo;

use App\Models\Activity;
use App\Models\Candidate;

/**
 * Eksport wszystkich danych kandydata (RODO — prawo dostępu, art. 15).
 * Zwraca komplet danych osobowych w formie strukturalnej (JSON).
 */
class ExportCandidateAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Candidate $candidate): array
    {
        $candidate->load(['contactLogs.user', 'tasks', 'documents', 'applications.jobPosting']);

        $activities = Activity::where('subject_type', $candidate->getMorphClass())
            ->where('subject_id', $candidate->id)
            ->latest('created_at')
            ->get(['event', 'changes', 'created_at']);

        return [
            'exported_at' => now()->toIso8601String(),
            'candidate' => $candidate->only([
                'id', 'first_name', 'last_name', 'phone', 'phone_normalized',
                'email', 'city', 'country', 'status', 'license_categories',
                'has_adr', 'adr_expiry', 'has_code_95', 'code_95_expiry',
                'driver_card_expiry', 'source', 'consent_rodo_at',
                'internal_notes', 'created_at',
            ]),
            'contact_logs' => $candidate->contactLogs->map(fn ($c) => [
                'channel' => $c->channel->value,
                'outcome' => $c->outcome->value,
                'note' => $c->note,
                'contacted_at' => $c->contacted_at?->toIso8601String(),
                'by' => $c->user?->name,
            ]),
            'tasks' => $candidate->tasks->map(fn ($t) => [
                'title' => $t->title,
                'status' => $t->status->value,
                'due_at' => $t->due_at?->toIso8601String(),
            ]),
            'documents' => $candidate->documents->map(fn ($d) => [
                'type' => $d->type->value,
                'original_name' => $d->original_name,
                'size' => $d->size,
                'uploaded_at' => $d->created_at?->toIso8601String(),
            ]),
            'applications' => $candidate->applications->map(fn ($a) => [
                'job_posting' => $a->jobPosting?->title,
            ]),
            'activities' => $activities,
        ];
    }
}

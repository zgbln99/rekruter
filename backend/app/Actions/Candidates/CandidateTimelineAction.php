<?php

namespace App\Actions\Candidates;

use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\ProfileSend;

/**
 * Buduje chronologiczny timeline kandydata z audit logu (`activities`),
 * agregując zdarzenia kandydata oraz encji powiązanych (DESIGN.md 19.12).
 */
class CandidateTimelineAction
{
    /**
     * @return array<int, array{at: ?string, type: string, label: string}>
     */
    public function execute(Candidate $candidate): array
    {
        $documentIds = $candidate->documents()->withTrashed()->pluck('id')->all();
        $applicationIds = $candidate->applications()->pluck('id')->all();
        $sendIds = $candidate->profileSends()->pluck('id')->all();

        $activities = Activity::query()
            ->with('user')
            ->where(function ($q) use ($candidate, $documentIds, $applicationIds, $sendIds) {
                $q->where(fn ($s) => $s->where('subject_type', $candidate->getMorphClass())->where('subject_id', $candidate->id));
                if ($documentIds) {
                    $q->orWhere(fn ($s) => $s->where('subject_type', (new Document)->getMorphClass())->whereIn('subject_id', $documentIds));
                }
                if ($applicationIds) {
                    $q->orWhere(fn ($s) => $s->where('subject_type', (new Application)->getMorphClass())->whereIn('subject_id', $applicationIds));
                }
                if ($sendIds) {
                    $q->orWhere(fn ($s) => $s->where('subject_type', (new ProfileSend)->getMorphClass())->whereIn('subject_id', $sendIds));
                }
            })
            ->latest('created_at')
            ->limit(200)
            ->get();

        return $activities->map(fn (Activity $a) => [
            'at' => $a->created_at?->toIso8601String(),
            'type' => $a->event,
            'label' => $this->label($a),
            'by' => $a->user?->name,
        ])->all();
    }

    private function label(Activity $a): string
    {
        $subject = class_basename($a->subject_type);
        $changes = $a->changes ?? [];

        return match ("{$subject}.{$a->event}") {
            'Candidate.created' => 'Utworzono kandydata',
            'Candidate.assigned_to_offer' => 'Przypisano do ogłoszenia: '.($changes['title'] ?? ''),
            'Candidate.photo_set' => 'Ustawiono zdjęcie profilowe',
            'Candidate.pdf_generated' => 'Wygenerowano PDF',
            'Candidate.exported' => 'Eksport danych (RODO)',
            'Candidate.updated' => 'Zaktualizowano dane',
            'Document.created' => 'Dodano dokument',
            'Document.downloaded' => 'Pobrano dokument',
            'Application.created' => 'Dodano do ogłoszenia',
            'Application.status_changed' => 'Zmieniono status: '.($changes['to'] ?? ''),
            'ProfileSend.created' => 'Przygotowano wysyłkę profilu',
            'ProfileSend.sent' => 'Wysłano profil do firmy',
            'ProfileSend.decision' => 'Decyzja firmy: '.($changes['decision'] ?? ''),
            default => $a->event,
        };
    }
}

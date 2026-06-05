<?php

namespace App\Actions\Candidates;

use App\Models\Candidate;

/**
 * Liczy w locie kompletność profilu kandydata oraz braki do wysłania profilu
 * do firmy (DESIGN.md 19.9).
 */
class CandidateCompletenessAction
{
    /**
     * @return array{items: array<int, array{key: string, label: string, done: bool}>, missing: array<int, string>, complete: bool, percent: int}
     */
    public function execute(Candidate $candidate): array
    {
        $candidate->loadCount(['documents', 'applications', 'profileSends']);

        $hasLicenses = ! empty($candidate->license_categories)
            || $candidate->has_adr || $candidate->has_code_95;
        $hasExperience = $candidate->exp_reefer || $candidate->exp_tilt
            || $candidate->exp_international || ! empty($candidate->experience_notes);
        $hasPdf = $candidate->profileSends()->whereNotNull('pdf_path')->exists();

        $items = [
            ['key' => 'basic_data', 'label' => 'Dane podstawowe', 'done' => $candidate->first_name !== null],
            ['key' => 'phone', 'label' => 'Telefon', 'done' => ! empty($candidate->phone)],
            ['key' => 'offer', 'label' => 'Przypisane ogłoszenie', 'done' => $candidate->applications_count > 0],
            ['key' => 'experience', 'label' => 'Doświadczenie', 'done' => $hasExperience],
            ['key' => 'licenses', 'label' => 'Uprawnienia', 'done' => $hasLicenses],
            ['key' => 'documents', 'label' => 'Dokumenty', 'done' => $candidate->documents_count > 0],
            ['key' => 'photo', 'label' => 'Zdjęcie', 'done' => $candidate->profile_photo_id !== null],
            ['key' => 'pdf', 'label' => 'Gotowy PDF', 'done' => $hasPdf],
        ];

        // Braki krytyczne dla wysłania profilu do firmy.
        $missingMap = [
            'offer' => 'brak przypisanego ogłoszenia',
            'licenses' => 'brak uprawnień',
            'experience' => 'brak doświadczenia',
            'photo' => 'brak zdjęcia',
        ];
        $missing = [];
        foreach ($items as $item) {
            if (isset($missingMap[$item['key']]) && ! $item['done']) {
                $missing[] = $missingMap[$item['key']];
            }
        }

        $done = count(array_filter($items, fn ($i) => $i['done']));
        $percent = (int) round($done / count($items) * 100);

        return [
            'items' => $items,
            'missing' => $missing,
            'complete' => $missing === [],
            'percent' => $percent,
        ];
    }
}

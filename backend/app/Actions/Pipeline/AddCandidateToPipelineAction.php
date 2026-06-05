<?php

namespace App\Actions\Pipeline;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Validation\ValidationException;

/**
 * Dodaje kandydata do pipeline danego ogłoszenia ze statusem startowym `new`
 * (lub wskazanym). Kandydat może być w ogłoszeniu tylko raz.
 */
class AddCandidateToPipelineAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Application
    {
        $status = $data['status'] ?? ApplicationStatus::New->value;

        $exists = Application::where('candidate_id', $data['candidate_id'])
            ->where('job_posting_id', $data['job_posting_id'])
            ->first();

        if ($exists !== null) {
            throw ValidationException::withMessages([
                'candidate_id' => ['Kandydat jest już przypisany do tego ogłoszenia.'],
            ]);
        }

        $position = (int) Application::where('job_posting_id', $data['job_posting_id'])
            ->where('status', $status)
            ->max('position');

        return Application::create([
            'candidate_id' => $data['candidate_id'],
            'job_posting_id' => $data['job_posting_id'],
            'status' => $status,
            'position' => $position + 1,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}

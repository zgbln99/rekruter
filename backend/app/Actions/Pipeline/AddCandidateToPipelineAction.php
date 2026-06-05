<?php

namespace App\Actions\Pipeline;

use App\Models\Application;
use App\Models\PipelineStage;
use Illuminate\Validation\ValidationException;

/**
 * Dodaje kandydata do pipeline danego ogłoszenia. Domyślnie trafia do
 * pierwszego etapu i na koniec kolumny.
 */
class AddCandidateToPipelineAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Application
    {
        $stageId = $data['stage_id'] ?? optional(
            PipelineStage::orderBy('position')->first()
        )->id;

        if ($stageId === null) {
            throw ValidationException::withMessages([
                'stage_id' => ['Brak zdefiniowanych etapów pipeline.'],
            ]);
        }

        $exists = Application::where('candidate_id', $data['candidate_id'])
            ->where('job_posting_id', $data['job_posting_id'])
            ->first();

        if ($exists !== null) {
            throw ValidationException::withMessages([
                'candidate_id' => ['Kandydat jest już przypisany do tego ogłoszenia.'],
            ]);
        }

        $position = (int) Application::where('job_posting_id', $data['job_posting_id'])
            ->where('stage_id', $stageId)
            ->max('position');

        return Application::create([
            'candidate_id' => $data['candidate_id'],
            'job_posting_id' => $data['job_posting_id'],
            'stage_id' => $stageId,
            'position' => $position + 1,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}

<?php

namespace App\Actions\Pipeline;

use App\Models\Application;

/**
 * Przenosi aplikację do innego etapu (drag&drop / bottom-sheet w UI).
 * Zapisuje pozycję w kolumnie i loguje zdarzenie w audit logu.
 */
class MoveApplicationAction
{
    public function execute(Application $application, string $stageId, ?int $position = null): Application
    {
        $fromStage = $application->stage_id;

        if ($position === null) {
            $position = (int) Application::where('job_posting_id', $application->job_posting_id)
                ->where('stage_id', $stageId)
                ->max('position') + 1;
        }

        $application->forceFill([
            'stage_id' => $stageId,
            'position' => $position,
        ])->save();

        if ($fromStage !== $stageId) {
            $application->logActivity('moved', [
                'from_stage' => $fromStage,
                'to_stage' => $stageId,
            ]);
        }

        return $application;
    }
}

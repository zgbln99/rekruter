<?php

namespace App\Actions\Pipeline;

use App\Models\Application;

/**
 * Zmienia status kandydata w ramach ogłoszenia (drag&drop / bottom-sheet w UI).
 * Zapisuje pozycję w kolumnie i loguje zdarzenie w audit logu.
 */
class MoveApplicationAction
{
    public function execute(Application $application, string $status, ?int $position = null): Application
    {
        $fromStatus = $application->status?->value;

        if ($position === null) {
            $position = (int) Application::where('job_posting_id', $application->job_posting_id)
                ->where('status', $status)
                ->max('position') + 1;
        }

        $application->forceFill([
            'status' => $status,
            'position' => $position,
        ])->save();

        if ($fromStatus !== $status) {
            $application->logActivity('status_changed', [
                'from' => $fromStatus,
                'to' => $status,
            ]);
        }

        return $application;
    }
}

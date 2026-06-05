<?php

namespace App\Actions\Candidates;

use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;

/**
 * Łączy duplikat ($source) z kandydatem docelowym ($target):
 * przenosi powiązania (dokumenty, kontakty, zadania, skierowania, aplikacje,
 * wysyłki profilu, audyt), uzupełnia puste pola docelowego danymi źródła,
 * a źródłowego kandydata usuwa (soft delete).
 */
class MergeCandidatesAction
{
    /** Pola skalarne uzupełniane, gdy w docelowym puste. */
    private const SCALARS = [
        'email', 'city', 'country', 'address', 'date_of_birth', 'nationality',
        'availability_from', 'experience_notes', 'source', 'internal_notes',
        'adr_expiry', 'code_95_expiry', 'driver_card_expiry',
    ];

    private const BOOLS = [
        'has_adr', 'has_code_95', 'has_hds', 'exp_reefer', 'exp_tilt',
        'exp_international', 'lang_de', 'lang_en',
    ];

    public function execute(Candidate $target, Candidate $source): Candidate
    {
        return DB::transaction(function () use ($target, $source) {
            // Proste powiązania 1:N.
            $source->documents()->update(['candidate_id' => $target->id]);
            $source->contactLogs()->update(['candidate_id' => $target->id]);
            $source->tasks()->update(['candidate_id' => $target->id]);
            $source->profileSends()->update(['candidate_id' => $target->id]);
            $source->placements()->update(['candidate_id' => $target->id]);

            // Aplikacje — unikalność (candidate_id, job_posting_id).
            $targetPostings = $target->applications()->pluck('job_posting_id')->all();
            foreach ($source->applications()->get() as $app) {
                if (in_array($app->job_posting_id, $targetPostings, true)) {
                    $app->delete();
                } else {
                    $app->update(['candidate_id' => $target->id]);
                }
            }

            // Audyt kandydata.
            Activity::where('subject_type', Candidate::class)
                ->where('subject_id', $source->id)
                ->update(['subject_id' => $target->id]);

            // Uzupełnij puste pola docelowego.
            foreach (self::SCALARS as $f) {
                if (blank($target->{$f}) && filled($source->{$f})) {
                    $target->{$f} = $source->{$f};
                }
            }
            foreach (self::BOOLS as $f) {
                if (! $target->{$f} && $source->{$f}) {
                    $target->{$f} = true;
                }
            }
            if (blank($target->license_categories) && filled($source->license_categories)) {
                $target->license_categories = $source->license_categories;
            }
            if (blank($target->work_history) && filled($source->work_history)) {
                $target->work_history = $source->work_history;
            }
            if (! $target->profile_photo_id && $source->profile_photo_id) {
                $target->profile_photo_id = $source->profile_photo_id;
            }
            $target->save();

            // Usuń źródło (soft delete).
            $source->delete();

            return $target->fresh(['applications', 'documents']);
        });
    }
}

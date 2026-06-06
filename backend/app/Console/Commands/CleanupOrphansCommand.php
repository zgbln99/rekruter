<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\ContactLog;
use App\Models\Document;
use App\Models\Placement;
use App\Models\PlacementInstallment;
use App\Models\ProfileSend;
use App\Models\Task;
use App\Support\Tenancy\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Usuwa osierocone rekordy po usuniętych (soft-delete) kandydatach:
 * zadania, skierowania + raty, aplikacje, kontakty, wysyłki profilu, dokumenty.
 *
 * Sierota = rekord, którego candidate_id nie wskazuje na aktywnego (nieusuniętego)
 * kandydata. Dotyczy kandydatów usuniętych przed poprawką kaskady.
 *
 * Użycie:
 *   php artisan rekruter:cleanup-orphans          (podgląd liczby sierot)
 *   php artisan rekruter:cleanup-orphans --force  (usuwa)
 */
class CleanupOrphansCommand extends Command
{
    protected $signature = 'rekruter:cleanup-orphans {--force : Wykonaj usunięcie (bez tego tylko podgląd)}';

    protected $description = 'Usuwa osierocone rekordy po usuniętych kandydatach';

    /** @var array<int, string> */
    private array $activeIds = [];

    public function handle(): int
    {
        // Aktywni (nieusunięci) kandydaci — wszystko inne to sieroty.
        $this->activeIds = Candidate::withoutGlobalScope(TenantScope::class)->pluck('id')->all();

        $placements = $this->orphans(Placement::withoutGlobalScope(TenantScope::class)->withTrashed())->get();
        $placementIds = $placements->pluck('id');

        $counts = [
            'skierowania' => $placements->count(),
            'raty' => PlacementInstallment::withoutGlobalScope(TenantScope::class)->whereIn('placement_id', $placementIds)->count(),
            'zadania' => $this->orphans(Task::withoutGlobalScope(TenantScope::class)->withTrashed())->count(),
            'kontakty' => $this->orphans(ContactLog::withoutGlobalScope(TenantScope::class))->count(),
            'aplikacje' => $this->orphans(Application::withoutGlobalScope(TenantScope::class))->count(),
            'wysyłki' => $this->orphans(ProfileSend::withoutGlobalScope(TenantScope::class))->count(),
            'dokumenty' => $this->orphans(Document::withoutGlobalScope(TenantScope::class)->withTrashed())->count(),
        ];

        $total = array_sum($counts);
        $this->info('Znalezione sieroty:');
        foreach ($counts as $k => $v) {
            $this->line(sprintf('  %-12s %d', $k.':', $v));
        }

        if ($total === 0) {
            $this->info('Brak sierot — nic do usunięcia.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn('Podgląd. Aby usunąć, uruchom z flagą --force');

            return self::SUCCESS;
        }

        // Usuń pliki ze storage.
        $disk = Storage::disk(config('rekruter.documents_disk'));
        foreach ($this->orphans(Document::withoutGlobalScope(TenantScope::class)->withTrashed())->get() as $doc) {
            try {
                Storage::disk($doc->disk)->delete($doc->path);
            } catch (\Throwable $e) {
            }
        }
        foreach ($this->orphans(ProfileSend::withoutGlobalScope(TenantScope::class))->whereNotNull('pdf_path')->pluck('pdf_path') as $pdf) {
            try {
                $disk->delete($pdf);
            } catch (\Throwable $e) {
            }
        }

        // Usuń rekordy.
        PlacementInstallment::withoutGlobalScope(TenantScope::class)->whereIn('placement_id', $placementIds)->delete();
        foreach ($placements as $p) {
            $p->forceDelete();
        }
        $this->orphans(Task::withoutGlobalScope(TenantScope::class)->withTrashed())->forceDelete();
        $this->orphans(ContactLog::withoutGlobalScope(TenantScope::class))->delete();
        $this->orphans(Application::withoutGlobalScope(TenantScope::class))->delete();
        $this->orphans(ProfileSend::withoutGlobalScope(TenantScope::class))->delete();
        $this->orphans(Document::withoutGlobalScope(TenantScope::class)->withTrashed())->forceDelete();

        $this->info("Usunięto sieroty (łącznie {$total} rekordów).");

        return self::SUCCESS;
    }

    /**
     * Filtr „sierota": candidate_id spoza aktywnych kandydatów.
     * Gdy brak aktywnych — wszystkie rekordy są sierotami (brak ograniczenia).
     */
    private function orphans($query)
    {
        return empty($this->activeIds)
            ? $query
            : $query->whereNotIn('candidate_id', $this->activeIds);
    }
}

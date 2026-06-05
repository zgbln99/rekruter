<?php

namespace App\Actions\Matching;

use App\Models\Candidate;
use App\Models\JobPosting;

/**
 * Porównuje wymagania ogłoszenia z atrybutami kandydata.
 * Zwraca wynik (match / partial / no_match) oraz listę braków (PL).
 */
class MatchCandidateToOfferAction
{
    /**
     * Definicja wymagań: klucz => [etykieta braku, predykat na kandydacie].
     *
     * @return array<string, array{0: string, 1: callable(Candidate): bool}>
     */
    private function checks(): array
    {
        return [
            'c' => ['brak kat. C', fn (Candidate $c) => in_array('C', $c->license_categories ?? [], true)],
            'ce' => ['brak kat. C+E', fn (Candidate $c) => in_array('C+E', $c->license_categories ?? [], true)],
            'code_95' => ['brak Kod 95', fn (Candidate $c) => (bool) $c->has_code_95],
            'driver_card' => ['brak karty kierowcy', fn (Candidate $c) => $c->driver_card_expiry !== null],
            'adr' => ['brak ADR', fn (Candidate $c) => (bool) $c->has_adr],
            'hds' => ['brak HDS', fn (Candidate $c) => (bool) $c->has_hds],
            'exp_reefer' => ['brak doświadczenia na chłodni', fn (Candidate $c) => (bool) $c->exp_reefer],
            'exp_tilt' => ['brak doświadczenia na plandece', fn (Candidate $c) => (bool) $c->exp_tilt],
            'exp_international' => ['brak doświadczenia międzynarodowego', fn (Candidate $c) => (bool) $c->exp_international],
            'lang_de' => ['brak języka niemieckiego', fn (Candidate $c) => (bool) $c->lang_de],
            'lang_en' => ['brak języka angielskiego', fn (Candidate $c) => (bool) $c->lang_en],
        ];
    }

    /**
     * @return array{result: string, required: int, met: int, missing: array<int, string>}
     */
    public function execute(Candidate $candidate, JobPosting $offer): array
    {
        $requirements = $offer->requirements ?? [];
        $checks = $this->checks();

        $required = 0;
        $met = 0;
        $missing = [];

        foreach ($checks as $key => [$label, $predicate]) {
            if (! ($requirements[$key] ?? false)) {
                continue; // wymaganie nieaktywne w ogłoszeniu
            }
            $required++;
            if ($predicate($candidate)) {
                $met++;
            } else {
                $missing[] = $label;
            }
        }

        $result = match (true) {
            $required === 0 => 'match',      // brak wymagań → wszystko pasuje
            $met === $required => 'match',
            $met === 0 => 'no_match',
            default => 'partial',
        };

        return [
            'result' => $result,
            'required' => $required,
            'met' => $met,
            'missing' => $missing,
        ];
    }
}

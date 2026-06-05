<?php

namespace Tests\Feature\Profiles;

use App\Actions\Profiles\GenerateReferralPdfAction;
use App\Models\JobPosting;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ReferralLangTest extends TestCase
{
    /**
     * Szablon skierowania renderuje się w danym języku z przetłumaczonymi
     * etykietami (bez wywoływania Gotenberga).
     */
    public function test_referral_view_renders_localized_labels(): void
    {
        $action = new GenerateReferralPdfAction();
        $m = new \ReflectionMethod($action, 'translations');
        $m->setAccessible(true);

        $offer = new JobPosting();
        $offer->title = 'Kierowca C+E';
        $offer->work_system = '3/1';
        $offer->salary_amount = '2500';
        $offer->currency = 'EUR';

        foreach (['uk' => 'Направлення', 'de' => 'vermittlung', 'en' => 'Referral'] as $lang => $needle) {
            $html = View::make('pdf.referral', [
                'offer' => $offer,
                'company' => null,
                'agencyName' => 'Agencja',
                'candidateName' => 'Jan Kowalski',
                'arrivalOverride' => '08.07.2026 09:00',
                'recruiterName' => 'Anna',
                'recruiterPhone' => '+48 600',
                'recruiterEmail' => 'a@x.pl',
                'generatedAt' => '01.01.2026',
                'lang' => $lang,
                't' => $m->invoke($action, $lang),
            ])->render();

            $this->assertStringContainsString($needle, $html);
        }
    }
}

<?php

namespace App\Actions\Profiles;

use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\View;

/**
 * Generuje dokument „Skierowanie do pracy" dla kierowcy (z ogłoszenia).
 *
 * Obsługuje:
 *  - personalizację (kierowca + ręcznie wpisany termin przyjazdu — z Placement),
 *  - nadpisania pól z modala ($overrides — tylko na ten PDF, bez zapisu),
 *  - język dokumentu ($lang: pl|uk|ru|en|de) — tłumaczone są etykiety/nagłówki;
 *    treść wpisana przez rekruterkę pozostaje w oryginale.
 */
class GenerateReferralPdfAction
{
    private const OVERRIDABLE = [
        'title', 'country', 'region_base', 'work_system', 'vehicle_type',
        'trailer_type', 'routes_info', 'cargo', 'points_per_day', 'loading_info',
        'daily_km', 'accommodation', 'contract_type', 'salary_amount', 'currency',
        'required_language', 'onsite_contact', 'public_description',
    ];

    public const LANGS = ['pl', 'uk', 'ru', 'en', 'de'];

    public function render(
        JobPosting $offer,
        User $recruiter,
        ?Candidate $candidate = null,
        ?string $arrivalOverride = null,
        array $overrides = [],
        string $lang = 'pl'
    ): string {
        $offer->loadMissing('company');

        foreach (self::OVERRIDABLE as $field) {
            if (array_key_exists($field, $overrides) && $overrides[$field] !== null && $overrides[$field] !== '') {
                $offer->setAttribute($field, $overrides[$field]);
            }
        }

        $lang = in_array($lang, self::LANGS, true) ? $lang : 'pl';
        $candidateName = $candidate?->fullName() ?: ($overrides['candidate_name'] ?? null);

        $html = View::make('pdf.referral', [
            'offer' => $offer,
            'company' => $offer->company,
            'agencyName' => $offer->tenant?->agencyName() ?? config('app.name'),
            'candidateName' => $candidateName,
            'arrivalOverride' => $arrivalOverride,
            'recruiterName' => $recruiter->name,
            'recruiterPhone' => $recruiter->phone,
            'recruiterEmail' => $recruiter->email,
            'generatedAt' => now()->format('d.m.Y'),
            'lang' => $lang,
            't' => $this->translations($lang),
        ])->render();

        return GotenbergClient::make()->htmlToPdf($html);
    }

    /**
     * Etykiety dokumentu w wybranym języku.
     *
     * @return array<string, string>
     */
    private function translations(string $lang): array
    {
        $dict = [
            'pl' => [
                'title_main' => 'Skierowanie do', 'title_accent' => 'pracy',
                'sub' => 'Informacje dla kierowcy', 'brand_hr' => 'Rekrutacja kierowców',
                'forwho' => 'Kierowca', 'employer' => 'Pracodawca',
                'company_name' => 'Nazwa firmy', 'region' => 'Region', 'position' => 'Stanowisko',
                'f_salary' => 'Wynagrodzenie', 'f_system' => 'System pracy', 'f_arrival' => 'Data przyjazdu',
                'sec_conditions' => 'Stanowisko i warunki',
                'p_vehicle' => 'Typ auta', 'p_cargo' => 'Przewożony towar', 'p_contract' => 'Rodzaj umowy',
                'p_points' => 'Punktów dziennie', 'p_km' => 'Średni przebieg',
                'p_loading' => 'Załadunek / rozładunek', 'p_language' => 'Wymagany język',
                'sec_routes' => 'Trasy i zakwaterowanie', 'routes' => 'Trasy', 'accommodation' => 'Zakwaterowanie',
                'sec_employer' => 'Kontakt', 'about' => 'O firmie',
                'contact_onsite' => 'Kontakt na miejscu', 'contact_office' => 'Kontakt (rekrutacja)',
                'sec_extra' => 'Dodatkowe informacje', 'footer_by' => 'Dokument przygotowany przez',
            ],
            'uk' => [
                'title_main' => 'Направлення на', 'title_accent' => 'роботу',
                'sub' => 'Інформація для водія', 'brand_hr' => 'Підбір водіїв',
                'forwho' => 'Водій', 'employer' => 'Роботодавець',
                'company_name' => 'Назва компанії', 'region' => 'Регіон', 'position' => 'Посада',
                'f_salary' => 'Заробітна плата', 'f_system' => 'Графік роботи', 'f_arrival' => 'Дата приїзду',
                'sec_conditions' => 'Посада та умови',
                'p_vehicle' => 'Тип авто', 'p_cargo' => 'Вантаж', 'p_contract' => 'Тип договору',
                'p_points' => 'Точок на день', 'p_km' => 'Середній пробіг',
                'p_loading' => 'Завантаження / розвантаження', 'p_language' => 'Потрібна мова',
                'sec_routes' => 'Маршрути та проживання', 'routes' => 'Маршрути', 'accommodation' => 'Проживання',
                'sec_employer' => 'Контакт', 'about' => 'Про компанію',
                'contact_onsite' => 'Контакт на місці', 'contact_office' => 'Контакт (рекрутинг)',
                'sec_extra' => 'Додаткова інформація', 'footer_by' => 'Документ підготовлено',
            ],
            'ru' => [
                'title_main' => 'Направление на', 'title_accent' => 'работу',
                'sub' => 'Информация для водителя', 'brand_hr' => 'Подбор водителей',
                'forwho' => 'Водитель', 'employer' => 'Работодатель',
                'company_name' => 'Название компании', 'region' => 'Регион', 'position' => 'Должность',
                'f_salary' => 'Заработная плата', 'f_system' => 'График работы', 'f_arrival' => 'Дата приезда',
                'sec_conditions' => 'Должность и условия',
                'p_vehicle' => 'Тип авто', 'p_cargo' => 'Груз', 'p_contract' => 'Тип договора',
                'p_points' => 'Точек в день', 'p_km' => 'Средний пробег',
                'p_loading' => 'Погрузка / разгрузка', 'p_language' => 'Требуемый язык',
                'sec_routes' => 'Маршруты и проживание', 'routes' => 'Маршруты', 'accommodation' => 'Проживание',
                'sec_employer' => 'Контакт', 'about' => 'О компании',
                'contact_onsite' => 'Контакт на месте', 'contact_office' => 'Контакт (рекрутинг)',
                'sec_extra' => 'Дополнительная информация', 'footer_by' => 'Документ подготовлен',
            ],
            'en' => [
                'title_main' => 'Job', 'title_accent' => 'Referral',
                'sub' => 'Information for the driver', 'brand_hr' => 'Driver recruitment',
                'forwho' => 'Driver', 'employer' => 'Employer',
                'company_name' => 'Company', 'region' => 'Region', 'position' => 'Position',
                'f_salary' => 'Salary', 'f_system' => 'Work schedule', 'f_arrival' => 'Arrival date',
                'sec_conditions' => 'Position & conditions',
                'p_vehicle' => 'Vehicle type', 'p_cargo' => 'Cargo', 'p_contract' => 'Contract type',
                'p_points' => 'Stops per day', 'p_km' => 'Average mileage',
                'p_loading' => 'Loading / unloading', 'p_language' => 'Required language',
                'sec_routes' => 'Routes & accommodation', 'routes' => 'Routes', 'accommodation' => 'Accommodation',
                'sec_employer' => 'Contact', 'about' => 'About the company',
                'contact_onsite' => 'On-site contact', 'contact_office' => 'Recruitment contact',
                'sec_extra' => 'Additional information', 'footer_by' => 'Document prepared by',
            ],
            'de' => [
                'title_main' => 'Arbeits', 'title_accent' => 'vermittlung',
                'sub' => 'Informationen für den Fahrer', 'brand_hr' => 'Fahrer-Rekrutierung',
                'forwho' => 'Fahrer', 'employer' => 'Arbeitgeber',
                'company_name' => 'Firma', 'region' => 'Region', 'position' => 'Position',
                'f_salary' => 'Vergütung', 'f_system' => 'Arbeitsrhythmus', 'f_arrival' => 'Anreisedatum',
                'sec_conditions' => 'Position & Bedingungen',
                'p_vehicle' => 'Fahrzeugtyp', 'p_cargo' => 'Ladung', 'p_contract' => 'Vertragsart',
                'p_points' => 'Stopps pro Tag', 'p_km' => 'Ø Kilometer',
                'p_loading' => 'Be- / Entladung', 'p_language' => 'Erforderliche Sprache',
                'sec_routes' => 'Routen & Unterkunft', 'routes' => 'Routen', 'accommodation' => 'Unterkunft',
                'sec_employer' => 'Kontakt', 'about' => 'Über das Unternehmen',
                'contact_onsite' => 'Kontakt vor Ort', 'contact_office' => 'Kontakt (Rekrutierung)',
                'sec_extra' => 'Zusätzliche Informationen', 'footer_by' => 'Dokument erstellt von',
            ],
        ];

        return $dict[$lang] ?? $dict['pl'];
    }
}

<?php

namespace App\Actions\Ai;

use App\Models\Tenant;
use App\Support\Ai\OpenAiClient;
use Illuminate\Validation\ValidationException;

/**
 * Generuje gotowy opis ogłoszenia (PL) na podstawie danych oferty — przez ChatGPT.
 */
class GenerateOfferDescriptionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Tenant $tenant, array $data): string
    {
        $client = OpenAiClient::fromTenant($tenant);

        if (! $client) {
            throw ValidationException::withMessages([
                'openai' => ['Skonfiguruj klucz API OpenAI w Ustawieniach, aby korzystać z AI.'],
            ]);
        }

        $facts = collect([
            'Stanowisko' => $data['title'] ?? null,
            'Kraj' => $data['country'] ?? null,
            'Region/baza' => $data['region_base'] ?? null,
            'Typ auta' => $data['vehicle_type'] ?? ($data['trailer_type'] ?? null),
            'System pracy' => $data['work_system'] ?? null,
            'Przewożony towar' => $data['cargo'] ?? null,
            'Trasy' => $data['routes_info'] ?? null,
            'Średni przebieg' => $data['daily_km'] ?? null,
            'Punktów dziennie' => $data['points_per_day'] ?? null,
            'Załadunek/rozładunek' => $data['loading_info'] ?? null,
            'Zakwaterowanie' => $data['accommodation'] ?? null,
            'Rodzaj umowy' => $data['contract_type'] ?? null,
            'Wynagrodzenie' => trim(($data['salary_amount'] ?? '').' '.($data['currency'] ?? '')),
            'Wymagany język' => $data['required_language'] ?? null,
        ])->filter()->map(fn ($v, $k) => "- {$k}: {$v}")->implode("\n");

        $system = 'Jesteś doświadczonym rekruterem kierowców zawodowych. '
            .'Tworzysz zwięzłe, atrakcyjne ogłoszenia o pracę po polsku, gotowe do publikacji '
            .'na Facebooku, OLX i grupach kierowców. Pisz konkretnie, w punktach, bez przesady '
            .'marketingowej. Nie wymyślaj danych, których nie podano.';

        $user = "Na podstawie poniższych danych napisz gotowy opis ogłoszenia o pracę dla kierowcy "
            ."(maks. ok. 1200 znaków, sekcje: krótki wstęp, zakres pracy, oferujemy, wymagania, kontakt na końcu pomiń):\n\n{$facts}";

        return $client->chat($system, $user);
    }
}

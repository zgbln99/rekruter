<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dysk dokumentów kandydatów
    |--------------------------------------------------------------------------
    |
    | Wszystkie dokumenty kandydatów (CV, skany, zdjęcia, wygenerowane PDF)
    | zapisywane są na tym dysku. W produkcji: `mega_s3` (S3-compatible).
    | W developmencie można użyć `local`. Nigdy nie zapisuj dokumentów lokalnie
    | poza trybem developerskim.
    |
    */

    'documents_disk' => env('DOCUMENTS_DISK', env('FILESYSTEM_DISK', 'local')),

    /*
    |--------------------------------------------------------------------------
    | Publiczny adres strony kariery
    |--------------------------------------------------------------------------
    |
    | Strona z ogłoszeniami żyje na domenie głównej, a panel pod subdomeną
    | (np. panel.domena). Publiczne linki ofert pokazywane w panelu muszą
    | wskazywać domenę główną, a nie host żądania API. Domyślnie APP_URL.
    |
    */

    'careers_url' => rtrim((string) env('CAREERS_URL', env('APP_URL', '')), '/'),

    /*
    |--------------------------------------------------------------------------
    | Zdjęcia (stock) na publiczną stronę kariery
    |--------------------------------------------------------------------------
    |
    | Profesjonalne zdjęcia ciężarówek (Unsplash) — ładowane w przeglądarce
    | odwiedzającego. Hero + okładki ofert. Pod spodem zawsze jest gradient,
    | więc strona wygląda dobrze nawet gdy zdjęcie się nie wczyta.
    |
    */

    'hero_image' => env('CAREERS_HERO_IMAGE', 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=1920&q=70'),

    // Pula domyślnych okładek (gdy oferta nie ma własnego zdjęcia).
    'stock_images' => [
        'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1586191582151-f73872dfd183?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1591768793355-74d04bb6608f?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=900&q=70',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unsplash — pobieranie zdjęć ciężarówek w panelu
    |--------------------------------------------------------------------------
    |
    | Klucz dostępu (Access Key) z https://unsplash.com/developers. Bez klucza
    | przycisk „Losuj zdjęcie" użyje puli domyślnej powyżej. Zapytania celują
    | w europejskie marki/ciężarówki (Scania, Volvo, DAF, MAN, Mercedes Actros).
    |
    */
    'unsplash_key' => env('UNSPLASH_ACCESS_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Edytowalne teksty publicznej strony kariery
    |--------------------------------------------------------------------------
    |
    | Administrator może je nadpisać w panelu (Ustawienia). Tu trzymamy domyślne
    | wartości i etykiety dla panelu. Klucz → [label, default, type].
    |
    */
    'careers_texts' => [
        'hero_kicker' => ['label' => 'Hero — mała etykieta', 'default' => 'Agencja pracy dla kierowców', 'type' => 'input'],
        'hero_title' => ['label' => 'Hero — nagłówek', 'default' => 'Praca za kierownicą, na której można polegać.', 'type' => 'input'],
        'hero_lead' => ['label' => 'Hero — opis pod nagłówkiem', 'default' => 'Sprawdzone oferty u solidnych pracodawców w Niemczech i Polsce. Bezpośrednie zatrudnienie, jasne warunki, kontakt w 24 godziny — bez pośredników i bez opłat dla kierowcy.', 'type' => 'textarea'],
        'hero_cta' => ['label' => 'Hero — przycisk główny', 'default' => 'Zobacz oferty', 'type' => 'input'],
        'values_kicker' => ['label' => 'Sekcja „Dlaczego my" — etykieta', 'default' => 'Dlaczego my', 'type' => 'input'],
        'values_title' => ['label' => 'Sekcja „Dlaczego my" — nagłówek', 'default' => 'Robimy to konkretnie', 'type' => 'input'],
        'value1_title' => ['label' => 'Atut 1 — tytuł', 'default' => 'Bezpośrednie zatrudnienie', 'type' => 'input'],
        'value1_text' => ['label' => 'Atut 1 — opis', 'default' => 'Kierujemy Cię prosto do pracodawcy. Jasna umowa i pewne, terminowe wynagrodzenie.', 'type' => 'textarea'],
        'value2_title' => ['label' => 'Atut 2 — tytuł', 'default' => 'Kontakt w 24 godziny', 'type' => 'input'],
        'value2_text' => ['label' => 'Atut 2 — opis', 'default' => 'Zostaw zgłoszenie lub zadzwoń — odzywamy się szybko i rozmawiamy po polsku.', 'type' => 'textarea'],
        'value3_title' => ['label' => 'Atut 3 — tytuł', 'default' => 'Zero opłat dla kierowcy', 'type' => 'input'],
        'value3_text' => ['label' => 'Atut 3 — opis', 'default' => 'Rekrutacja jest dla Ciebie całkowicie bezpłatna. Płaci pracodawca, nie Ty.', 'type' => 'textarea'],
    ],

    'truck_queries' => [
        'scania truck highway',
        'volvo truck europe',
        'daf truck',
        'man truck lorry',
        'mercedes actros truck',
        'european lorry motorway',
        'iveco truck',
        'renault truck europe',
    ],

];

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

    'stock_images' => [
        'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1586191582151-f73872dfd183?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1591768793355-74d04bb6608f?auto=format&fit=crop&w=900&q=70',
        'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=900&q=70',
    ],

];

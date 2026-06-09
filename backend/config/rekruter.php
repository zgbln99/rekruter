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

];

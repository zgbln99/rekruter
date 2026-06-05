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

];

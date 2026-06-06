<?php

return [
    /*
    | Klucze VAPID do Web Push. Wygeneruj komendą:
    |   php artisan rekruter:vapid
    | i wstaw do .env: VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY.
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'https://rekruter.local')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],
];

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Generuje parę kluczy VAPID do powiadomień Web Push.
 * Wynik wklej do .env (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY).
 */
class GenerateVapidCommand extends Command
{
    protected $signature = 'rekruter:vapid';

    protected $description = 'Generuje klucze VAPID do powiadomień push';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('Skopiuj do pliku .env (i zrestartuj app/queue):');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->line('VAPID_SUBJECT=mailto:biuro@twoja-agencja.pl   # opcjonalnie');

        return self::SUCCESS;
    }
}

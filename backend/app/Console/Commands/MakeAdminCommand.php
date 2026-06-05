<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Nadaje użytkownikowi rolę administratora (dostęp do zarządzania użytkownikami).
 *
 * Użycie:
 *   php artisan rekruter:make-admin admin@firma.pl
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'rekruter:make-admin {email : E-mail użytkownika}';

    protected $description = 'Nadaje użytkownikowi rolę administratora';

    public function handle(): int
    {
        $email = $this->argument('email');

        // Bez scope tenanta (kontekst CLI) — szukamy globalnie.
        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if (! $user) {
            $this->error("Nie znaleziono użytkownika: {$email}");
            $this->line('Dostępni użytkownicy:');
            User::withoutGlobalScopes()->get(['email', 'role'])
                ->each(fn ($u) => $this->line("  - {$u->email} ({$u->role->value})"));

            return self::FAILURE;
        }

        $user->forceFill(['role' => UserRole::Admin])->save();

        $this->info("Użytkownik {$email} jest teraz administratorem.");

        return self::SUCCESS;
    }
}

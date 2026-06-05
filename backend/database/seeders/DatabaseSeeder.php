<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed startowy: domyślna organizacja (tenant) + konto administratora.
     *
     * Dane logowania można nadpisać zmiennymi środowiskowymi:
     * SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD, SEED_TENANT_NAME.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => env('SEED_TENANT_NAME', 'Agencja Rekrutacyjna'),
                'settings' => [],
            ]
        );

        User::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => env('SEED_ADMIN_EMAIL', 'admin@rekruter.local')],
            [
                'name' => 'Administrator',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Admin,
            ]
        );
    }
}

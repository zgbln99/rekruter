<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone');
            $table->string('phone_normalized')->index();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();

            $table->string('status')->default('new');
            $table->jsonb('license_categories')->default(DB::raw("'[]'::jsonb"));

            $table->boolean('has_adr')->default(false);
            $table->date('adr_expiry')->nullable();
            $table->boolean('has_code_95')->default(false);
            $table->date('code_95_expiry')->nullable();
            $table->date('driver_card_expiry')->nullable();

            // FK do documents dołączymy w Fazie 2 (moduł Dokumenty).
            $table->uuid('profile_photo_id')->nullable();

            $table->string('source')->nullable();
            $table->timestamp('consent_rodo_at')->nullable();
            $table->text('internal_notes')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        // Deduplikacja: numer unikalny w obrębie tenanta wśród aktywnych rekordów.
        DB::statement(
            'CREATE UNIQUE INDEX candidates_tenant_phone_unique
             ON candidates (tenant_id, phone_normalized)
             WHERE deleted_at IS NULL'
        );

        // Wyszukiwanie pełnotekstowe / fuzzy (DESIGN.md sekcja 6.2).
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement(
            'CREATE INDEX candidates_search_trgm
             ON candidates USING gin (
                (coalesce(first_name, \'\') || \' \' || coalesce(last_name, \'\') || \' \' || coalesce(city, \'\')) gin_trgm_ops
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};

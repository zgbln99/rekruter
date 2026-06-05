<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // FAQ dla rekrutera: [{q, a}] — typowe pytania kierowców + odpowiedzi.
            $table->jsonb('faq')->default(DB::raw("'[]'::jsonb"))->after('call_script');
            // Dodatkowe pola najczęstszych pytań kierowców.
            $table->string('contract_type')->nullable();   // rodzaj umowy
            $table->string('points_per_day')->nullable();  // liczba punktów dziennie
            $table->string('loading_info')->nullable();     // załadunek/rozładunek
            $table->string('daily_km')->nullable();         // średni przebieg dzienny
            $table->string('pdf_url')->nullable();           // link do PDF ogłoszenia
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['faq', 'contract_type', 'points_per_day', 'loading_info', 'daily_km', 'pdf_url']);
        });
    }
};

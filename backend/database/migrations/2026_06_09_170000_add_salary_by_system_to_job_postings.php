<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opcjonalne wynagrodzenie zależne od systemu pracy.
 * Lista par {system, amount}, np. [{"system":"3/1","amount":"2500 EUR"}].
 * Pusta = standardowe (jedno) wynagrodzenie z pola salary_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->json('salary_by_system')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('salary_by_system');
        });
    }
};

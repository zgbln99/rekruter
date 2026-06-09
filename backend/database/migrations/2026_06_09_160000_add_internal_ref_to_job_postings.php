<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wewnętrzne oznaczenie/referencja oferty - widoczne tylko w panelu,
 * ułatwia szybkie rozpoznanie ogłoszenia (np. „HERM01-26", „Rossmann Lipsk").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('internal_ref', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('internal_ref');
        });
    }
};

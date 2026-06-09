<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adres zdjęcia okładkowego ogłoszenia (np. z Unsplash) — pokazywane na
 * publicznej stronie kariery (hero oferty + kafel na liście).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('cover_image_url', 1024)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('cover_image_url');
        });
    }
};

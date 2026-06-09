<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flaga publikacji ogłoszenia na publicznej stronie kariery.
 * Domyślnie false — ogłoszenie trafia na stronę dopiero po ręcznym włączeniu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->index();
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'published_at']);
        });
    }
};

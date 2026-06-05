<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Ścieżka w storage (S3) do tła plakatu wygenerowanego przez AI.
            // Tło generujemy raz i reużywamy; AI ponownie tylko przy „Odśwież tło".
            $table->string('poster_bg_path')->nullable()->after('pdf_url');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('poster_bg_path');
        });
    }
};

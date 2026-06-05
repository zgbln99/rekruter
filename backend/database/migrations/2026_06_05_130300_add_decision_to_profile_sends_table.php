<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_sends', function (Blueprint $table) {
            // Decyzja firmy po wysłaniu profilu.
            $table->string('decision')->default('pending')->after('status');
            $table->timestamp('decision_at')->nullable()->after('decision');
        });
    }

    public function down(): void
    {
        Schema::table('profile_sends', function (Blueprint $table) {
            $table->dropColumn(['decision', 'decision_at']);
        });
    }
};

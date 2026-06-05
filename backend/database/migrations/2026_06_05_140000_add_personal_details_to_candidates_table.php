<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('address')->nullable()->after('city');
            $table->date('date_of_birth')->nullable()->after('address');
            // Historia pracy: [{employer, position, period, description?}]
            $table->jsonb('work_history')->default(DB::raw("'[]'::jsonb"))->after('experience_notes');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['address', 'date_of_birth', 'work_history']);
        });
    }
};

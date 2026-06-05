<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Status kandydata w ramach ogłoszenia (źródło prawdy dla kanbana).
            $table->string('status')->default('new')->after('stage_id');
        });

        // stage_id staje się opcjonalne (pipeline_stages deprecated — ADR-12).
        Schema::table('applications', function (Blueprint $table) {
            $table->uuid('stage_id')->nullable()->change();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index(['job_posting_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['job_posting_id', 'status', 'position']);
            $table->dropColumn('status');
        });
    }
};

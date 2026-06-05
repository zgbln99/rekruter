<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignUuid('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignUuid('stage_id')->constrained('pipeline_stages')->cascadeOnDelete();

            $table->unsignedInteger('position')->default(0); // kolejność w kolumnie kanban
            $table->text('notes')->nullable();

            $table->timestamps();

            // Kandydat tylko raz w danym ogłoszeniu.
            $table->unique(['candidate_id', 'job_posting_id']);
            $table->index(['job_posting_id', 'stage_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

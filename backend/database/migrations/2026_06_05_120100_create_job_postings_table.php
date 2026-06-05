<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->jsonb('required_categories')->default(DB::raw("'[]'::jsonb"));
            $table->string('location')->nullable();
            $table->string('salary_range')->nullable();
            $table->string('status')->default('open');

            // Na przyszłość: identyfikator z portalu pracy (integracje).
            $table->string('external_ref')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};

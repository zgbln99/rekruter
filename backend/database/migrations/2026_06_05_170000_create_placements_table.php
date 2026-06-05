<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignUuid('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('arrival_at'); // data i godzina przyjazdu (wpisywane osobno)
            $table->string('arrival_status')->default('pending');
            $table->dateTime('arrival_confirmed_at')->nullable();
            $table->foreignUuid('arrival_confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'arrival_at']);
            $table->index(['candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('channel');
            $table->string('outcome');
            $table->text('note')->nullable();
            $table->timestamp('contacted_at');
            $table->timestamp('next_contact_at')->nullable();

            // Powiązanie z automatycznie utworzonym zadaniem follow-up.
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->nullOnDelete();

            $table->timestamps();

            $table->index(['candidate_id', 'contacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_logs');
    }
};

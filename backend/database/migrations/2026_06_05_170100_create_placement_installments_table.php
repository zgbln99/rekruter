<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('placement_id')->constrained('placements')->cascadeOnDelete();

            $table->unsignedSmallInteger('sequence'); // 1 lub 2
            $table->date('due_date');                 // termin wystawienia faktury
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->date('invoiced_at')->nullable();
            $table->date('paid_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'due_date']);
            $table->unique(['placement_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_installments');
    }
};

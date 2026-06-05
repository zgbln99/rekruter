<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('candidate_id')->constrained('candidates')->cascadeOnDelete();

            $table->string('type');
            $table->string('disk')->default('s3');
            $table->string('path');               // klucz obiektu w S3 (prywatny)
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('is_profile_photo')->default(false);

            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['candidate_id', 'type']);
        });

        // Domknięcie FK z Fazy 1: zdjęcie profilowe kandydata wskazuje na dokument.
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreign('profile_photo_id')
                ->references('id')->on('documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['profile_photo_id']);
        });
        Schema::dropIfExists('documents');
    }
};

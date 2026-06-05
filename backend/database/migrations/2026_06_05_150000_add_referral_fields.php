<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opis firmy + strona — do dokumentu „Skierowanie do pracy".
        Schema::table('companies', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('website')->nullable()->after('description');
        });

        // Pola skierowania na ogłoszeniu (warunki pracy dla kierowcy).
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('arrival_info')->nullable()->after('start_date');   // data/godzina przyjazdu
            $table->string('vehicle_type')->nullable()->after('trailer_type'); // typ auta (opisowy)
            $table->string('cargo')->nullable();                               // przewożony towar
            $table->text('routes_info')->nullable();                           // trasy
            $table->text('accommodation')->nullable();                         // zakwaterowanie
            $table->text('onsite_contact')->nullable();                        // osoba kontaktowa na miejscu
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['description', 'website']);
        });
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['arrival_info', 'vehicle_type', 'cargo', 'routes_info', 'accommodation', 'onsite_contact']);
        });
    }
};

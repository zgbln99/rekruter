<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('driver_type')->nullable()->after('title');
            $table->string('trailer_type')->nullable()->after('driver_type');
            $table->string('country')->nullable()->after('trailer_type');
            $table->string('region_base')->nullable()->after('country');
            $table->string('work_system')->nullable()->after('region_base');
            $table->string('salary_amount')->nullable()->after('work_system');
            $table->string('currency')->nullable()->after('salary_amount');
            $table->date('start_date')->nullable()->after('currency');
            $table->string('required_language')->nullable()->after('start_date');
            $table->string('required_experience')->nullable()->after('required_language');

            // Gotowy opis do kopiowania (FB/OLX/Jooble) — publiczny.
            $table->text('public_description')->nullable()->after('description');
            // Notatka wewnętrzna rekruterki (NIE w PDF, NIE publiczna).
            $table->text('recruiter_notes')->nullable()->after('public_description');
            // Skrypt rozmowy: lista pytań do zadania kierowcy.
            $table->jsonb('call_script')->default(DB::raw("'[]'::jsonb"))->after('recruiter_notes');
            // Wymagania-checkboxy (mapa boolean).
            $table->jsonb('requirements')->default(DB::raw("'{}'::jsonb"))->after('required_categories');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn([
                'driver_type', 'trailer_type', 'country', 'region_base', 'work_system',
                'salary_amount', 'currency', 'start_date', 'required_language',
                'required_experience', 'public_description', 'recruiter_notes',
                'call_script', 'requirements',
            ]);
        });
    }
};

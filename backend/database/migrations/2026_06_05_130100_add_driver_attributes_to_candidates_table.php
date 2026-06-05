<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('has_hds')->default(false)->after('code_95_expiry');
            $table->boolean('exp_reefer')->default(false)->after('has_hds');      // chłodnia
            $table->boolean('exp_tilt')->default(false)->after('exp_reefer');     // plandeka
            $table->boolean('exp_international')->default(false)->after('exp_tilt');
            $table->boolean('lang_de')->default(false)->after('exp_international');
            $table->boolean('lang_en')->default(false)->after('lang_de');
            $table->string('nationality')->nullable()->after('country');
            $table->date('availability_from')->nullable()->after('nationality');
            $table->text('experience_notes')->nullable()->after('availability_from');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'has_hds', 'exp_reefer', 'exp_tilt', 'exp_international',
                'lang_de', 'lang_en', 'nationality', 'availability_from', 'experience_notes',
            ]);
        });
    }
};

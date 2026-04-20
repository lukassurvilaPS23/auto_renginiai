<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renginiu_registracijos', function (Blueprint $table) {
            $table->string('vardas_pavarde')->nullable()->after('vartotojas_id');
            $table->string('telefonas')->nullable()->after('vardas_pavarde');
            $table->string('automobilis')->nullable()->after('telefonas');
            $table->string('valstybinis_nr')->nullable()->after('automobilis');
            $table->text('komentaras')->nullable()->after('valstybinis_nr');
            $table->json('nuotraukos')->nullable()->after('komentaras');
        });
    }

    public function down(): void
    {
        Schema::table('renginiu_registracijos', function (Blueprint $table) {
            $table->dropColumn([
                'vardas_pavarde',
                'telefonas',
                'automobilis',
                'valstybinis_nr',
                'komentaras',
                'nuotraukos',
            ]);
        });
    }
};

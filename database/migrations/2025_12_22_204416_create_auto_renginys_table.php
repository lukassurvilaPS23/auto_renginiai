<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_renginiai', function (Blueprint $table) {
            $table->id();

            $table->string('pavadinimas');
            $table->text('aprasymas')->nullable();

            $table->dateTime('pradzios_data');
            $table->dateTime('pabaigos_data')->nullable();

            $table->string('miestas');
            $table->string('adresas')->nullable();

            $table->string('statusas')->default('aktyvus');

            $table->foreignId('organizatorius_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_renginiai');
    }
};

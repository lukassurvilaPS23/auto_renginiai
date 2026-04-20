<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renginio_komentarai', function (Blueprint $table) {
            $table->id();

            $table->foreignId('auto_renginys_id')
                ->constrained('auto_renginiai')
                ->cascadeOnDelete();

            $table->foreignId('vartotojas_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('komentaras');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renginio_komentarai');
    }
};

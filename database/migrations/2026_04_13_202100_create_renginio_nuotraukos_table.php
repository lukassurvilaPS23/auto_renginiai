<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renginio_nuotraukos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('auto_renginys_id')
                ->constrained('auto_renginiai')
                ->cascadeOnDelete();

            $table->foreignId('vartotojas_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('kelias');
            $table->string('statusas')->default('laukia');
            $table->timestamp('patvirtinta_at')->nullable();

            $table->timestamps();

            $table->index(['auto_renginys_id', 'statusas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renginio_nuotraukos');
    }
};

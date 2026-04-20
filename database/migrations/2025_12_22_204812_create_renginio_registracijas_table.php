<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renginiu_registracijos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('auto_renginys_id')
                ->constrained('auto_renginiai')
                ->cascadeOnDelete();

            $table->foreignId('vartotojas_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('statusas')->default('patvirtinta');

            $table->timestamps();

            $table->unique(['auto_renginys_id', 'vartotojas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renginiu_registracijos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('automobiliai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('marke');
            $table->string('modelis');
            $table->integer('metai');
            $table->string('spalva')->nullable();
            $table->string('vin', 17)->nullable()->unique();
            $table->string('variklis')->nullable();
            $table->string('kuras')->nullable();
            $table->text('aprasymas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automobiliai');
    }
};

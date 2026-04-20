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
        Schema::table('automobiliai', function (Blueprint $table) {
            $table->string('nuotrauka')->nullable()->after('aprasymas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automobiliai', function (Blueprint $table) {
            $table->dropColumn('nuotrauka');
        });
    }
};

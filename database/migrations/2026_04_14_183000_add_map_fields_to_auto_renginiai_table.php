<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_renginiai', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('adresas');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->json('zemelapio_objektai')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('auto_renginiai', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'zemelapio_objektai']);
        });
    }
};

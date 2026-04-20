<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_renginiai', function (Blueprint $table) {
            $table->json('nuotraukos')->nullable()->after('zemelapio_objektai');
        });
    }

    public function down(): void
    {
        Schema::table('auto_renginiai', function (Blueprint $table) {
            $table->dropColumn('nuotraukos');
        });
    }
};


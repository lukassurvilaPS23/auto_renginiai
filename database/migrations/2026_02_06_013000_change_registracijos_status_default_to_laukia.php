<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `renginiu_registracijos` MODIFY `statusas` VARCHAR(255) NOT NULL DEFAULT 'laukia'");
        }
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE renginiu_registracijos ALTER COLUMN statusas SET DEFAULT 'laukia'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `renginiu_registracijos` MODIFY `statusas` VARCHAR(255) NOT NULL DEFAULT 'patvirtinta'");
        }
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE renginiu_registracijos ALTER COLUMN statusas SET DEFAULT 'patvirtinta'");
        }
    }
};

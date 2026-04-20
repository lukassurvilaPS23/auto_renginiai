<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestiniaiVartotojaiSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@auto.lt'],
            ['name' => 'Administratorius', 'password' => Hash::make('slaptazodis')]
        );
        $admin->syncRoles(['administratorius']);

        $org = User::firstOrCreate(
            ['email' => 'org@auto.lt'],
            ['name' => 'Organizatorius', 'password' => Hash::make('slaptazodis')]
        );
        $org->syncRoles(['organizatorius']);

        $user = User::firstOrCreate(
            ['email' => 'user@auto.lt'],
            ['name' => 'Vartotojas', 'password' => Hash::make('slaptazodis')]
        );
        $user->syncRoles(['vartotojas']);
    }
}

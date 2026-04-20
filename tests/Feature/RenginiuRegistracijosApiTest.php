<?php

namespace Tests\Feature;

use App\Models\AutoRenginys;
use App\Models\RenginioRegistracija;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RenginiuRegistracijosApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_vartotojas_gali_registruotis_ir_atsisakyti(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        $renginys = AutoRenginys::create([
            'pavadinimas' => 'Renginys',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('vartotojas');

        Sanctum::actingAs($user);

        $this->postJson("/api/auto-renginiai/{$renginys->id}/registracija", [
            'vardas_pavarde' => 'Jonas Jonaitis',
            'telefonas' => '+37060000000',
            'automobilis' => 'BMW',
            'valstybinis_nr' => 'ABC123',
            'komentaras' => 'Testas',
        ])
            ->assertStatus(201)
            ->assertJsonPath('registracija.statusas', 'laukia');

        $this->assertDatabaseHas('renginiu_registracijos', [
            'auto_renginys_id' => $renginys->id,
            'vartotojas_id' => $user->id,
            'statusas' => 'laukia',
        ]);

        $this->deleteJson("/api/auto-renginiai/{$renginys->id}/registracija")
            ->assertStatus(200);

        $this->assertDatabaseHas('renginiu_registracijos', [
            'auto_renginys_id' => $renginys->id,
            'vartotojas_id' => $user->id,
            'statusas' => 'atsaukta',
        ]);
    }

    public function test_organizatorius_gali_patvirtinti_registracija(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        $renginys = AutoRenginys::create([
            'pavadinimas' => 'Renginys',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('vartotojas');

        $registracija = RenginioRegistracija::create([
            'auto_renginys_id' => $renginys->id,
            'vartotojas_id' => $user->id,
            'statusas' => 'laukia',
            'vardas_pavarde' => 'Jonas Jonaitis',
        ]);

        Sanctum::actingAs($org);

        $this->patchJson("/api/registracijos/{$registracija->id}/patvirtinti")
            ->assertStatus(200)
            ->assertJsonPath('registracija.statusas', 'patvirtinta');

        $this->assertDatabaseHas('renginiu_registracijos', [
            'id' => $registracija->id,
            'statusas' => 'patvirtinta',
        ]);
    }

    public function test_negalima_registruotis_i_savo_rengini(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        $renginys = AutoRenginys::create([
            'pavadinimas' => 'Renginys',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        Sanctum::actingAs($org);

        $this->postJson("/api/auto-renginiai/{$renginys->id}/registracija")
            ->assertStatus(422);
    }

    public function test_savininkas_gali_perziureti_registracijas(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        $renginys = AutoRenginys::create([
            'pavadinimas' => 'Renginys',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('vartotojas');

        RenginioRegistracija::create([
            'auto_renginys_id' => $renginys->id,
            'vartotojas_id' => $user->id,
            'statusas' => 'patvirtinta',
        ]);

        Sanctum::actingAs($org);

        $this->getJson("/api/auto-renginiai/{$renginys->id}/registracijos")
            ->assertStatus(200)
            ->assertJsonStructure(['registracijos']);
    }

    public function test_administratorius_gali_perziureti_registracijas(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        $renginys = AutoRenginys::create([
            'pavadinimas' => 'Renginys',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('vartotojas');

        RenginioRegistracija::create([
            'auto_renginys_id' => $renginys->id,
            'vartotojas_id' => $user->id,
            'statusas' => 'patvirtinta',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('administratorius');

        Sanctum::actingAs($admin);

        $this->getJson("/api/auto-renginiai/{$renginys->id}/registracijos")
            ->assertStatus(200)
            ->assertJsonStructure(['registracijos']);
    }

    public function test_kitas_vartotojas_negali_perziureti_registraciju(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        $renginys = AutoRenginys::create([
            'pavadinimas' => 'Renginys',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        $kitas = User::factory()->create();
        $kitas->assignRole('vartotojas');

        Sanctum::actingAs($kitas);

        $this->getJson("/api/auto-renginiai/{$renginys->id}/registracijos")
            ->assertStatus(403);
    }
}

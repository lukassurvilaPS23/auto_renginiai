<?php

namespace Tests\Feature;

use App\Models\AutoRenginys;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutoRenginiaiApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_organizatorius_gali_sukurti_auto_rengini(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        Sanctum::actingAs($org);

        $response = $this->postJson('/api/auto-renginiai', [
            'pavadinimas' => 'Žiemos driftas',
            'aprasymas' => 'Testinis renginys',
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => '2025-12-30 21:00:00',
            'miestas' => 'Vilnius',
            'adresas' => 'Ozo g. 25',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('auto_renginys.pavadinimas', 'Žiemos driftas');

        $this->assertDatabaseHas('auto_renginiai', [
            'pavadinimas' => 'Žiemos driftas',
            'miestas' => 'Vilnius',
            'organizatorius_id' => $org->id,
        ]);
    }

    public function test_paprastas_vartotojas_negali_sukurti_auto_renginio(): void
    {
        $user = User::factory()->create();
        $user->assignRole('vartotojas');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auto-renginiai', [
            'pavadinimas' => 'Bandymas',
            'pradzios_data' => '2025-12-30 18:00:00',
            'miestas' => 'Kaunas',
        ]);

        $response->assertStatus(403);
    }

    public function test_viesas_sarasas_veikia(): void
    {
        $org = User::factory()->create();
        $org->assignRole('organizatorius');

        AutoRenginys::create([
            'pavadinimas' => 'Renginys 1',
            'aprasymas' => null,
            'pradzios_data' => '2025-12-30 18:00:00',
            'pabaigos_data' => null,
            'miestas' => 'Vilnius',
            'adresas' => null,
            'statusas' => 'aktyvus',
            'organizatorius_id' => $org->id,
        ]);

        $this->getJson('/api/auto-renginiai')
            ->assertStatus(200)
            ->assertJsonStructure(['auto_renginiai']);
    }
}

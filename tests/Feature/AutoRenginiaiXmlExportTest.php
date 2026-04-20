<?php

namespace Tests\Feature;

use App\Models\AutoRenginys;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoRenginiaiXmlExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_export_xml_veikia(): void
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

        $response = $this->get('/api/auto-renginiai/export.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<auto_renginiai', false);
        $response->assertSee('<pavadinimas>Renginys 1</pavadinimas>', false);
    }
}

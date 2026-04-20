<?php

namespace App\Http\Controllers;

use App\Models\AutoRenginys;
use App\Models\RenginioKomentaras;
use App\Models\RenginioNuotrauka;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RenginiuAtsiliepimaiController extends Controller
{
    private function renginysPasibaiges(AutoRenginys $autoRenginys): bool
    {
        $end = $autoRenginys->pabaigos_data ?? $autoRenginys->pradzios_data;
        if (!$end) {
            return false;
        }

        return Carbon::parse($end)->isPast();
    }

    public function index(AutoRenginys $autoRenginys)
    {
        $komentarai = $autoRenginys->komentarai()
            ->with('vartotojas:id,name,nuotrauka')
            ->orderByDesc('created_at')
            ->get();

        $nuotraukos = $autoRenginys->nuotraukos()
            ->where('statusas', 'patvirtinta')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'komentarai' => $komentarai->map(function ($k) {
                return [
                    'id' => $k->id,
                    'komentaras' => $k->komentaras,
                    'sukurta' => optional($k->created_at)->toDateTimeString(),
                    'vartotojas' => [
                        'id' => $k->vartotojas?->id,
                        'vardas' => $k->vartotojas?->name,
                        'nuotrauka' => $k->vartotojas?->nuotrauka,
                    ],
                ];
            }),
            'nuotraukos' => $nuotraukos->map(function ($n) {
                return [
                    'id' => $n->id,
                    'url' => '/storage/' . ltrim($n->kelias, '/'),
                    'sukurta' => optional($n->created_at)->toDateTimeString(),
                ];
            }),
        ], 200);
    }

    public function storeKomentaras(Request $request, AutoRenginys $autoRenginys)
    {
        if (!$this->renginysPasibaiges($autoRenginys)) {
            return response()->json(['zinute' => 'Komentarus galima rašyti tik pasibaigus renginiui.'], 422);
        }

        $data = $request->validate([
            'komentaras' => 'required|string|max:2000',
        ]);

        $komentaras = RenginioKomentaras::create([
            'auto_renginys_id' => $autoRenginys->id,
            'vartotojas_id' => $request->user()->id,
            'komentaras' => $data['komentaras'],
        ]);

        return response()->json([
            'zinute' => 'Komentaras pridėtas',
            'komentaras' => [
                'id' => $komentaras->id,
                'komentaras' => $komentaras->komentaras,
                'sukurta' => optional($komentaras->created_at)->toDateTimeString(),
            ],
        ], 201);
    }

    public function storeNuotraukos(Request $request, AutoRenginys $autoRenginys)
    {
        if (!$this->renginysPasibaiges($autoRenginys)) {
            return response()->json(['zinute' => 'Nuotraukas galima įkelti tik pasibaigus renginiui.'], 422);
        }

        $data = $request->validate([
            'nuotraukos' => 'required|array|min:1|max:5',
            'nuotraukos.*' => 'required|image|max:5120',
        ]);

        $created = [];
        foreach ($data['nuotraukos'] as $file) {
            $path = $file->store('renginiu-nuotraukos', 'public');

            $n = RenginioNuotrauka::create([
                'auto_renginys_id' => $autoRenginys->id,
                'vartotojas_id' => $request->user()->id,
                'kelias' => $path,
                'statusas' => 'laukia',
            ]);

            $created[] = [
                'id' => $n->id,
                'statusas' => $n->statusas,
            ];
        }

        return response()->json([
            'zinute' => 'Nuotraukos įkeltos (laukia patvirtinimo)',
            'nuotraukos' => $created,
        ], 201);
    }

    public function manoLaukianciosNuotraukos(Request $request, AutoRenginys $autoRenginys)
    {
        $nuotraukos = $autoRenginys->nuotraukos()
            ->where('statusas', 'laukia')
            ->where('vartotojas_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'nuotraukos' => $nuotraukos->map(function ($n) {
                return [
                    'id' => $n->id,
                    'statusas' => $n->statusas,
                    'url' => '/storage/' . ltrim($n->kelias, '/'),
                    'sukurta' => optional($n->created_at)->toDateTimeString(),
                ];
            }),
        ], 200);
    }

    public function atsauktiNuotrauka(Request $request, RenginioNuotrauka $nuotrauka)
    {
        if ((int)$nuotrauka->vartotojas_id !== (int)$request->user()->id) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        if ($nuotrauka->statusas !== 'laukia') {
            return response()->json(['zinute' => 'Šios nuotraukos nebegalima atšaukti.'], 422);
        }

        if (!empty($nuotrauka->kelias)) {
            Storage::disk('public')->delete($nuotrauka->kelias);
        }

        $nuotrauka->delete();

        return response()->json([
            'zinute' => 'Nuotraukos prašymas atšauktas',
        ], 200);
    }

    public function laukinciosNuotraukos(Request $request, AutoRenginys $autoRenginys)
    {
        $user = $request->user();
        $yraAdmin = $user->hasRole('administratorius');
        $yraSavininkas = (int)$autoRenginys->organizatorius_id === (int)$user->id;

        if (!$yraAdmin && !$yraSavininkas) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        $nuotraukos = $autoRenginys->nuotraukos()
            ->where('statusas', 'laukia')
            ->with('vartotojas:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'nuotraukos' => $nuotraukos->map(function ($n) {
                return [
                    'id' => $n->id,
                    'statusas' => $n->statusas,
                    'url' => '/storage/' . ltrim($n->kelias, '/'),
                    'sukurta' => optional($n->created_at)->toDateTimeString(),
                    'vartotojas' => [
                        'id' => $n->vartotojas?->id,
                        'vardas' => $n->vartotojas?->name,
                        'email' => $n->vartotojas?->email,
                    ],
                ];
            }),
        ], 200);
    }

    public function patvirtintiNuotrauka(Request $request, RenginioNuotrauka $nuotrauka)
    {
        $user = $request->user();
        $autoRenginys = AutoRenginys::findOrFail($nuotrauka->auto_renginys_id);

        $yraAdmin = $user->hasRole('administratorius');
        $yraSavininkas = (int)$autoRenginys->organizatorius_id === (int)$user->id;

        if (!$yraAdmin && !$yraSavininkas) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        $nuotrauka->statusas = 'patvirtinta';
        $nuotrauka->patvirtinta_at = now();
        $nuotrauka->save();

        return response()->json([
            'zinute' => 'Nuotrauka patvirtinta',
            'nuotrauka' => [
                'id' => $nuotrauka->id,
                'statusas' => $nuotrauka->statusas,
            ],
        ], 200);
    }

    public function atmestiNuotrauka(Request $request, RenginioNuotrauka $nuotrauka)
    {
        $user = $request->user();
        $autoRenginys = AutoRenginys::findOrFail($nuotrauka->auto_renginys_id);

        $yraAdmin = $user->hasRole('administratorius');
        $yraSavininkas = (int)$autoRenginys->organizatorius_id === (int)$user->id;

        if (!$yraAdmin && !$yraSavininkas) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        if (!empty($nuotrauka->kelias)) {
            Storage::disk('public')->delete($nuotrauka->kelias);
        }

        $nuotrauka->statusas = 'atmesta';
        $nuotrauka->save();

        return response()->json([
            'zinute' => 'Nuotrauka atmesta',
            'nuotrauka' => [
                'id' => $nuotrauka->id,
                'statusas' => $nuotrauka->statusas,
            ],
        ], 200);
    }
}

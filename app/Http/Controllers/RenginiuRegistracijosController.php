<?php

namespace App\Http\Controllers;

use App\Models\AutoRenginys;
use App\Models\RenginioRegistracija;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *   name="Registracijos",
 *   description="Registracija į auto renginius ir registracijos atšaukimas"
 * )
 */

class RenginiuRegistracijosController extends Controller
{

    /**
 * @OA\Post(
 *   path="/api/auto-renginiai/{autoRenginys}/registracija",
 *   tags={"Registracijos"},
 *   summary="Užsiregistruoti į auto renginį",
 *   security={{"bearerAuth":{}}},
 *   @OA\Parameter(
 *     name="autoRenginys",
 *     in="path",
 *     required=true,
 *     @OA\Schema(type="integer"),
 *     example=1
 *   ),
 *   @OA\Response(response=201, description="Registracija sėkminga"),
 *   @OA\Response(response=401, description="Neprisijungęs"),
 *   @OA\Response(response=404, description="Renginys nerastas"),
 *   @OA\Response(response=422, description="Negalima registruotis / validacija")
 * )
 */

    // POST /api/auto-renginiai/{id}/registracija
    public function registruotis(Request $request, AutoRenginys $autoRenginys)
    {
        $user = $request->user();

        // Organizatorius gali būti ir dalyvis, bet dažnai nenorima registruoti į savo renginį:
        // jei nenori šito ribojimo — ištrink šitą if bloką.
        if ($user->id === $autoRenginys->organizatorius_id) {
            return response()->json(['zinute' => 'Negalite registruotis į savo auto renginį.'], 422);
        }

        $validated = $request->validate([
            'vardas_pavarde' => ['required', 'string', 'max:255'],
            'telefonas' => ['nullable', 'string', 'max:50'],
            'automobilis' => ['nullable', 'string', 'max:255'],
            'valstybinis_nr' => ['nullable', 'string', 'max:50'],
            'komentaras' => ['nullable', 'string', 'max:2000'],
            'nuotraukos' => ['nullable', 'array', 'max:5'],
            'nuotraukos.*' => ['file', 'image', 'max:5120'],
        ]);

        $existing = RenginioRegistracija::where('auto_renginys_id', $autoRenginys->id)
            ->where('vartotojas_id', $user->id)
            ->first();

        if ($existing && $existing->statusas === 'patvirtinta') {
            return response()->json(['zinute' => 'Registracija jau patvirtinta.'], 422);
        }

        if ($existing && $existing->statusas === 'laukia') {
            return response()->json(['zinute' => 'Registracija jau pateikta ir laukia patvirtinimo.'], 409);
        }

        $photoPaths = [];
        $photos = $request->file('nuotraukos', []);
        foreach ($photos as $file) {
            $photoPaths[] = Storage::disk('public')->put('registracijos', $file);
        }

        $registracija = RenginioRegistracija::updateOrCreate(
            [
                'auto_renginys_id' => $autoRenginys->id,
                'vartotojas_id' => $user->id,
            ],
            [
                'statusas' => 'laukia',
                'vardas_pavarde' => $validated['vardas_pavarde'],
                'telefonas' => $validated['telefonas'] ?? null,
                'automobilis' => $validated['automobilis'] ?? null,
                'valstybinis_nr' => $validated['valstybinis_nr'] ?? null,
                'komentaras' => $validated['komentaras'] ?? null,
                'nuotraukos' => $photoPaths,
            ]
        );

        return response()->json([
            'zinute' => 'Registracija pateikta ir laukia patvirtinimo.',
            'registracija' => $registracija,
        ], 201);
    }

    /**
 * @OA\Delete(
 *   path="/api/auto-renginiai/{autoRenginys}/registracija",
 *   tags={"Registracijos"},
 *   summary="Atšaukti registraciją į auto renginį",
 *   security={{"bearerAuth":{}}},
 *   @OA\Parameter(
 *     name="autoRenginys",
 *     in="path",
 *     required=true,
 *     @OA\Schema(type="integer"),
 *     example=1
 *   ),
 *   @OA\Response(response=200, description="Registracija atšaukta"),
 *   @OA\Response(response=401, description="Neprisijungęs"),
 *   @OA\Response(response=404, description="Registracijos nerasta")
 * )
 */

    // DELETE /api/auto-renginiai/{id}/registracija
    public function atsisakyti(Request $request, AutoRenginys $autoRenginys)
    {
        $user = $request->user();

        $registracija = RenginioRegistracija::where('auto_renginys_id', $autoRenginys->id)
            ->where('vartotojas_id', $user->id)
            ->first();

        if (!$registracija) {
            return response()->json(['zinute' => 'Registracijos nerasta.'], 404);
        }

        $leidziami = ['laukia', 'patvirtinta'];
        if (!in_array($registracija->statusas, $leidziami, true)) {
            return response()->json(['zinute' => 'Registracijos atšaukti nebegalima.'], 422);
        }

        $registracija->update(['statusas' => 'atsaukta']);

        return response()->json(['zinute' => 'Registracija atšaukta.'], 200);
    }

    public function patvirtinti(Request $request, RenginioRegistracija $registracija)
    {
        $user = $request->user();

        $yraAdmin = $user->hasRole('administratorius');
        $yraSavininkas = (int)$registracija->autoRenginys?->organizatorius_id === (int)$user->id;
        if (!$yraAdmin && !$yraSavininkas) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        if ($registracija->statusas !== 'laukia') {
            return response()->json(['zinute' => 'Registracijos būsenos pakeisti nebegalima.'], 422);
        }

        $registracija->update(['statusas' => 'patvirtinta']);

        return response()->json([
            'zinute' => 'Registracija patvirtinta.',
            'registracija' => $registracija,
        ], 200);
    }

    public function atsauktiOrganizatoriaus(Request $request, RenginioRegistracija $registracija)
    {
        $user = $request->user();

        $yraAdmin = $user->hasRole('administratorius');
        $yraSavininkas = (int)$registracija->autoRenginys?->organizatorius_id === (int)$user->id;
        if (!$yraAdmin && !$yraSavininkas) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        $leidziami = ['laukia', 'patvirtinta'];
        if (!in_array($registracija->statusas, $leidziami, true)) {
            return response()->json(['zinute' => 'Registracijos būsenos pakeisti nebegalima.'], 422);
        }

        $registracija->update(['statusas' => 'atsaukta']);

        return response()->json([
            'zinute' => 'Registracija atšaukta.',
            'registracija' => $registracija,
        ], 200);
    }

    public function manoRegistracijos(Request $request)
    {
        $user = $request->user();

        $registracijos = RenginioRegistracija::query()
            ->where('vartotojas_id', $user->id)
            ->with('autoRenginys:id,pavadinimas,miestas,pradzios_data,pabaigos_data')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'statusas' => $r->statusas,
                    'sukurta' => optional($r->created_at)->toDateTimeString(),
                    'auto_renginys' => [
                        'id' => $r->autoRenginys?->id,
                        'pavadinimas' => $r->autoRenginys?->pavadinimas,
                        'miestas' => $r->autoRenginys?->miestas,
                        'pradzios_data' => optional($r->autoRenginys?->pradzios_data)->toDateTimeString(),
                        'pabaigos_data' => optional($r->autoRenginys?->pabaigos_data)->toDateTimeString(),
                    ],
                ];
            });

        return response()->json([
            'registracijos' => $registracijos,
        ], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *   name="Autentifikacija",
 *   description="Registracija, prisijungimas, profilis ir atsijungimas"
 * )
 */


class AutentifikacijaController extends Controller
{
    /**
     * Slaptažodžio kūrimo / keitimo taisyklės:
     *  - ne mažiau 8 simbolių
     *  - bent 1 didžioji raidė
     *  - bent 1 skaičius
     *  - bent 1 specialus simbolis (ne raidė ir ne skaičius)
     */
    private function slaptazodzioTaisykles(bool $confirmed = false): array
    {
        $rules = [
            'string',
            'min:8',
            'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
        ];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Bendros klaidų žinutės slaptažodžio laukui.
     */
    private function slaptazodzioZinutes(string $laukas): array
    {
        return [
            "{$laukas}.min" => 'Slaptažodis turi būti bent 8 simbolių.',
            "{$laukas}.regex" => 'Slaptažodis turi turėti bent vieną didžiąją raidę, vieną skaičių ir vieną specialų simbolį.',
            "{$laukas}.confirmed" => 'Slaptažodžiai nesutampa.',
        ];
    }

    /**
 * @OA\Post(
 *   path="/api/registruotis",
 *   tags={"Autentifikacija"},
 *   summary="Vartotojo registracija ir tokeno gavimas",
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"vardas","el_pastas","slaptazodis"},
 *       @OA\Property(property="vardas", type="string", example="Lukas"),
 *       @OA\Property(property="el_pastas", type="string", example="lukas@auto.lt"),
 *       @OA\Property(property="slaptazodis", type="string", example="slaptazodis")
 *     )
 *   ),
 *   @OA\Response(
 *     response=201,
 *     description="Sėkminga registracija",
 *     @OA\JsonContent(
 *       @OA\Property(property="zinute", type="string", example="Registracija sėkminga"),
 *       @OA\Property(property="token", type="string", example="1|abc...")
 *     )
 *   ),
 *   @OA\Response(response=422, description="Validacijos klaida")
 * )
 */

    public function registruotis(Request $request)
    {
        $data = $request->validate([
            'vardas' => 'required|string|max:255',
            'el_pastas' => 'required|email|unique:users,email',
            'slaptazodis' => array_merge(['required'], $this->slaptazodzioTaisykles()),
        ], $this->slaptazodzioZinutes('slaptazodis'));

        $user = User::create([
            'name' => $data['vardas'],
            'email' => $data['el_pastas'],
            'password' => Hash::make($data['slaptazodis']),
        ]);

        Role::firstOrCreate(['name' => 'vartotojas']);
        $user->assignRole('vartotojas');

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'zinute' => 'Registracija sėkminga',
            'token' => $token,
        ], 201);
    }

/**
 * @OA\Post(
 *   path="/api/prisijungti",
 *   tags={"Autentifikacija"},
 *   summary="Prisijungimas ir tokeno gavimas",
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"el_pastas","slaptazodis"},
 *       @OA\Property(property="el_pastas", type="string", example="org@auto.lt"),
 *       @OA\Property(property="slaptazodis", type="string", example="slaptazodis")
 *     )
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="Sėkmingas prisijungimas",
 *     @OA\JsonContent(
 *       @OA\Property(property="zinute", type="string", example="Prisijungta"),
 *       @OA\Property(property="token", type="string", example="1|abc...")
 *     )
 *   ),
 *   @OA\Response(response=422, description="Validacijos klaida")
 * )
 */

    public function prisijungti(Request $request)
    {
        $request->validate([
            'el_pastas' => 'required|email',
            'slaptazodis' => 'required|string',
        ]);

        $user = User::where('email', $request->el_pastas)->first();

        if (!$user || !Hash::check($request->slaptazodis, $user->password)) {
            throw ValidationException::withMessages([
                'el_pastas' => ['Neteisingi prisijungimo duomenys'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'zinute' => 'Prisijungta',
            'token' => $token,
        ], 200);
    }

    /**
 * @OA\Post(
 *   path="/api/atsijungti",
 *   tags={"Autentifikacija"},
 *   summary="Atsijungti (ištrina current token)",
 *   security={{"bearerAuth":{}}},
 *   @OA\Response(
 *     response=200,
 *     description="OK",
 *     @OA\JsonContent(
 *       @OA\Property(property="zinute", type="string", example="Atsijungta")
 *     )
 *   ),
 *   @OA\Response(response=401, description="Neprisijungęs")
 * )
 */

    public function atsijungti(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'zinute' => 'Atsijungta',
        ], 200);
    }

    /**
 * @OA\Get(
 *   path="/api/as",
 *   tags={"Autentifikacija"},
 *   summary="Gauti prisijungusio vartotojo informaciją",
 *   security={{"bearerAuth":{}}},
 *   @OA\Response(response=200, description="OK"),
 *   @OA\Response(response=401, description="Neprisijungęs")
 * )
 */

    public function as(Request $request)
    {
        $user = $request->user()->loadMissing('roles:id,name');

        return response()->json([
            'vartotojas' => $user,
            'roles' => $user->roles->pluck('name'),
        ], 200);
    }

    public function atnaujintiProfili(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'vardas' => 'required|string|max:255',
            'el_pastas' => 'required|email|unique:users,email,' . $user->id,
            'dabartinis_slaptazodis' => 'nullable|required_with:naujas_slaptazodis|string',
            'naujas_slaptazodis' => array_merge(['nullable'], $this->slaptazodzioTaisykles(true)),
            'nuotrauka' => 'nullable|image|max:10240',
        ], $this->slaptazodzioZinutes('naujas_slaptazodis'));

        if (!empty($data['naujas_slaptazodis'])) {
            if (!Hash::check($data['dabartinis_slaptazodis'] ?? '', $user->password)) {
                throw ValidationException::withMessages([
                    'dabartinis_slaptazodis' => ['Neteisingas dabartinis slaptažodis'],
                ]);
            }

            $user->password = Hash::make($data['naujas_slaptazodis']);
        }

        $user->name = $data['vardas'];
        $user->email = $data['el_pastas'];

        if ($request->hasFile('nuotrauka')) {
            $path = $request->file('nuotrauka')->store('profilio-nuotraukos', 'public');
            $user->nuotrauka = '/storage/' . $path;
        }

        $user->save();

        $user->loadMissing('roles:id,name');

        return response()->json([
            'zinute' => 'Profilis atnaujintas',
            'vartotojas' => $user,
            'roles' => $user->roles->pluck('name'),
        ], 200);
    }

    public function vartotojoAktyvumas(Request $request, $userId = null)
    {
        $targetUserId = $userId ?? $request->user()->id;
        $targetUser = \App\Models\User::findOrFail($targetUserId);

        \Log::info('vartotojoAktyvumas', [
            'targetUserId' => $targetUserId,
            'requestUserId' => $userId,
            'authId' => $request->user()->id,
        ]);

        $automobiliai = \App\Models\Automobilis::where('user_id', $targetUserId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $komentarai = \App\Models\RenginioKomentaras::where('vartotojas_id', $targetUserId)
            ->with(['renginys:id,pavadinimas,miestas', 'vartotojas:id,name,nuotrauka'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $nuotraukos = \App\Models\RenginioNuotrauka::where('vartotojas_id', $targetUserId)
            ->where('statusas', 'patvirtinta')
            ->with('renginys:id,pavadinimas,miestas')
            ->orderBy('patvirtinta_at', 'desc')
            ->get()
            ->toArray();

        $isOrganizatorius = $targetUser->hasRole('organizatorius') || $targetUser->hasRole('administratorius');
        $renginiai = [];
        if ($isOrganizatorius) {
            $renginiai = \App\Models\AutoRenginys::where('organizatorius_id', $targetUserId)
                ->orderBy('pradzios_data', 'desc')
                ->get()
                ->toArray();
        }

        \Log::info('vartotojoAktyvumas result', [
            'automobiliai_count' => count($automobiliai),
            'komentarai_count' => count($komentarai),
            'nuotraukos_count' => count($nuotraukos),
            'renginiai_count' => count($renginiai),
        ]);

        return response()->json([
            'vartotojas' => $targetUser->only('id', 'name', 'email', 'nuotrauka'),
            'roles' => $targetUser->roles->pluck('name'),
            'automobiliai' => $automobiliai,
            'komentarai' => $komentarai,
            'nuotraukos' => $nuotraukos,
            'renginiai' => $renginiai,
        ], 200);
    }

    public function siustiSlaptazodzioNuoroda(Request $request)
    {
        $request->validate([
            'el_pastas' => 'required|email',
        ]);

        $status = Password::sendResetLink([
            'email' => $request->el_pastas,
        ]);

        $zinute = 'Jei šis el. paštas registruotas, išsiuntėme slaptažodžio atkūrimo nuorodą.';

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'zinute' => 'Per daug užklausų. Palaukite minutę ir bandykite dar kartą.',
            ], 429);
        }

        return response()->json(['zinute' => $zinute], 200);
    }

    public function nustatytiSlaptazodiIsNuorodos(Request $request)
    {
        $data = $request->validate([
            'el_pastas' => 'required|email',
            'token' => 'required|string',
            'slaptazodis' => array_merge(['required'], $this->slaptazodzioTaisykles(true)),
        ], $this->slaptazodzioZinutes('slaptazodis'));

        $status = Password::reset(
            [
                'email' => $data['el_pastas'],
                'password' => $data['slaptazodis'],
                'password_confirmation' => $data['slaptazodis_confirmation'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'zinute' => 'Slaptažodis pakeistas. Galite prisijungti.',
            ], 200);
        }

        $klaidos = match ($status) {
            Password::INVALID_TOKEN => 'Nuoroda netinkama arba pasibaigusi. Paprašykite naujos.',
            Password::INVALID_USER => 'Vartotojas nerastas.',
            default => 'Nepavyko pakeisti slaptažodžio. Bandykite dar kartą.',
        };

        return response()->json(['zinute' => $klaidos], 422);
    }
}

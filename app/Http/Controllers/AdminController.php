<?php

namespace App\Http\Controllers;

use App\Models\AutoRenginys;
use App\Models\RenginioNuotrauka;
use App\Models\RenginioRegistracija;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    private function assertAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('administratorius')) {
            return response()->json(['zinute' => 'Neturite teisių.'], 403);
        }

        return null;
    }

    public function statistika(Request $request)
    {
        if ($resp = $this->assertAdmin($request)) {
            return $resp;
        }

        return response()->json([
            'statistika' => [
                'vartotojai' => User::count(),
                'renginiai' => AutoRenginys::count(),
                'registracijos' => RenginioRegistracija::count(),
                'registracijos_laukia' => RenginioRegistracija::where('statusas', 'laukia')->count(),
                'nuotraukos_laukia' => RenginioNuotrauka::where('statusas', 'laukia')->count(),
            ],
        ], 200);
    }

    public function vartotojai(Request $request)
    {
        if ($resp = $this->assertAdmin($request)) {
            return $resp;
        }

        $users = User::query()
            ->with('roles:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->roles->pluck('name')->values(),
                    'sukurta' => optional($u->created_at)->toDateTimeString(),
                ];
            });

        return response()->json(['vartotojai' => $users], 200);
    }

    public function nustatytiRole(Request $request, User $user)
    {
        if ($resp = $this->assertAdmin($request)) {
            return $resp;
        }

        $data = $request->validate([
            'role' => ['required', 'string', 'in:vartotojas,organizatorius,administratorius'],
        ]);

        Role::firstOrCreate(['name' => $data['role']]);
        $user->syncRoles([$data['role']]);

        $user->loadMissing('roles:id,name');

        return response()->json([
            'zinute' => 'Rolė atnaujinta',
            'vartotojas' => [
                'id' => $user->id,
                'roles' => $user->roles->pluck('name')->values(),
            ],
        ], 200);
    }
}


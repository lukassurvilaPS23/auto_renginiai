<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutentifikacijaController;
use App\Http\Controllers\AutoRenginiaiController;
use App\Http\Controllers\RenginiuRegistracijosController;
use App\Http\Controllers\RenginiuAtsiliepimaiController;
use App\Http\Controllers\AutomobiliuController;

Route::get('/testas', function () {
    return response()->json(['ok' => true]);
});

Route::get('/testas-swagger', fn () => response()->json(['ok' => true]));

// Auth
Route::post('/registruotis', [AutentifikacijaController::class, 'registruotis']);
Route::post('/prisijungti', [AutentifikacijaController::class, 'prisijungti']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auto-renginiai/{autoRenginys}/registracijos', [\App\Http\Controllers\AutoRenginiaiController::class, 'registracijos']);
    Route::post('/atsijungti', [AutentifikacijaController::class, 'atsijungti']);
    Route::get('/as', [AutentifikacijaController::class, 'as']);

    Route::put('/profilis', [AutentifikacijaController::class, 'atnaujintiProfili']);
    Route::get('/vartotojai/{userId}/aktyvumas', [AutentifikacijaController::class, 'vartotojoAktyvumas']);
    Route::get('/mano-aktyvumas', [AutentifikacijaController::class, 'vartotojoAktyvumas']);

});

// Auto renginiai
Route::get('/auto-renginiai/export.xml', [AutoRenginiaiController::class, 'exportXml']);

Route::get('/auto-renginiai', [AutoRenginiaiController::class, 'index']);
Route::get('/auto-renginiai/{autoRenginys}', [AutoRenginiaiController::class, 'show']);
Route::get('/auto-renginiai/{autoRenginys}/atsiliepimai', [RenginiuAtsiliepimaiController::class, 'index']);

// Automobiliai (garage)

Route::get('/automobiliai/{automobilis}', [AutomobiliuController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auto-renginiai', [AutoRenginiaiController::class, 'store']);
    Route::put('/auto-renginiai/{autoRenginys}', [AutoRenginiaiController::class, 'update']);
    Route::delete('/auto-renginiai/{autoRenginys}', [AutoRenginiaiController::class, 'destroy']);

    // Automobiliai (garage) - authenticated CRUD
    Route::get('/automobiliai', [AutomobiliuController::class, 'index']);
    Route::post('/automobiliai', [AutomobiliuController::class, 'store']);
    Route::put('/automobiliai/{automobilis}', [AutomobiliuController::class, 'update']);
    Route::delete('/automobiliai/{automobilis}', [AutomobiliuController::class, 'destroy']);

    Route::post('/auto-renginiai/{autoRenginys}/komentarai', [RenginiuAtsiliepimaiController::class, 'storeKomentaras']);
    Route::post('/auto-renginiai/{autoRenginys}/nuotraukos', [RenginiuAtsiliepimaiController::class, 'storeNuotraukos']);
    Route::get('/auto-renginiai/{autoRenginys}/mano-nuotraukos-laukia', [RenginiuAtsiliepimaiController::class, 'manoLaukianciosNuotraukos']);
    Route::get('/auto-renginiai/{autoRenginys}/nuotraukos-laukia', [RenginiuAtsiliepimaiController::class, 'laukinciosNuotraukos']);
    Route::patch('/renginiu-nuotraukos/{nuotrauka}/patvirtinti', [RenginiuAtsiliepimaiController::class, 'patvirtintiNuotrauka']);
    Route::patch('/renginiu-nuotraukos/{nuotrauka}/atmesti', [RenginiuAtsiliepimaiController::class, 'atmestiNuotrauka']);
    Route::delete('/renginiu-nuotraukos/{nuotrauka}', [RenginiuAtsiliepimaiController::class, 'atsauktiNuotrauka']);

    // Registracijos į renginius
    Route::post('/auto-renginiai/{autoRenginys}/registracija', [RenginiuRegistracijosController::class, 'registruotis']);
    Route::delete('/auto-renginiai/{autoRenginys}/registracija', [RenginiuRegistracijosController::class, 'atsisakyti']);

    // Dalyvio registracijos (kur užsiregistravo)
    Route::get('/mano-registracijos', [RenginiuRegistracijosController::class, 'manoRegistracijos']);

    // Organizatoriaus / administratoriaus veiksmai su registracijomis
    Route::patch('/registracijos/{registracija}/patvirtinti', [RenginiuRegistracijosController::class, 'patvirtinti']);
    Route::patch('/registracijos/{registracija}/atsaukti', [RenginiuRegistracijosController::class, 'atsauktiOrganizatoriaus']);
});

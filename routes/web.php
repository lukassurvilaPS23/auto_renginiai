<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/xml', '/api/auto-renginiai/export.xml')->name('xml');
Route::redirect('/swagger', '/api/documentation')->name('swagger');

Route::get('/', function () {
    return view('index');
})->name('pagrindinis');

Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');
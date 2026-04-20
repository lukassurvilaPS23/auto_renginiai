<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
 * Serve files from storage/app/public when the request hits Laravel (e.g. broken public/storage
 * symlink, or front controller handling /storage/*). Static file exists → public/server.php returns
 * false before index.php, so this is a fallback.
 */
Route::get('/storage/{path}', function (string $path) {
    $path = urldecode($path);
    $path = str_replace('\\', '/', $path);
    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }
    $path = ltrim($path, '/');
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return Storage::disk('public')->response($path);
})->where('path', '.*');

Route::get('/', function () {
    return view('index');
})->name('pagrindinis');

Route::get('/{any}', function () {
    return view('index');
})->where('any', '^(?!api/|storage/|build/).*');
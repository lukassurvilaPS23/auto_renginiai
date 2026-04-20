<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('pagrindinis');

Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');
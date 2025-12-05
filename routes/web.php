<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home-new/', function () {
    return view('home');
});

Route::get('/legal-notice/', function () {
    return view('legal-notice');
})->name('legal-notice');

Route::get('/privacy-policy/', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/check/{slug}', [RedirectController::class, 'check'])->name('check');
Route::get('/checking/{slug}', [RedirectController::class, 'checking'])->name('checking');

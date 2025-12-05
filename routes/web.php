<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home-new/', function () {
    return view('home');
});

Route::get('/check/{slug}', [RedirectController::class, 'check'])->name('check');
Route::get('/checking/{slug}', [RedirectController::class, 'checking'])->name('checking');

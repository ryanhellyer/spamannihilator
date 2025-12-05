<?php

use App\Http\Controllers\RedirectController;
use App\Http\Controllers\RedirectManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::post('/', [RedirectManagementController::class, 'store'])->name('redirect.store');

Route::get('/home-new/', function () {
    return view('home');
});

Route::get('/legal-notice/', function () {
    return view('legal-notice');
})->name('legal-notice');

Route::get('/privacy-policy/', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/admin/{hash}', [RedirectManagementController::class, 'show'])->name('admin.show');
Route::put('/admin/{hash}', [RedirectManagementController::class, 'update'])->name('admin.update');

Route::get('/check/{slug}', [RedirectController::class, 'check'])->name('check');
Route::get('/checking/{slug}', [RedirectController::class, 'checking'])->name('checking');

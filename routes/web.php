<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardImportController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardDuelmaController;

// Public Landing Page
Route::get('/', [LandingController::class, 'home'])->name('landing');
// Customer cart routes (require login)
Route::get('/cart', [CartController::class, 'view'])->middleware('auth');
Route::post('/cart/add/{item}', [CartController::class, 'add'])->middleware('auth');

// Admin dashboard (can expand later)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

// Default Breeze routes
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Cards
    Route::resource('cards', CardController::class);
    Route::post('/cards/{card}/restore', [CardController::class, 'restore'])->name('cards.restore');

    Route::get('/cards/import/yugipedia', [CardImportController::class, 'create'])->name('cards.import.create');
    Route::post('/cards/import/yugipedia', [CardImportController::class, 'store'])->name('cards.import.store');
});

//DuelMa
// Allows 60 requests per minute to your endpoint, while controller logic caps Fandom at 20/min
Route::post('/cards/fetch-wiki', [CardDuelmaController::class, 'fetchFromFandom'])
    ->middleware(['throttle:60,1'])
    ->name('cards.fetch-wiki');
require __DIR__ . '/auth.php';

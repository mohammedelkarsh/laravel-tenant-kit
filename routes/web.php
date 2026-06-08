<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/up', function () {
    return response()->noContent();
});

Route::get('/', function () {
    return view('landing');
});

Route::get('/workspaces/create', [TenantRegistrationController::class, 'create'])
    ->name('tenants.create');

Route::post('/workspaces', [TenantRegistrationController::class, 'store'])
    ->name('tenants.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->prefix('billing')->name('billing.')->group(function () {
    Route::get('/{tenant}', [BillingController::class, 'show'])->name('show');
    Route::post('/{tenant}/checkout/{plan}', [BillingController::class, 'checkout'])->name('checkout');
    Route::get('/{tenant}/portal', [BillingController::class, 'portal'])->name('portal');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

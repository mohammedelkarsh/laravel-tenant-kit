<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\AuthTokenController as TenantAuthTokenController;
use App\Http\Controllers\Api\Tenant\TeamController as TenantTeamApiController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Tenant\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\Auth\RegisteredUserController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InvitationController;
use App\Http\Controllers\Tenant\TeamController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/locale/{locale}', LocaleController::class)->name('tenant.locale.switch');

    Route::get('/', function () {
        if (auth()->check()) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.home', [
            'tenantName' => tenant('name'),
            'tenantId' => tenant('id'),
        ]);
    })->name('tenant.home');

    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('tenant.login');

        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('tenant.register');

        Route::post('register', [RegisteredUserController::class, 'store']);
    });

    Route::get('invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('tenant.invitations.accept');

    Route::middleware('auth')->group(function () {
        Route::get('dashboard', DashboardController::class)
            ->name('tenant.dashboard');

        Route::get('team', [TeamController::class, 'index'])
            ->name('tenant.team.index');

        Route::post('team/invitations', [TeamController::class, 'invite'])
            ->middleware('role:owner|admin')
            ->name('tenant.team.invite');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('tenant.logout');
    });
});

Route::middleware([
    'api',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api')->group(function () {
    Route::post('auth/token', [TenantAuthTokenController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [TenantAuthTokenController::class, 'me']);
        Route::delete('auth/token', [TenantAuthTokenController::class, 'destroy']);
        Route::get('team', [TenantTeamApiController::class, 'index']);
    });
});

<?php

use App\Http\Controllers\Api\Central\AuthTokenController;
use App\Http\Controllers\Api\Central\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [AuthTokenController::class, 'store'])
    ->middleware('throttle:api-auth');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [WorkspaceController::class, 'me'])
        ->middleware('abilities:user:read');

    Route::delete('/auth/token', [AuthTokenController::class, 'destroy']);

    Route::get('/workspaces', [WorkspaceController::class, 'index'])
        ->middleware('abilities:workspaces:read');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])
        ->middleware('abilities:workspaces:write');
    Route::get('/workspaces/{tenant}', [WorkspaceController::class, 'show'])
        ->middleware('abilities:workspaces:read');
    Route::get('/workspaces/{tenant}/subscription', [WorkspaceController::class, 'subscription'])
        ->middleware('abilities:workspaces:read');
});

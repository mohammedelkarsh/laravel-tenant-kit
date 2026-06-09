<?php

use App\Http\Controllers\Api\Central\AuthTokenController;
use App\Http\Controllers\Api\Central\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [AuthTokenController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [WorkspaceController::class, 'me']);
    Route::delete('/auth/token', [AuthTokenController::class, 'destroy']);

    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{tenant}', [WorkspaceController::class, 'show']);
});

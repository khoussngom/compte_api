<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;

// Public demo route used for documentation/testing on remote deployments
Route::get('/comptes-demo', function () {
    return response()->json([
        'success' => true,
        'data' => [
            [
                'id' => 1,
                'numero' => 'CPT-0001',
                'solde' => '1000.00',
                'type' => 'courant'
            ]
        ]
    ]);
});

// Public read-only endpoints (no auth required)
Route::get('/comptes', [CompteController::class, 'index']);
Route::get('/comptes/{numero}', [CompteController::class, 'show']);

// Health check (public) - useful when shell is not available on the host
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'index']);

// Protected endpoints (write or user-specific) — require authentication
Route::middleware(['auth:sanctum', 'rating'])->group(function () {
    Route::get('/comptes/mes-comptes', [CompteController::class, 'mesComptes']);
    Route::post('/comptes/{id}/archive', [CompteController::class, 'archive']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\UserController;

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

// Public account creation endpoint
Route::post('/accounts', [AccountController::class, 'store']);

// Generic message sending (uses the bound MessageServiceInterface)
Route::post('/messages', [\App\Http\Controllers\MessageController::class, 'send']);

// User listing endpoints
Route::get('/users/clients', [UserController::class, 'clients']);
Route::get('/users/admins', [UserController::class, 'admins']);

// Health check (public) - useful when shell is not available on the host
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'index']);

// Previously-protected endpoints (now public as requested)
// Note: the API is intentionally left unsecured per current requirement.
Route::get('/comptes/mes-comptes', [CompteController::class, 'mesComptes']);
Route::post('/comptes/{id}/archive', [CompteController::class, 'archive']);

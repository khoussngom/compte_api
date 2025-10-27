<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AccountController;

// Routes pour /api/v1/... (définies ici pour réutilisation dans api.php et web.php)

// Demo endpoint
Route::get('/v1/comptes-demo', function () {
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

// Public read-only endpoints
Route::get('/v1/comptes', [CompteController::class, 'index']);
Route::get('/v1/comptes/{numero}', [CompteController::class, 'show']);

// Public account creation endpoint
Route::post('/v1/accounts', [AccountController::class, 'store'])->middleware('logging');

// Generic message sending
Route::post('/v1/messages', [\App\Http\Controllers\MessageController::class, 'send'])->middleware('logging');

Route::get('/v1/users/clients', [UserController::class, 'clients']);
Route::get('/v1/users/admins', [UserController::class, 'admins']);

Route::get('/v1/health', [\App\Http\Controllers\HealthController::class, 'index']);

Route::get('/v1/comptes/mes-comptes', [CompteController::class, 'mesComptes']);
Route::post('/v1/comptes/{id}/archive', [CompteController::class, 'archive']);

// Blocage endpoints
Route::post('/v1/comptes/{compte}/bloquer', [CompteController::class, 'bloquer']);
Route::post('/v1/comptes/numero/{numero}/bloquer', [CompteController::class, 'bloquerByNumero']);

// Endpoint: récupérer un compte par numéro
Route::get('/v1/comptes/{numeroCompte}', [CompteController::class, 'showByNumero']);

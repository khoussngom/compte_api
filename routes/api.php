<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AccountController;


Route::domain(env('API_HOST', 'khouss.ngom'))->group(function () {

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
    Route::post('/accounts', [AccountController::class, 'store'])->middleware('logging');

    // Generic message sending (uses the bound MessageServiceInterface)
    Route::post('/messages', [\App\Http\Controllers\MessageController::class, 'send'])->middleware('logging');

    Route::get('/users/clients', [UserController::class, 'clients']);
    Route::get('/users/admins', [UserController::class, 'admins']);

    Route::get('/health', [\App\Http\Controllers\HealthController::class, 'index']);

    Route::get('/comptes/mes-comptes', [CompteController::class, 'mesComptes']);
    Route::post('/comptes/{id}/archive', [CompteController::class, 'archive']);

    Route::post('/v1/comptes/{compte}/bloquer', [CompteController::class, 'bloquer']);
    Route::post('/v1/comptes/numero/{numero}/bloquer', [CompteController::class, 'bloquerByNumero']);

});


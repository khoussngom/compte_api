<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AccountController;


Route::middleware('cors')->group(function () {

    Route::get('comptes-demo', function () {

        return (new AccountController())->successResponse([
            [
                'id' => 1,
                'numero' => 'CPT-0001',
                'solde' => '1000.00',
                'type' => 'courant'
            ]
        ], 'Demo comptes');
    });



    Route::get('comptes', [CompteController::class, 'index'])->middleware(['auth:api', \App\Http\Middleware\AccountAccessMiddleware::class]);

    Route::patch('comptes/{identifiant}', [CompteController::class, 'update'])->middleware(['auth:api', 'logging', \App\Http\Middleware\AccountAccessMiddleware::class]);

    Route::delete('comptes/{compteId}', [CompteController::class, 'destroy'])->middleware(['auth:api', 'logging', \App\Http\Middleware\AccountAccessMiddleware::class]);

    Route::get('comptes/{numero}', [CompteController::class, 'show'])->middleware(['auth:api', \App\Http\Middleware\AccountAccessMiddleware::class]);

    // Accept POST /api/v1/accounts (primary) and also POST /api/v1/comptes as an alias
    // to support callers that expect the French resource name.
    Route::post('accounts', [AccountController::class, 'store'])->middleware('logging');
    Route::post('comptes', [AccountController::class, 'store'])->middleware('logging');

    Route::post('messages', [\App\Http\Controllers\MessageController::class, 'send'])->middleware('logging');

    Route::get('users/clients', [UserController::class, 'clients']);
    Route::get('users/admins', [UserController::class, 'admins']);
    Route::get('users/client', [UserController::class, 'findClient']);
    Route::post('login', [\App\Http\Controllers\LoginController::class, 'login']);
    Route::post('clients/change-password', [\App\Http\Controllers\AuthController::class, 'changePassword'])->middleware('auth:api');

    Route::get('health', [\App\Http\Controllers\HealthController::class, 'index']);

    Route::get('comptes/mes-comptes', [CompteController::class, 'mesComptes'])->middleware('auth:api');
    Route::post('comptes/{id}/archive', [CompteController::class, 'archive'])->middleware(['auth:api', \App\Http\Middleware\AccountAccessMiddleware::class]);


    Route::post('comptes/{compte}/bloquer', [CompteController::class, 'bloquer'])->middleware(['auth:api', \App\Http\Middleware\AccountAccessMiddleware::class]);
    Route::post('comptes/numero/{numero}/bloquer', [CompteController::class, 'bloquerByNumero'])->middleware(['auth:api', \App\Http\Middleware\AccountAccessMiddleware::class]);

    Route::post('comptes/{compte}/bloquer-v2', [CompteController::class, 'bloquerV2'])->middleware(['auth:api','logging', \App\Http\Middleware\AccountAccessMiddleware::class]);
    Route::post('comptes/{compte}/debloquer', [CompteController::class, 'debloquer'])->middleware(['auth:api','logging', \App\Http\Middleware\AccountAccessMiddleware::class]);

    Route::get('comptes/{numeroCompte}', [CompteController::class, 'showByNumero'])->middleware(['auth:api', \App\Http\Middleware\AccountAccessMiddleware::class]);
});

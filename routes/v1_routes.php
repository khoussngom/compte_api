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



    Route::get('comptes', [CompteController::class, 'index']);

    Route::patch('comptes/{identifiant}', [CompteController::class, 'update'])->middleware('logging');

    Route::delete('comptes/{compteId}', [CompteController::class, 'destroy'])->middleware('logging');

    Route::get('comptes/{numero}', [CompteController::class, 'show']);

    Route::post('accounts', [AccountController::class, 'store'])->middleware('logging');

    Route::post('messages', [\App\Http\Controllers\MessageController::class, 'send'])->middleware('logging');

    Route::get('users/clients', [UserController::class, 'clients']);
    Route::get('users/admins', [UserController::class, 'admins']);

    Route::get('health', [\App\Http\Controllers\HealthController::class, 'index']);

    Route::get('comptes/mes-comptes', [CompteController::class, 'mesComptes']);
    Route::post('comptes/{id}/archive', [CompteController::class, 'archive']);


    Route::post('comptes/{compte}/bloquer', [CompteController::class, 'bloquer']);
    Route::post('comptes/numero/{numero}/bloquer', [CompteController::class, 'bloquerByNumero']);

    Route::post('comptes/{compte}/bloquer-v2', [CompteController::class, 'bloquerV2'])->middleware('logging');
    Route::post('comptes/{compte}/debloquer', [CompteController::class, 'debloquer'])->middleware('logging');

    Route::get('comptes/{numeroCompte}', [CompteController::class, 'showByNumero']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;

Route::middleware(['auth:sanctum', 'rating'])->group(function () {
    Route::get('/comptes', [CompteController::class, 'index']);
    Route::get('/comptes/mes-comptes', [CompteController::class, 'mesComptes']);
    Route::get('/comptes/{numero}', [CompteController::class, 'show']);
    Route::post('/comptes/{id}/archive', [CompteController::class, 'archive']);
});

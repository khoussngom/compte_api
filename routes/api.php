<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AccountController;


Route::domain(env('API_HOST', 'khouss.ngom'))->group(function () {
    Route::prefix('v1')->group(function () {
        require __DIR__ . '/v1_routes.php';
    });
});

Route::prefix('v1')->group(function () {
    require __DIR__ . '/v1_routes.php';
});



<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Documentation Swagger UI (static)
Route::get('/docs', function () {
    $path = public_path('docs/index.html');
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
});

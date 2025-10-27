<?php

use Illuminate\Support\Facades\Route;

// NOTE: v1 API routes are mounted in routes/api.php so they use the `api` middleware
// (stateless, no CSRF). We used to include them here for a custom local prefix but
// serving API routes from `routes/api.php` is the recommended approach.


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

// Temporary protected diagnostic endpoint (requires DIAG_SECRET or APP_DEBUG=true)
Route::get('/khouss.ngom/_diagnose', [\App\Http\Controllers\DiagController::class, 'index']);

// Mount v1 API routes under a local testing prefix so URLs like
// /khouss.ngom/api/v1/comptes work when the host is not the production API_HOST.
// This is useful for environments (Render, Netlify preview, etc.) where we cannot
// set a custom host header. The actual route definitions are in routes/v1_routes.php
// and are written relative to the mount point.
Route::prefix('khouss.ngom/api/v1')->group(function () {
    require __DIR__ . '/v1_routes.php';
});

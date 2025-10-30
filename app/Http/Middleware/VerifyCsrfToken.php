<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{

    // Exclude our API v1 endpoints from CSRF protection by default. This project
    // mounts the API under a variety of path shapes (with or without the
    // "khouss.ngom" mount segment and with/without an "api/" prefix). Add the
    // common variants here so production API clients (cURL, Swagger UI, etc.)
    // won't be blocked by VerifyCsrfToken.
    protected $except = [
        'khouss.ngom/api/v1/comptes/*',
        'khouss.ngom/api/v1/*',
        'api/v1/*',
        'v1/*',
    ];

    public function __construct()
    {
        if (app()->environment('local')) {
            $this->except = [
                'khouss.ngom/api/v1/*',
                'api/v1/*',
            ];
        }
    }


    protected function inExceptArray($request)
    {
        $path = $request->path();

        $matched = false;
        foreach ($this->except as $except) {
            if (empty($except)) {
                continue;
            }

            if ($request->is($except) || trim($except, '/') === $path) {
                $matched = true;
                break;
            }
        }

        if (app()->environment('local')) {
            Log::info('CSRF check', [
                'path' => $path,
                'except_patterns' => $this->except,
                'matched_exception' => $matched,
                'app_env' => app()->environment(),
            ]);
        }

        return $matched;
    }
}

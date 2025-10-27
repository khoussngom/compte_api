<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{

    protected $except = ['khouss.ngom/api/v1/comptes/*',];

    public function __construct()
    {
        // Only disable CSRF for our local API mount during local development
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

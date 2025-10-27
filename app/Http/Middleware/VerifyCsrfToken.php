<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * We exclude our local-mounted API prefixes so tools like Swagger UI
     * or curl can call PATCH/POST without a CSRF token during local dev.
     * Keep these patterns conservative and adjust for production as needed.
     *
     * @var array<int, string>
     */
    /**
     * We intentionally keep the default empty and only add the exemptions when
     * running in the local environment. This avoids weakening CSRF protection
     * in staging/production unintentionally.
     *
     * @var array<int,string>
     */
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

    /**
     * Override the parent to add logging for diagnostics.
     * This will record the request path and whether it matched an exemption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
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

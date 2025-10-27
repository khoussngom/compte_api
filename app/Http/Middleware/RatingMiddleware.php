<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Traits\ApiResponseTrait;

class RatingMiddleware
{
    use ApiResponseTrait;
    /**
     * Limite le nombre de requêtes par utilisateur (exemple: 100 requêtes/heure).
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->user() ? $request->user()->id : $request->ip();
        $key = 'rate_limit:' . $userId;
        $maxAttempts = 100;
        $decayMinutes = 60;

        $attempts = Cache::get($key, 0);
        if ($attempts >= $maxAttempts) {
            return $this->errorResponse('Trop de requêtes, veuillez patienter.', 429);
        }
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        return $next($request);
    }
}

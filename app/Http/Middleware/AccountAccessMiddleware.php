<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountAccessMiddleware
{
    /**
     * Enforce admin vs client access rules:
     * - admins can access everything
     * - clients can access only their own comptes or profile operations
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user() ?? Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->admin) {
            return $next($request);
        }

        $paramNames = ['compte', 'identifiant', 'id', 'numero', 'numeroCompte', 'compteId'];
        $found = null;
        foreach ($paramNames as $p) {
            if ($request->route($p) !== null) {
                $found = $request->route($p);
                break;
            }
        }

        if (! $found) {
            return $next($request);
        }

        $compte = null;
        if (is_string($found) && preg_match('/^[0-9a-fA-F\-]{36}$/', $found)) {
            $compte = Compte::find($found);
        }

        if (! $compte) {
            $compte = Compte::where('numero_compte', (string) $found)->first();
        }

        if (! $compte) {
            return response()->json(['message' => 'Compte introuvable'], 404);
        }

        $client = $compte->client;
        if (! $client) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ((string) ($client->user_id ?? '') !== (string) $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return $next($request);
    }
}

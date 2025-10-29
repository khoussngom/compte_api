<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Compte;

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

        // If user is admin, allow
        if ($user->admin) {
            return $next($request);
        }

        // For non-admins (clients) ensure the resource being touched belongs to them.
        // Try to resolve a compte identifier from common route parameter names.
        $paramNames = ['compte', 'identifiant', 'id', 'numero', 'numeroCompte', 'compteId'];
        $found = null;
        foreach ($paramNames as $p) {
            if ($request->route($p) !== null) {
                $found = $request->route($p);
                break;
            }
        }

        // If no compte identifier present, allow (controller should further scope listings)
        if (! $found) {
            return $next($request);
        }

        // Resolve compte by UUID id or by account number
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

        // Ensure compte's client belongs to authenticated user
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

<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    use ApiResponseTrait;
    public function clients(Request $request)
    {
        $users = User::whereHas('client')->with('client')->paginate(15);

        // Transform items into plain arrays with a predictable shape so the
        // frontend doesn't get unexpected nulls or Eloquent metadata.
        $items = array_map(function ($u) {
            if (is_object($u) && method_exists($u, 'toArray')) {
                $u = $u->toArray();
            }

            // Ensure client is an array with selected fields (avoid leaking internals)
            $client = null;
            if (is_array($u) && array_key_exists('client', $u) && !is_null($u['client'])) {
                $c = $u['client'];
                $client = [
                    'id' => $c['id'] ?? null,
                    'nom' => $c['nom'] ?? null,
                    'prenom' => $c['prenom'] ?? null,
                    'email' => $c['email'] ?? null,
                    'telephone' => $c['telephone'] ?? null,
                    'nci' => $c['nci'] ?? null,
                ];
            }

            return [
                'id' => $u['id'] ?? null,
                'nom' => $u['nom'] ?? null,
                'prenom' => $u['prenom'] ?? null,
                'email' => $u['email'] ?? null,
                'telephone' => $u['telephone'] ?? null,
                'client' => $client,
            ];
        }, $users->items());

        return $this->paginatedResponse($items, $users, 'Clients récupérés');
    }

    public function admins(Request $request)
    {
        $users = User::whereHas('admin')->with('admin')->paginate(15);
        return $this->paginatedResponse($users->items(), $users, 'Admins récupérés');
    }

    /**
     * Trouver un client par numéro de téléphone ou NCI (même endpoint).
     * Usage: GET /users/client?telephone=221770000000 ou /users/client?nci=1895200000231
     */
    public function findClient(Request $request)
    {
        $telephone = $request->query('telephone');
        $nci = $request->query('nci');

        if (empty($telephone) && empty($nci)) {
            return $this->errorResponse('Fournissez `telephone` ou `nci` en paramètre de requête.', 422);
        }

        $query = \App\Models\Client::query();

        if (!empty($telephone)) {
            // Normalize digits and compare on the 9 last digits to avoid false positives.
            $digits = preg_replace('/\D+/', '', $telephone);
            $last9 = $digits !== '' ? (strlen($digits) > 9 ? substr($digits, -9) : $digits) : '';

            $query->where(function($q) use ($telephone, $digits, $last9) {
                // exact matches (raw)
                $q->where('telephone', $telephone)
                  ->orWhere('telephone', '+'.$telephone);

                if (!empty($last9)) {
                    // Postgres: compare right(regexp_replace(telephone, '\D', '', 'g'), 9)
                    if (DB::getDriverName() === 'pgsql') {
                        $q->orWhereRaw("right(regexp_replace(telephone, '\\D', '', 'g'), 9) = ?", [$last9]);
                    } else {
                        // MySQL/other: remove common non-digit characters then compare right 9
                        // Build a REPLACE chain for common separators
                        $q->orWhereRaw(
                            "right(replace(replace(replace(replace(replace(replace(replace(replace(telephone, '+', ''), ' ', ''), '-', ''), '.', ''), '(', ''), ')', ''), '/', ''), '\\\\', ''), 9) = ?",
                            [$last9]
                        );
                    }
                }
            });
        } elseif (!empty($nci)) {
            $query->where('nci', $nci);
        }

        $client = $query->with('user', 'comptes')->first();

        if (! $client) {
            return $this->errorResponse('Client introuvable.', 404);
        }

        return $this->successResponse($client, 'Client trouvé');
    }
}

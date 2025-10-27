<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use App\Traits\ApiQueryTrait;
use Illuminate\Support\Carbon;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompteFilterRequest;
use App\Http\Requests\BlocageCompteRequest;
use App\Services\CompteLookupService;
use App\Http\Resources\CompteResource;
use App\Exceptions\CompteNotFoundException;
use App\Http\Requests\UpdateCompteRequest;

class CompteController extends Controller
{
    use ApiResponseTrait, ApiQueryTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     summary="Liste tous les comptes non archivés",
     *     tags={"Comptes"},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="statut", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="order", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Liste paginée des comptes")
     * )
     */
    public function index(CompteFilterRequest $request)
    {
        $comptes = $this->applyQueryFilters(Compte::query(), $request);
        $pagination = [
            'currentPage' => $comptes->currentPage(),
            'itemsPerPage' => $comptes->perPage(),
            'totalItems' => $comptes->total(),
            'totalPages' => $comptes->lastPage(),
        ];
        return $this->paginatedResponse($comptes->items(), $pagination, 'Liste récupérée avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/mes-comptes",
     *     summary="Liste les comptes du client connecté",
     *     tags={"Comptes"},
     *     @OA\Response(response=200, description="Liste des comptes du client")
     * )
     */
    public function mesComptes(Request $request)
    {
        $telephone = $request->user()->telephone ?? null;
        if (!$telephone) {
            return $this->errorResponse('Téléphone utilisateur manquant', 400);
        }
        $comptes = Compte::client($telephone)->get();
        return $this->successResponse($comptes, 'Comptes du client récupérés');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{numero}",
     *     summary="Détail d’un compte par numéro",
     *     tags={"Comptes"},
     *     @OA\Parameter(name="numero", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Détail du compte")
     * )
     */
    public function show($numero)
    {
        $compte = Compte::numero($numero)->first();
        if (!$compte) {
            return $this->notFoundResponse('Compte introuvable');
        }
        return $this->successResponse($compte, 'Détail du compte récupéré');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes/{id}/archive",
     *     summary="Archive un compte au lieu de le supprimer",
     *     tags={"Comptes"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Compte archivé")
     * )
     */
    public function archive($id)
    {
        $compte = Compte::find($id);
        if (!$compte) {
            return $this->notFoundResponse('Compte introuvable');
        }
        $compte->archived = true;
        $compte->save();
        return $this->successResponse($compte, 'Compte archivé avec succès');
    }

    /**
     * Bloquer un compte (enregistre la période et le motif). Le blocage effectif est appliqué
     * automatiquement par le job VerifierBlocageCompteJob lorsque la date_debut_blocage est atteinte.
     *
     * This method resolves the compte either by numeric id or by account number (numero_compte).
     */
    public function bloquer(BlocageCompteRequest $request, $compteIdentifier)
    {
        $compte = null;
        if (is_numeric($compteIdentifier)) {
            $compte = Compte::find($compteIdentifier);
        }

        if (!$compte) {
            $compte = Compte::where('numero_compte', $compteIdentifier)->first();
        }

        if (!$compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        $compte->date_debut_blocage = $request->input('date_debut_blocage');
        $compte->date_fin_blocage = $request->input('date_fin_blocage');
        $compte->motif_blocage = $request->input('motif_blocage');

        try {
            $start = Carbon::parse($compte->date_debut_blocage)->startOfDay();
            $end = Carbon::parse($compte->date_fin_blocage)->endOfDay();
            if (Carbon::now()->between($start, $end)) {
                $compte->statut_compte = 'bloqué';
                $compte->transactions()->update(['archived' => true]);
                Log::info('Compte bloqué immédiatement via endpoint', ['compte_id' => $compte->id]);
            }
        } catch (\Exception $e) {
        }

        $compte->save();

        return $this->successResponse($compte, 'Données de blocage enregistrées');
    }


    public function bloquerByNumero(BlocageCompteRequest $request, $numero)
    {
        return $this->bloquer($request, $numero);
    }

    /**
     * Récupère un compte par son numéro.
     */
    public function showByNumero($numeroCompte, CompteLookupService $service)
    {
        $compte = $service->findByNumero($numeroCompte);

        if (! $compte) {
            throw new CompteNotFoundException($numeroCompte);
        }

        $resource = new CompteResource($compte);
        return $this->successResponse($resource->toArray(request()), 'Détail du compte récupéré');
    }

    /**
     * Mettre à jour un compte (par id ou numero_compte). Tous les champs sont optionnels,
     * mais au moins un doit être fourni.
     */
    public function update(UpdateCompteRequest $request, $identifiant)
    {
        // resolve compte by id or numero
        $compte = Compte::find($identifiant);
        if (!$compte) {
            $compte = Compte::where('numero_compte', $identifiant)->first();
        }

        if (!$compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        $data = $request->validated();

        if (array_key_exists('titulaire', $data)) {
            // only set if the database actually has this column (some migrations differ by deployment)
            if (Schema::hasColumn('comptes', 'titulaire')) {
                $compte->titulaire = $data['titulaire'];
            } else {
                Log::warning('Attempt to set titulaire but comptes.titulaire column missing', ['compte_id' => $compte->id]);
            }
        }

        if (array_key_exists('informationsClient', $data)) {
            $clientData = $data['informationsClient'];

            // update the associated user (primary contact info) when present
            $user = $compte->user;
            if ($user) {
                if (!empty($clientData['telephone'])) {
                    $user->telephone = $clientData['telephone'];
                }
                if (!empty($clientData['email'])) {
                    $user->email = $clientData['email'];
                }
                if (!empty($clientData['password'])) {
                    $user->password = bcrypt($clientData['password']);
                }
                $user->save();
            }

            // If a separate Client record exists (some deployments have a clients table), update its fields too
            if ($compte->client) {
                $client = $compte->client;
                if (!empty($clientData['telephone'])) {
                    $client->telephone = $clientData['telephone'];
                }
                if (!empty($clientData['email'])) {
                    $client->email = $clientData['email'];
                }
                if (!empty($clientData['password'])) {
                    $client->password = bcrypt($clientData['password']);
                }
                if (!empty($clientData['nci'])) {
                    $client->nci = $clientData['nci'];
                }
                $client->save();
            }
        }

        $compte->save();

        return response()->json([
            'success' => true,
            'message' => 'Compte mis à jour avec succès',
            'data' => new CompteResource($compte->fresh('client'))
        ], 201);
    }

    /**
     * Soft-delete a compte: set statut to 'ferme', set date_fermeture, then soft delete
     */
    public function destroy($compteId)
    {
        $compte = Compte::find($compteId);
        if (! $compte) {
            $compte = Compte::where('numero_compte', $compteId)->first();
        }

        if (! $compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        // Update status and closure date
        $compte->statut_compte = 'ferme';
        $compte->date_fermeture = now();
        $compte->save();

        // Soft delete (requires SoftDeletes trait and deleted_at column)
        try {
            $compte->delete();
        } catch (\Throwable $e) {
            // If delete fails, still return success for the status update; log the error
            Log::warning('Soft delete failed for compte', ['id' => $compte->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé avec succès',
            'data' => [
                'id' => $compte->id,
                'numeroCompte' => $compte->numero_compte,
                'statut' => 'ferme',
                'dateFermeture' => optional($compte->date_fermeture)->toIso8601String(),
            ]
        ], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use FILTER_VALIDATE_BOOLEAN;
use Illuminate\Http\Request;
use App\Traits\ApiQueryTrait;
use Illuminate\Support\Carbon;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Jobs\MoveCompteToBufferJob;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlocageRequest;
use App\Services\CompteLookupService;
use App\Http\Resources\CompteResource;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\DeblocageRequest;
use App\Http\Requests\CompteFilterRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Exceptions\CompteNotFoundException;
use App\Http\Requests\BlocageCompteRequest;

class CompteController extends Controller
{
    use ApiResponseTrait, ApiQueryTrait;
    use \App\Traits\BlocageTrait;

    /**
    * @OA\Post(
    *     path="/api/v1/comptes/{id}/archive",
    *     summary="Archive un compte au lieu de le supprimer",
    *     tags={"Comptes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="Identifiant du compte : UUID (id) ou numéro de compte (numero_compte)",
    *         @OA\Schema(type="string", example="3fa85f64-5717-4562-b3fc-2c963f66afa6")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Compte archivé (job en file pour déplacement vers la base tampon)",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="string"),
    *             @OA\Property(property="numeroCompte", type="string"),
    *             @OA\Property(property="movedToBuffer", type="string", example="queued")
    *         )
    *     ),
    *     @OA\Response(response=404, description="Compte introuvable"),
    *     @OA\Response(response=400, description="Requête invalide"),
    *     @OA\Response(response=500, description="Erreur serveur lors de l'archivage")
    * )
     */
    public function index(CompteFilterRequest $request)
    {
        // Exclude permanently closed accounts (statut 'ferme') and
        // exclude savings accounts ('epargne') that are currently blocked.
        // Blocking is considered active when statut_compte = 'bloqué' OR
        // when the current date is between date_debut_blocage and date_fin_blocage.
        $now = now()->toDateTimeString();

        $baseQuery = Compte::query()
            ->where(function ($q) {
                $q->whereNull('statut_compte')
                  ->orWhere('statut_compte', '<>', 'ferme');
            })

            ->whereRaw(
                "NOT ((statut_compte = 'bloqué') OR (date_debut_blocage IS NOT NULL AND date_fin_blocage IS NOT NULL AND date_debut_blocage <= ? AND date_fin_blocage >= ?))",
                [$now, $now]
            );

        $comptes = $this->applyQueryFilters($baseQuery, $request);
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
    return $this->respondWithCollection($comptes, 'Comptes du client récupérés');
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
    public function show($numero, CompteLookupService $service)
    {
        // Use the lookup service which searches local DB first then neon_buffer.
        $compte = $service->findByNumero($numero);
        if (! $compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        return $this->respondWithResource($compte, 'Détail du compte récupéré');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes/{id}/archive",
     *     summary="Archive un compte au lieu de le supprimer",
     *     tags={"Comptes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="Identifiant du compte : UUID (id) ou numéro de compte (numero_compte)",
    *         @OA\Schema(type="string")
    *     ),
     *     @OA\Response(response=200, description="Compte archivé")
     * )
     */
    public function archive($id)
    {
        $compte = null;

        if (preg_match('/^[0-9a-fA-F-]{36}$/', (string) $id)) {
            $compte = Compte::find($id);
        }

        if (! $compte) {
            $compte = Compte::where('numero_compte', $id)->first();
        }

        if (!$compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        $compte->archived = true;
        $compte->statut_compte = $compte->statut_compte ?? 'archive';
        $compte->save();

        try {
            MoveCompteToBufferJob::dispatch($compte->id, 'archive')->afterCommit();
            Log::channel('comptes')->info('Archive requested, MoveCompteToBufferJob queued', ['id' => $compte->id]);
        } catch (\Throwable $e) {
            Log::channel('comptes')->error('Échec dispatch MoveCompteToBufferJob', ['id' => $compte->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Erreur lors de l’archivage (job)', 500);
        }

        return $this->respondWithData([
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'movedToBuffer' => 'queued',
        ], 'Compte archivé (job en file pour déplacement vers la base tampon)', 200);
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

        $type = strtolower(trim((string) ($compte->type_compte ?? $compte->type ?? '')));
        if ($type !== 'epargne') {
            return $this->errorResponse('Seuls les comptes de type epargne peuvent être bloqués.', 400);
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
        try {
            MoveCompteToBufferJob::dispatch($compte->id, 'bloquer')->afterCommit();
            Log::channel('comptes')->info('Bloquer requested: MoveCompteToBufferJob queued', ['id' => $compte->id]);
        } catch (\Throwable $e) {
            Log::channel('comptes')->warning('Échec dispatch MoveCompteToBufferJob lors du blocage', ['id' => $compte->id, 'error' => $e->getMessage()]);
        }

    return $this->respondWithResource($compte, 'Données de blocage enregistrées');
    }


    public function bloquerByNumero(BlocageCompteRequest $request, $numero)
    {
        return $this->bloquer($request, $numero);
    }

    /**
     * New API: bloquer using motif/duree/unite payload and immediate application when applicable.
    *
    * @OA\Post(
    *     path="/api/v1/comptes/{compte}/bloquer-v2",
    *     summary="Bloquer un compte (motif + durée)",
    *     tags={"Comptes"},
    *     @OA\Parameter(name="compte", in="path", required=true, @OA\Schema(type="string")),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"motif","duree","unite"},
    *             @OA\Property(property="motif", type="string", example="Suspicion de fraude"),
    *             @OA\Property(property="duree", type="integer", example=30),
    *             @OA\Property(property="unite", type="string", example="jours", description="jours|mois|annees")
    *         )
    *     ),
    *     @OA\Response(response=200, description="Compte bloqué / données renvoyées")
    * )
     */
    public function bloquerV2(BlocageRequest $request, $compteIdentifier)
    {
        $compte = null;
        if (is_numeric($compteIdentifier)) {
            $compte = Compte::find($compteIdentifier);
        }

        if (! $compte) {
            $compte = Compte::where('numero_compte', $compteIdentifier)->first();
        }

        if (! $compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        try {
            $motif = $request->input('motif');
            $duree = (int) $request->input('duree');
            $unite = $request->input('unite');

            $compte = $this->applyBlocage($compte, $motif, $duree, $unite, $request->user()->id ?? 'api');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
        try {
            MoveCompteToBufferJob::dispatch($compte->id, 'bloquer')->afterCommit();
            Log::channel('comptes')->info('BloquerV2 requested: MoveCompteToBufferJob queued', ['id' => $compte->id]);
        } catch (\Throwable $e) {
            Log::channel('comptes')->warning('Échec dispatch MoveCompteToBufferJob lors du blocage (v2)', ['id' => $compte->id, 'error' => $e->getMessage()]);
        }

    return $this->respondWithResource(new CompteResource($compte), 'Compte bloqué avec succès');
    }

    /**
     * Debloquer an account (manual endpoint).
    *
    * @OA\Post(
    *     path="/api/v1/comptes/{compte}/debloquer",
    *     summary="Débloquer un compte (motif)",
    *     tags={"Comptes"},
    *     @OA\Parameter(name="compte", in="path", required=true, @OA\Schema(type="string")),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"motif"},
    *             @OA\Property(property="motif", type="string", example="Contrôle terminé")
    *         )
    *     ),
    *     @OA\Response(response=200, description="Compte débloqué / données renvoyées")
    * )
     */
    public function debloquer(DeblocageRequest $request, $compteIdentifier)
    {
        $compte = null;
        if (is_numeric($compteIdentifier)) {
            $compte = Compte::find($compteIdentifier);
        }

        if (! $compte) {
            $compte = Compte::where('numero_compte', $compteIdentifier)->first();
        }

        if (! $compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        $type = strtolower(trim((string) ($compte->type_compte ?? $compte->type ?? '')));
        if ($type === 'cheque' || $type === 'courant') {
            return $this->errorResponse('Les comptes de type cheque ne peuvent pas être débloqués via cette API.', 400);
        }

        try {
            $motif = $request->input('motif');
            $compte = $this->applyDeblocage($compte, $motif, $request->user()->id ?? 'api');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

    return $this->respondWithResource(new CompteResource($compte), 'Compte débloqué avec succès');
    }


    public function showByNumero($numeroCompte, CompteLookupService $service)
    {
        $compte = $service->findByNumero($numeroCompte);

        if (! $compte) {
            throw new CompteNotFoundException($numeroCompte);
        }

        $resource = new CompteResource($compte);
    return $this->respondWithResource($resource, 'Détail du compte récupéré');
    }


    public function update(UpdateCompteRequest $request, $identifiant)
    {
        $compte = Compte::find($identifiant);
        if (!$compte) {
            $compte = Compte::where('numero_compte', $identifiant)->first();
        }

        if (!$compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        $data = $request->validated();

        if (array_key_exists('titulaire', $data)) {

            if (Schema::hasColumn('comptes', 'titulaire')) {
                $compte->titulaire = $data['titulaire'];
            } else {
                Log::warning('Attempt to set titulaire but comptes.titulaire column missing', ['compte_id' => $compte->id]);
            }
        }

        if (array_key_exists('informationsClient', $data)) {
            $clientData = $data['informationsClient'];

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

    return $this->respondWithResource(new CompteResource($compte->fresh('client')), 'Compte mis à jour avec succès', 201);
    }


    public function destroy($compteId)
    {
        $compte = Compte::find($compteId);
        if (! $compte) {
            $compte = Compte::where('numero_compte', $compteId)->first();
        }

        if (! $compte) {
            return $this->notFoundResponse('Compte introuvable');
        }

        $compte->statut_compte = 'ferme';
        $compte->date_fermeture = now();
        $compte->save();

        try {
            $compte->delete();
        } catch (\Throwable $e) {

            Log::warning('Soft delete failed for compte', ['id' => $compte->id, 'error' => $e->getMessage()]);
        }

        return $this->respondWithData([
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'statut' => 'ferme',
            'dateFermeture' => optional($compte->date_fermeture)->toIso8601String(),
        ], 'Compte supprimé avec succès', 200);
    }
}

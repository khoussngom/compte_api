<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Jobs\MoveCompteToBufferJob;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Support\Str;

class CompteService
{
    public function indexQuery($request)
    {
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

        $user = $request->user();
        if ($user && ! $user->admin) {
            $baseQuery->whereHas('client', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $baseQuery;
    }

    public function mesComptes($request)
    {
        $telephone = $request->user()->telephone ?? null;
        if (! $telephone) {
            return ['error' => 'Téléphone utilisateur manquant', 'status' => 400];
        }

        $comptes = Compte::client($telephone)->get();
        return ['data' => $comptes];
    }

    public function showByNumero($numero, $service)
    {
        $compte = $service->findByNumero($numero);
        if (! $compte) {
            return null;
        }
        return $compte;
    }

    public function archive($id)
    {
        $compte = null;
        if (preg_match('/^[0-9a-fA-F\-]{36}$/', (string) $id)) {
            $compte = Compte::find($id);
        }

        if (! $compte) {
            $compte = Compte::where('numero_compte', $id)->first();
        }

        if (! $compte) {
            return ['error' => 'Compte introuvable', 'status' => 404];
        }

        $type = strtolower((string) ($compte->type_compte ?? $compte->type ?? ''));
        $now = now();
        $blockStarted = $compte->date_debut_blocage && $compte->date_debut_blocage <= $now;
        $blockNotEnded = $compte->date_fin_blocage && $compte->date_fin_blocage > $now;
        $isBlocked = strtolower((string) ($compte->statut_compte ?? '')) === 'bloqué' || ($blockStarted && $blockNotEnded);

        if ($type !== 'epargne' || ! $isBlocked) {
            return ['error' => 'Seuls les comptes épargne actuellement en période de blocage peuvent être archivés.', 'status' => 400];
        }

        $compte->archived = true;
        $compte->statut_compte = 'archive';
        try { $compte->date_archivage = $now; } catch (\Throwable $_) {}
        $compte->save();

        try {
            MoveCompteToBufferJob::dispatch($compte->id, 'archive')->afterCommit();
            Log::channel('comptes')->info('Archive requested, MoveCompteToBufferJob queued', ['id' => $compte->id]);
        } catch (\Throwable $e) {
            Log::channel('comptes')->error('Échec dispatch MoveCompteToBufferJob', ['id' => $compte->id, 'error' => $e->getMessage()]);
            return ['error' => 'Erreur lors de l’archivage (job)', 'status' => 500];
        }

        return ['data' => ['id' => $compte->id, 'numeroCompte' => $compte->numero_compte, 'movedToBuffer' => 'queued']];
    }

    public function bloquer($compteIdentifier, $request)
    {
        $compte = null;
        if (is_numeric($compteIdentifier)) {
            $compte = Compte::find($compteIdentifier);
        }
        if (! $compte) {
            $compte = Compte::where('numero_compte', $compteIdentifier)->first();
        }
        if (! $compte) {
            return ['error' => 'Compte introuvable', 'status' => 404];
        }

        $type = strtolower(trim((string) ($compte->type_compte ?? $compte->type ?? '')));
        if ($type !== 'epargne') {
            return ['error' => 'Seuls les comptes de type epargne peuvent être bloqués.', 'status' => 400];
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
            }
        } catch (\Exception $e) {}

        $compte->save();
        try { MoveCompteToBufferJob::dispatch($compte->id, 'bloquer')->afterCommit(); } catch (\Throwable $_) {}

        return ['data' => $compte];
    }

    public function bloquerV2($compteIdentifier, $request)
    {
        // Delegate to bloquer for now; controller previously calls applyBlocage trait.
        return $this->bloquer($compteIdentifier, $request);
    }

    public function debloquer($compteIdentifier, $request)
    {
        $compte = null;
        if (is_numeric($compteIdentifier)) { $compte = Compte::find($compteIdentifier); }
        if (! $compte) { $compte = Compte::where('numero_compte', $compteIdentifier)->first(); }
        if (! $compte) { return ['error' => 'Compte introuvable', 'status' => 404]; }

        $type = strtolower(trim((string) ($compte->type_compte ?? $compte->type ?? '')));
        if ($type === 'cheque' || $type === 'courant') {
            return ['error' => 'Les comptes de type cheque ne peuvent pas être débloqués via cette API.', 'status' => 400];
        }

        try {
            $motif = $request->input('motif');
            // simple debloquage: clear statut
            $compte->statut_compte = 'actif';
            $compte->motif_blocage = null;
            $compte->date_debut_blocage = null;
            $compte->date_fin_blocage = null;
            $compte->save();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'status' => 400];
        }

        return ['data' => $compte];
    }

    public function update($identifiant, $request)
    {
        $compte = null;
        if (preg_match('/^[0-9a-fA-F-]{36}$/', (string) $identifiant)) { $compte = Compte::find($identifiant); }
        if (! $compte) { $compte = Compte::where('numero_compte', $identifiant)->first(); }
        if (! $compte) { return ['error' => 'Compte introuvable', 'status' => 404]; }

        $data = $request->validated();

        if (array_key_exists('titulaire', $data)) { $compte->titulaire = $data['titulaire'] ?? $compte->titulaire; }

        if (array_key_exists('informationsClient', $data)) {
            $clientData = $data['informationsClient'];
            $user = $compte->user;
            if ($user) {
                if (! empty($user->force_password_change)) {
                    $required = ['nom','prenom','telephone','email','password'];
                    foreach ($required as $r) { if (empty($clientData[$r])) { return ['error' => "Le champ {$r} est requis pour compléter la première connexion.", 'status' => 422]; } }
                    $user->nom = $clientData['nom'];
                    $user->prenom = $clientData['prenom'];
                    $user->telephone = $clientData['telephone'];
                    $user->email = $clientData['email'];
                    $user->password = bcrypt($clientData['password']);
                    $user->force_password_change = false;
                    $user->save();
                } else {
                    if (!empty($clientData['telephone'])) { $user->telephone = $clientData['telephone']; }
                    if (!empty($clientData['email'])) { $user->email = $clientData['email']; }
                    if (!empty($clientData['password'])) { $user->password = bcrypt($clientData['password']); }
                    $user->save();
                }
            }

            if ($compte->client) {
                $client = $compte->client;
                if (!empty($clientData['telephone'])) { $client->telephone = $clientData['telephone']; }
                if (!empty($clientData['email'])) { $client->email = $clientData['email']; }
                if (!empty($clientData['password'])) { $client->password = bcrypt($clientData['password']); }
                if (!empty($clientData['nci'])) { $client->nci = $clientData['nci']; }
                $client->save();
            }
        }

        $compte->save();
        return ['data' => $compte->fresh('client')];
    }

    public function destroy($compteId)
    {
        $compte = Compte::find($compteId) ?: Compte::where('numero_compte', $compteId)->first();
        if (! $compte) { return ['error' => 'Compte introuvable', 'status' => 404]; }

        $compte->statut_compte = 'ferme';
        $compte->date_fermeture = now();
        $compte->save();
        try { $compte->delete(); } catch (\Throwable $e) { Log::warning('Soft delete failed for compte', ['id' => $compte->id, 'error' => $e->getMessage()]); }

        return ['data' => ['id' => $compte->id, 'numeroCompte' => $compte->numero_compte, 'statut' => 'ferme', 'dateFermeture' => optional($compte->date_fermeture)->toIso8601String()]];
    }
}

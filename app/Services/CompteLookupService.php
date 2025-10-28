<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompteLookupService
{
    /**
     * Recherche un compte localement, puis dans la source serverless si non trouvé.
     * Retourne une instance de Compte ou null.
     */
    public function findByNumero(string $numeroCompte): ?Compte
    {
        // Recherche locale
        try {
            $compte = Compte::where('numero_compte', $numeroCompte)->first();
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la recherche locale de compte: '.$e->getMessage());
            $compte = null;
        }

        if ($compte) {
            // Retour local prioritaire
            return $compte;
        }

        // Fallback: rechercher dans la base serverless distante
        return $this->findInServerless($numeroCompte);
    }

    /**
     * Requête vers la base serverless (API distante). Placeholder —
     * implémentation dépend de l'intégration réelle (HTTP client, key, etc.).
     */
    private function findInServerless(string $numeroCompte): ?Compte
    {
        // Attempt to read from the configured neon buffer Postgres connection.
        try {
            $row = DB::connection('neon_buffer')->table('comptes')
                ->where('numero_compte', $numeroCompte)
                ->first();

            if (! $row) {
                Log::info("Compte not found in neon_buffer: {$numeroCompte}");
                return null;
            }

            // Map the stdClass row to a Compte model instance for read-only use.
            $data = (array) $row;
            $compte = new Compte();
            // Set raw attributes and mark as existing to avoid inserts on save().
            $compte->setRawAttributes($data, true);
            $compte->exists = true;

            // Ensure the model's date casts and attributes are applied on access.
            return $compte;
        } catch (\Throwable $e) {
            Log::error('Erreur recherche neon_buffer: ' . $e->getMessage());
            return null;
        }
    }
}

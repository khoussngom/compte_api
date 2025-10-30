<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteLookupService
{

    public function findByNumero(string $numeroCompte): ?Compte
    {

        try {
            $compte = Compte::where('numero_compte', $numeroCompte)->first();
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la recherche locale de compte: '.$e->getMessage());
            $compte = null;
        }

        if ($compte) {

            return $compte;
        }


        return $this->findInServerless($numeroCompte);
    }


    private function findInServerless(string $numeroCompte): ?Compte
    {
        try {
            $row = DB::connection('neon_buffer')->table('comptes')
                ->where('numero_compte', $numeroCompte)
                ->first();

            if (! $row) {
                Log::info("Compte not found in neon_buffer: {$numeroCompte}");
                return null;
            }

            $data = (array) $row;
            $compte = new Compte();

            $compte->setRawAttributes($data, true);
            $compte->exists = true;


            return $compte;
        } catch (\Throwable $e) {
            Log::error('Erreur recherche neon_buffer: ' . $e->getMessage());
            return null;
        }
    }
}

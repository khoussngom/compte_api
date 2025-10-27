<?php
namespace App\Traits;

use App\Models\Compte;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

trait BlocageTrait
{
    protected function computeEndDate(int $duree, string $unite)
    {
        $now = now();
        switch ($unite) {
            case 'jours':
                return $now->copy()->addDays($duree);
            case 'mois':
                return $now->copy()->addMonths($duree);
            case 'annees':
                return $now->copy()->addYears($duree);
            default:
                return $now->copy()->addDays($duree);
        }
    }

    public function applyBlocage(Compte $compte, string $motif, int $duree, string $unite, ?string $initiator = null)
    {
        if (($compte->statut_compte ?? $compte->statut) !== 'actif') {
            throw new \App\Exceptions\CompteDejaBloqueException('Le compte n\'est pas actif et ne peut être bloqué.');
        }

        $start = now();
        $end = $this->computeEndDate($duree, $unite);

        $compte->motif_blocage = $motif;
        $compte->date_debut_blocage = $start;
        $compte->date_fin_blocage = $end;
        $compte->statut_compte = 'bloque';
        $compte->save();

        Log::channel('comptes')->info('Compte bloqué', ['compte_id' => $compte->id, 'motif' => $motif, 'duree' => $duree, 'unite' => $unite, 'initiator' => $initiator]);

        return $compte->fresh();
    }

    public function applyDeblocage(Compte $compte, string $motif, ?string $initiator = null)
    {
        if (($compte->statut_compte ?? $compte->statut) !== 'bloque') {
            throw new \App\Exceptions\CompteNotBloqueException('Le compte n\'est pas bloqué.');
        }

        $compte->statut_compte = 'actif';
        $compte->date_deblocage = now();
        $compte->save();

        Log::channel('comptes')->info('Compte débloqué', ['compte_id' => $compte->id, 'motif' => $motif, 'initiator' => $initiator]);

        return $compte->fresh();
    }
}

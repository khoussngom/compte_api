<?php
namespace App\Traits;

use App\Models\Compte;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Jobs\RestoreFromBufferJob;

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
        // Only savings accounts ('epargne') can be blocked
        $type = strtolower(trim((string) ($compte->type_compte ?? $compte->type ?? '')));
        if ($type !== 'epargne') {
            throw new \Exception('Seuls les comptes de type epargne peuvent être bloqués.');
        }

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
        // Prevent debloquage for cheque accounts — business rule: cheques cannot be
        // blocked or unblocked via the API.
        $type = strtolower(trim((string) ($compte->type_compte ?? $compte->type ?? '')));
        if ($type === 'cheque' || $type === 'courant') {
            throw new \Exception('Les comptes de type cheque ne peuvent pas être débloqués via cette API.');
        }

        if (($compte->statut_compte ?? $compte->statut) !== 'bloque') {
            throw new \App\Exceptions\CompteNotBloqueException('Le compte n\'est pas bloqué.');
        }

        $compte->statut_compte = 'actif';
        $compte->date_deblocage = now();
        $compte->save();

        Log::channel('comptes')->info('Compte débloqué', ['compte_id' => $compte->id, 'motif' => $motif, 'initiator' => $initiator]);

        // Attempt to restore from buffer Neon asynchronously (in case the compte was moved)
        try {
            RestoreFromBufferJob::dispatch($compte->numero_compte);
        } catch (\Throwable $e) {
            Log::channel('comptes')->warning('Failed to dispatch RestoreFromBufferJob', ['numero_compte' => $compte->numero_compte, 'error' => $e->getMessage()]);
        }

        return $compte->fresh();
    }
}

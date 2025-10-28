<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Traits\BlocageTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class VerifierBlocageCompteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BlocageTrait;


    public function handle()
    {
        $this->archiverComptesBloques();
        $this->bloquerComptesAutomatiquement();
        $this->debloquerComptesAutomatiquement();
    }


    protected function debloquerComptesAutomatiquement()
    {
        $now = now();

        $comptes = Compte::where('statut_compte', 'bloqué')
            ->whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', $now)
            ->get();

        foreach ($comptes as $compte) {
            try {
                $this->applyDeblocage($compte, 'auto-deblocage', 'system');
                Log::channel('comptes')->info('Compte débloqué automatiquement', ['compte_id' => $compte->id]);
            } catch (\Exception $e) {
                Log::channel('comptes')->error('Erreur lors du déblocage automatique', ['compte_id' => $compte->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function archiverComptesBloques()
    {
        $threshold = now()->subDays(365);

        $comptes = Compte::where('statut_compte', 'bloqué')
            ->whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', $threshold)
            ->get();

        foreach ($comptes as $compte) {
            try {
                $compte->statut_compte = 'ferme';
                $compte->date_fermeture = now();
                $compte->archived = true;
                $compte->save();

                if (method_exists($compte, 'delete')) {
                    $compte->delete();
                }

                Log::channel('comptes')->info('Compte archivé après blocage prolongé', ['compte_id' => $compte->id]);
            } catch (\Exception $e) {
                Log::channel('comptes')->error("Erreur lors de l'archivage du compte bloqué", ['compte_id' => $compte->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function bloquerComptesAutomatiquement()
    {
        $today = now()->startOfDay();

        $comptes = Compte::whereNotNull('date_debut_blocage')
            ->whereDate('date_debut_blocage', '<=', $today)
            ->where(function ($q) {
                $q->whereNull('statut_compte')->orWhere('statut_compte', '!=', 'bloqué');
            })->get();

        foreach ($comptes as $compte) {
            try {
                $compte->statut_compte = 'bloqué';
                $compte->save();

                $compte->transactions()->update(['archived' => true]);

                Log::channel('comptes')->info('Compte bloqué automatiquement', ['compte_id' => $compte->id]);
            } catch (\Exception $e) {
                Log::channel('comptes')->error('Erreur lors du blocage automatique du compte', ['compte_id' => $compte->id, 'error' => $e->getMessage()]);
            }
        }
    }
}

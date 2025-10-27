<?php
namespace App\Jobs;

use App\Models\Compte;
use App\Traits\BlocageTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DebloquerCompteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, BlocageTrait;

    /**
     * Execute the job.
     * Find comptes whose block end date has passed and deblock them.
     */
    public function handle()
    {
        $now = now();
        $comptes = Compte::where('statut_compte', 'bloque')
            ->whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', $now)
            ->get();

        foreach ($comptes as $compte) {
            try {
                $this->applyDeblocage($compte, 'Déblocage automatique programmé', 'scheduler');
            } catch (\Exception $e) {
                Log::channel('comptes')->error('Erreur lors du déblocage automatique', ['compte_id' => $compte->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DebloquerCompteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $today = now()->startOfDay();

        $comptes = Compte::whereNotNull('date_fin_blocage')
            ->whereDate('date_fin_blocage', '<=', $today)
            ->where('statut_compte', 'bloqué')
            ->get();

        foreach ($comptes as $compte) {
            try {
                $compte->statut_compte = 'actif';
                $compte->save();

                $compte->transactions()->update(['archived' => false]);

                Log::info('Compte débloqué automatiquement', ['compte_id' => $compte->id]);
            } catch (\Exception $e) {
                Log::error('Erreur lors du déblocage automatique du compte', ['compte_id' => $compte->id, 'error' => $e->getMessage()]);
            }
        }
    }
}

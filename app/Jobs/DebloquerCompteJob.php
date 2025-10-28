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

class DebloquerCompteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, BlocageTrait;

    /**
     * Execute the job.
     * Find comptes whose block end date has passed and deblock them.
     */
    public function handle()
    {
        $today = now();

        $comptes = Compte::whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', $today)
            ->where('statut_compte', 'bloqué')
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

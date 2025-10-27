<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ArchiveComptesToBufferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function handle()
    {
        $comptes = Compte::where('archived', true)
            ->orWhere('statut_compte', 'ferme')
            ->get();

        foreach ($comptes as $compte) {
            try {
                $data = $compte->toArray();
                unset($data['id']);


                DB::connection('neon_buffer')->table('comptes')->updateOrInsert([
                    'numero_compte' => $compte->numero_compte
                ], $data);


                if (method_exists($compte, 'forceDelete')) {
                    $compte->forceDelete();
                } else {
                    $compte->delete();
                }

                Log::channel('comptes')->info('Compte transféré vers buffer Neon', ['numero_compte' => $compte->numero_compte]);
            } catch (\Throwable $e) {
                Log::channel('comptes')->error('Erreur lors du transfert vers buffer Neon', ['numero_compte' => $compte->numero_compte, 'error' => $e->getMessage()]);
            }
        }
    }
}

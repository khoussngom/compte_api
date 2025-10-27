<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreFromBufferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $numeroCompte;

    public function __construct(?string $numeroCompte = null)
    {
        $this->numeroCompte = $numeroCompte;
    }

    /**
     * Restore comptes from the neon buffer to the primary DB.
     * If a numeroCompte is provided, restore only that compte; otherwise restore comptes
     * whose date_deblocage <= now() (i.e., ready to be returned to main DB) or that are not archived.
     */
    public function handle()
    {
        try {
            $conn = DB::connection('neon_buffer');

            $query = $conn->table('comptes');

            if ($this->numeroCompte) {
                $rows = $query->where('numero_compte', $this->numeroCompte)->get();
            } else {
                $now = now();
                // rows where deblocage is due OR statut not 'ferme' (possible unarchive)
                $rows = $query->where(function ($q) use ($now) {
                    $q->whereNotNull('date_deblocage')->where('date_deblocage', '<=', $now);
                })->orWhere('archived', false)->get();
            }

            foreach ($rows as $row) {
                try {
                    $data = (array) $row;
                    // preserve original values except id (let primary DB generate id)
                    unset($data['id']);

                    // upsert into primary DB by numero_compte
                    DB::table('comptes')->updateOrInsert([
                        'numero_compte' => $data['numero_compte']
                    ], $data);

                    // remove from buffer
                    $conn->table('comptes')->where('numero_compte', $data['numero_compte'])->delete();

                    Log::channel('comptes')->info('Compte restauré depuis buffer Neon', ['numero_compte' => $data['numero_compte']]);
                } catch (\Throwable $e) {
                    Log::channel('comptes')->error('Erreur lors de la restauration depuis buffer Neon', ['row' => $row, 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::channel('comptes')->error('Erreur de connexion au buffer Neon lors de la restauration', ['error' => $e->getMessage()]);
        }
    }
}

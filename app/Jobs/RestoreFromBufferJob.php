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

class RestoreFromBufferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $numeroCompte;

    public function __construct(?string $numeroCompte = null)
    {
        $this->numeroCompte = $numeroCompte;
    }

    public function handle()
    {
        try {
            $conn = DB::connection('neon_buffer');

            $query = $conn->table('comptes');

            if ($this->numeroCompte) {
                $rows = $query->where('numero_compte', $this->numeroCompte)->get();
            } else {
                $now = now();
                $rows = $query->where(function ($q) use ($now) {
                    $q->whereNotNull('date_deblocage')->where('date_deblocage', '<=', $now);
                })->orWhere('archived', false)->get();
            }

            foreach ($rows as $row) {
                try {
                    $data = (array) $row;
                    unset($data['id']);

                    DB::table('comptes')->updateOrInsert([
                        'numero_compte' => $data['numero_compte']
                    ], $data);

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

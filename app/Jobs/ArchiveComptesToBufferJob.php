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
    /**
     * Optional identifier (uuid id or numero_compte). If null, job will process all archived/closed comptes.
     * @var string|null
     */
    protected $identifier;

    public function __construct(?string $identifier = null)
    {
        $this->identifier = $identifier;
    }

    public function handle()
    {
    $query = Compte::withoutGlobalScope('non_archived');

        if ($this->identifier) {

            $identifier = $this->identifier;
            if (preg_match('/^[0-9a-fA-F\-]{36}$/', $identifier)) {
                $query->where('id', $identifier);
            } else {
                $query->where('numero_compte', $identifier);
            }
        } else {
            // Select only savings accounts ('epargne') that are currently blocked
            // and whose blocking period has started (date_debut_blocage <= now)
            // and not yet finished (date_fin_blocage > now()).
            $now = now();
            $query->where('type_compte', 'epargne')
                  ->where('statut_compte', 'bloqué')
                  ->whereDate('date_debut_blocage', '<=', $now)
                  ->whereDate('date_fin_blocage', '>', $now);
        }

        $comptes = $query->get();

        foreach ($comptes as $compte) {
            try {

                // Ensure the compte is marked as archived and has an archive date
                // before copying to the buffer. This reflects the business rule
                // that eligible blocked savings accounts are archived when their
                // blocking period has started.
                if ((string) ($compte->statut_compte ?? '') !== 'archive') {
                    $compte->statut_compte = 'archive';
                    // Set date_archivage when the column exists; setting the
                    // attribute directly is harmless if it doesn't persist.
                    try {
                        $compte->date_archivage = now();
                    } catch (\Exception $e) {
                        // ignore if attribute not present
                    }
                    $compte->save();
                }

                $data = $compte->getAttributes();

                if ($compte->created_at) {
                    $data['created_at'] = $compte->created_at->toDateTimeString();
                }
                if ($compte->updated_at) {
                    $data['updated_at'] = $compte->updated_at->toDateTimeString();
                }

                $where = [];
                if (!empty($data['id'])) {
                    $where['id'] = $data['id'];
                } else {
                    $where['numero_compte'] = $compte->numero_compte;
                }

                $bufferEnabled = filter_var(env('NEON_BUFFER_ENABLED', false), \FILTER_VALIDATE_BOOLEAN);
                if ($bufferEnabled) {
                    try {
                        DB::connection('neon_buffer')->table('comptes')->updateOrInsert($where, $data);
                        Log::channel('comptes')->info('Compte transféré vers buffer Neon', ['numero_compte' => $compte->numero_compte, 'id' => $compte->id]);
                    } catch (\Throwable $e) {
                        Log::channel('comptes')->error('Erreur lors du transfert vers buffer Neon', ['numero_compte' => $compte->numero_compte ?? null, 'identifier' => $this->identifier, 'error' => $e->getMessage()]);
                    }
                } else {
                    Log::channel('comptes')->info('Archive job skipped buffer insert (disabled by config)', ['id' => $compte->id, 'numero' => $compte->numero_compte]);
                }

                try {
                    $primaryConnection = $compte->getConnectionName() ?? config('database.default');
                    DB::connection($primaryConnection)->table($compte->getTable())->where('id', $compte->id)->delete();
                } catch (\Throwable $e) {
                    try {
                        $compte->forceDelete();
                    } catch (\Throwable $ex) {
                        Log::channel('comptes')->warning('Impossible de supprimer physiquement le compte, il restera soft-deleted', ['id' => $compte->id, 'error' => $ex->getMessage()]);
                    }
                }

                Log::channel('comptes')->info('Compte transféré vers buffer Neon', ['numero_compte' => $compte->numero_compte, 'id' => $compte->id]);
            } catch (\Throwable $e) {
                Log::channel('comptes')->error('Erreur lors du transfert vers buffer Neon', ['numero_compte' => $compte->numero_compte ?? null, 'identifier' => $this->identifier, 'error' => $e->getMessage()]);
            }
        }
    }
}

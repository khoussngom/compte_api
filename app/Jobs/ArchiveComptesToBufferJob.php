<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use const FILTER_VALIDATE_BOOLEAN;
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
    // bypass the global "non_archived" scope so we can find comptes that were just marked archived
    $query = Compte::withoutGlobalScope('non_archived');

        if ($this->identifier) {
            // Avoid querying the UUID `id` column with a non-UUID value which causes
            // a Postgres invalid input syntax for type uuid (SQLSTATE[22P02]).
            // Detect whether the identifier looks like a UUID and query the proper column.
            $identifier = $this->identifier;
            if (preg_match('/^[0-9a-fA-F\-]{36}$/', $identifier)) {
                $query->where('id', $identifier);
            } else {
                $query->where('numero_compte', $identifier);
            }
        } else {
            $query->where('archived', true)
                  ->orWhere('statut_compte', 'ferme');
        }

        $comptes = $query->get();

        foreach ($comptes as $compte) {
            try {
                // preserve attributes including id when copying to buffer
                $data = $compte->getAttributes();

                // ensure timestamps are plain strings / nulls
                if ($compte->created_at) {
                    $data['created_at'] = $compte->created_at->toDateTimeString();
                }
                if ($compte->updated_at) {
                    $data['updated_at'] = $compte->updated_at->toDateTimeString();
                }

                // Use the primary key (id) to keep the same UUID in buffer; fallback to numero_compte if id missing
                $where = [];
                if (!empty($data['id'])) {
                    $where['id'] = $data['id'];
                } else {
                    $where['numero_compte'] = $compte->numero_compte;
                }

                // Prefer the default DB when running tests (app()->runningUnitTests() can be unreliable in some contexts)
                // Buffer persistence is controlled by the NEON_BUFFER_ENABLED env var (default: false).
                $bufferEnabled = filter_var(env('NEON_BUFFER_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
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

                // Note: we intentionally avoid copying related models here to keep buffer transfer simple.
                // If full relational copy is required, implement a robust migration that copies clients/users
                // and handles foreign keys in the buffer DB.

                // Physically remove the original row from the primary DB to ensure it no longer appears
                // even when SoftDeletes are enabled. Use a direct table delete on the model's connection.
                try {
                    $primaryConnection = $compte->getConnectionName() ?? config('database.default');
                    DB::connection($primaryConnection)->table($compte->getTable())->where('id', $compte->id)->delete();
                } catch (\Throwable $e) {
                    // Fallback to model forceDelete if direct delete fails
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

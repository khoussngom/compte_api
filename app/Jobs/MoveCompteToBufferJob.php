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

class MoveCompteToBufferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $compteId;
    protected string $context;

    public function __construct(string $compteId, string $context = 'archive')
    {
        $this->compteId = $compteId;
        $this->context = $context;
    }

    public function handle()
    {
        try {
            $compte = Compte::withoutGlobalScope('non_archived')->find($this->compteId);
            if (! $compte) {
                Log::channel('comptes')->warning('MoveCompteToBufferJob: compte introuvable', ['id' => $this->compteId]);
                return;
            }

            $data = $compte->getAttributes();
            if ($compte->created_at) {
                $data['created_at'] = $compte->created_at->toDateTimeString();
            }
            if ($compte->updated_at) {
                $data['updated_at'] = $compte->updated_at->toDateTimeString();
            }

            $where = !empty($data['id']) ? ['id' => $data['id']] : ['numero_compte' => $data['numero_compte']];

            $casts = method_exists($compte, 'getCasts') ? $compte->getCasts() : [];
            $booleanAttrs = [];
            foreach ($casts as $attr => $castType) {
                if (in_array($castType, ['boolean', 'bool'], true)) {
                    $booleanAttrs[] = $attr;
                }
            }
            $booleanAttrs = array_unique(array_merge($booleanAttrs, ['is_admin_managed']));

            foreach ($booleanAttrs as $attr) {
                if (!array_key_exists($attr, $data)) {
                    continue;
                }
                $raw = $data[$attr];
                $norm = filter_var((string) $raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($norm === null) {
                    if ((string) $raw === 't') {
                        $norm = true;
                    } elseif ((string) $raw === 'f') {
                        $norm = false;
                    } else {
                        $norm = (bool) $raw;
                    }
                }
                $data[$attr] = $norm ? 't' : 'f';
            }

            $bufferEnabled = filter_var(env('NEON_BUFFER_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
            $inserted = false;
            if ($bufferEnabled) {
                try {
                    $inserted = DB::connection('neon_buffer')->table('comptes')->updateOrInsert($where, $data);
                    Log::channel('comptes')->info('MoveCompteToBufferJob: compte écrit dans neon_buffer', ['id' => $compte->id, 'context' => $this->context, 'inserted' => $inserted]);
                } catch (\Throwable $e) {
                    $inserted = false;
                    Log::channel('comptes')->error('MoveCompteToBufferJob: échec écriture neon_buffer', ['id' => $compte->id, 'error' => $e->getMessage()]);
                }
            } else {
                Log::channel('comptes')->info('MoveCompteToBufferJob: buffer disabled, skip write', ['id' => $compte->id, 'context' => $this->context]);
            }

            if ($this->context === 'archive') {
                if ($inserted) {
                    try {
                        $primaryConnection = $compte->getConnectionName() ?? config('database.default');
                        DB::connection($primaryConnection)->table($compte->getTable())->where('id', $compte->id)->delete();
                        Log::channel('comptes')->info('MoveCompteToBufferJob: compte supprimé de la DB primaire', ['id' => $compte->id]);
                    } catch (\Throwable $e) {
                        try {
                            $compte->forceDelete();
                            Log::channel('comptes')->info('MoveCompteToBufferJob: compte forceDelete effectué', ['id' => $compte->id]);
                        } catch (\Throwable $ex) {
                            Log::channel('comptes')->error('MoveCompteToBufferJob: impossible de supprimer le compte', ['id' => $compte->id, 'error' => $ex->getMessage()]);
                        }
                    }
                } else {
                    Log::channel('comptes')->warning('MoveCompteToBufferJob: write to buffer failed or disabled, skipping delete', ['id' => $compte->id, 'context' => $this->context]);
                }
            }

        } catch (\Throwable $e) {
            Log::channel('comptes')->error('MoveCompteToBufferJob général: erreur', ['id' => $this->compteId, 'error' => $e->getMessage()]);
        }
    }
}

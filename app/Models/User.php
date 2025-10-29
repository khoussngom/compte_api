<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasUuids;
    use HasApiTokens;

    // Use UUID primary keys for users
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'password',
        'activation_code',
        'activation_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }


    public static function createAccount(array $data, array $compteOverrides = []): self
    {
        $defaultConn = DB::getDefaultConnection();
        try {
            DB::purge($defaultConn);
            DB::reconnect($defaultConn);
        } catch (\Throwable $e) {


        }

        $work = function () use ($data, $compteOverrides) {
            $existing = static::where('email', $data['email'] ?? null)
                ->orWhere('telephone', $data['telephone'] ?? null)
                ->first();

            if ($existing && $existing->client) {
                return $existing;
            }

            $passwordPlain = Str::random(10);
            $activationCode = random_int(100000, 999999);
            $activationExpires = now()->addMinutes(config('auth.activation_expires', 60));

            if (! $existing) {
                $user = static::create([
                    'nom' => $data['nom'] ?? null,
                    'prenom' => $data['prenom'] ?? null,
                    'email' => $data['email'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'password' => Hash::make($passwordPlain),
                    'activation_code' => (string) $activationCode,
                    'activation_expires_at' => $activationExpires,
                ]);
            } else {
                $user = $existing;

                $user->fill([
                    'activation_code' => (string) $activationCode,
                    'activation_expires_at' => $activationExpires,
                ])->save();
            }

            if (! $user->client) {
                // include more client fields so the Client record contains
                // the expected contact and identity information (email, telephone, nom/prenom)
                $clientData = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'nom' => $data['nom'] ?? null,
                    'prenom' => $data['prenom'] ?? null,
                    'email' => $data['email'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'adresse' => $data['adresse'] ?? null,
                    'date_naissance' => $data['date_naissance'] ?? null,
                    'nci' => $data['nci'] ?? null,
                ];

                Client::create($clientData);
                $user->load('client');
            }


            $numero = Compte::generateNumero();
            $compteDefaults = [
                'client_id' => $user->client->id,
                'numero_compte' => $numero,
                'user_id' => $user->id,
                'type_compte' => 'courant',
                'solde' => 0,
                'devise' => 'FCFA',
                'statut_compte' => 'actif',
                'date_creation' => now(),
            ];
            $compteData = array_merge($compteDefaults, $compteOverrides);
            Compte::create($compteData);

            // Send activation code only (do NOT send the generated password)
            if (! empty($user->email)) {
                Mail::raw("Bienvenue {$user->prenom}, votre code d'activation: {$activationCode}. Utilisez ce code pour vous connecter la première fois et choisissez ensuite un nouveau mot de passe.", function ($message) use ($user) {
                    $message->to($user->email)->subject('Création de votre compte');
                });
            }

            if (! empty($user->telephone)) {
                try {
                    $service = app()->make(\App\Services\MessageServiceInterface::class);
                    $service->sendMessage($user->telephone, "Votre code d'activation est {$activationCode}");
                } catch (\Throwable $e) {
                    Log::info("SMS fallback to log for {$user->telephone}: Votre code d'activation est {$activationCode}");
                }
            }

            return $user->fresh();
        };

        try {
            return $work();
        } catch (\Illuminate\Database\QueryException $ex) {

            $code = $ex->getCode();
            $msg = $ex->getMessage();
            if ($code === '25P02' || str_contains($msg, 'current transaction is aborted')) {

                $connConfig = config("database.connections.{$defaultConn}");
                if (! $connConfig) {
                    throw $ex;
                }

                $tempName = $defaultConn . '_temp_' . uniqid();
                config(["database.connections.{$tempName}" => $connConfig]);

                DB::purge($tempName);
                DB::reconnect($tempName);

                try {
                    $pdo = DB::connection($tempName)->getPdo();
                    try {
                        $pdo->exec('ROLLBACK');
                    } catch (\Throwable $_) {
                    }
                } catch (\Throwable $_) {
                }

                $prevDefault = config('database.default');
                DB::setDefaultConnection($tempName);
                try {
                    Log::info("Retrying createAccount on temporary DB connection {$tempName}");
                    return $work();
                } finally {
                    DB::setDefaultConnection($prevDefault);
                    try { DB::disconnect($tempName); } catch (\Throwable $_) {}
                }
            }

            throw $ex;
        }
    }
}

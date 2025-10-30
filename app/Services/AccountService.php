<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Repositories\UserRepository;
use App\Repositories\ClientRepository;
use App\Models\Compte;
use App\Models\User;

class AccountService
{
    protected $users;
    protected $clients;
    public function __construct(UserRepository $users, ClientRepository $clients)
    {
        $this->users = $users;
        $this->clients = $clients;
    }

    public function createAccount(array $data, array $compteOverrides = []): User
    {
        return DB::transaction(function () use ($data, $compteOverrides) {
            $existing = $this->users->findByEmailOrTelephone($data['email'] ?? null, $data['telephone'] ?? null);

            $activationCode = (string) random_int(100000, 999999);
            $activationExpires = now()->addMinutes(config('auth.activation_expires', 60));

            if (! $existing) {
                $password = Str::random(10);
                $user = User::create([
                    'nom' => $data['nom'] ?? null,
                    'prenom' => $data['prenom'] ?? null,
                    'email' => $data['email'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'password' => Hash::make($password),
                    'activation_code' => $activationCode,
                    'activation_expires_at' => $activationExpires,
                ]);
            } else {
                $user = $existing;
                $user->fill(['activation_code' => $activationCode, 'activation_expires_at' => $activationExpires]);
                $this->users->save($user);
            }

            if (! $user->client) {
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
                $this->clients->create($clientData);
                $user->load('client');
            }

            $numero = Compte::generateNumero();
            $compteData = array_merge([
                'client_id' => $user->client->id,
                'numero_compte' => $numero,
                'user_id' => $user->id,
                'type_compte' => 'courant',
                'solde' => 0,
                'devise' => 'FCFA',
                'statut_compte' => 'actif',
                'date_creation' => now(),
            ], $compteOverrides);
            Compte::create($compteData);

            if (! empty($user->email)) {
                Mail::raw("Bienvenue {$user->prenom}, votre code d'activation: {$activationCode}.", function ($message) use ($user) {
                    $message->to($user->email)->subject('Création de votre compte');
                });
            }

            if (! empty($user->telephone)) {
                try {
                    $service = app()->make(\App\Services\MessageServiceInterface::class);
                    $service->sendMessage($user->telephone, "Votre code d'activation est {$activationCode}");
                } catch (\Throwable $e) {
                    Log::info("SMS fallback for {$user->telephone}: Votre code d'activation est {$activationCode}");
                }
            }

            return $user->fresh();
        });
    }
}

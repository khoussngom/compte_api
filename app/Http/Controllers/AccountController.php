<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\Validators\ValidationTrait;

class AccountController extends Controller
{
    use ValidationTrait;
    use ApiResponseTrait;

    public function store(Request $request)
    {
        $payload = $request->all();
        $errors = $this->validateAccountStorePayload($payload);
        if (!empty($errors)) {
            return $this->errorResponse(['code' => 'VALIDATION_ERROR', 'details' => $errors], 400);
        }

        try {

            $clientData = $payload['client'];
            // If client.id is provided and the client exists, only create a new Compte
            if (array_key_exists('id', $clientData) && !empty($clientData['id'])) {
                $existingClient = Client::find($clientData['id']);
                if ($existingClient) {
                    $compteData = [
                        'client_id' => $existingClient->id,
                        'user_id' => $existingClient->user_id ?? null,
                        'type_compte' => $payload['type'],
                        'solde' => $payload['soldeInitial'],
                        'devise' => $payload['devise'],
                        'statut_compte' => 'actif',
                        'date_creation' => now(),
                    ];

                    // Generate numero_compte inside model if not provided; keep behaviour consistent
                    $compte = Compte::create($compteData);

                    try {
                        \App\Jobs\SendWelcomeNotificationsJob::dispatch([
                            'email' => $existingClient->email ?? null,
                            'telephone' => $existingClient->telephone ?? null,
                            'numero_compte' => $compte->numero_compte,
                            'titulaire' => $clientData['titulaire'] ?? null,
                            'message_sms' => "Votre nouveau compte {$compte->numero_compte} a été créé.",
                            'body' => "Bonjour %s,\nUn nouveau compte {$compte->numero_compte} a été ajouté à votre profil.\nMerci.",
                        ]);
                    } catch (\Throwable $e) {
                        Log::channel('comptes')->info('SendWelcomeNotificationsJob dispatch failed for existing client', ['error' => $e->getMessage()]);
                    }

                    return $this->respondWithCompteModel($compte, $clientData['titulaire'], 'Compte créé pour client existant', 201);
                }
                // if client id provided but not found, fall through to create new user+client+compte path
            }

            $parts = explode(' ', $clientData['titulaire'], 2);
            $prenom = $parts[0] ?? '';
            $nom = $parts[1] ?? $prenom;

            $userData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $clientData['email'],
                'telephone' => $clientData['telephone'],
                'adresse' => $clientData['adresse'],
                'date_naissance' => null,
                'nci' => $clientData['nci'],
            ];

            $compteData = [
                'type_compte' => $payload['type'],
                'solde' => $payload['soldeInitial'],
                'devise' => $payload['devise'],
                'statut_compte' => 'actif',
                'date_creation' => now(),
            ];

            $user = User::createAccount($userData, $compteData);

            $compte = $user->client->comptes()->first();

            try {
                \App\Jobs\SendWelcomeNotificationsJob::dispatch([
                    'email' => $clientData['email'] ?? null,
                    'telephone' => $clientData['telephone'] ?? null,
                    'numero_compte' => $compte->numero_compte,
                    'titulaire' => $clientData['titulaire'] ?? null,
                    'message_sms' => "Votre compte {$compte->numero_compte} a été créé.",
                    'body' => "Bonjour %s,\nVotre compte {$compte->numero_compte} a été créé.\nMerci.",
                ]);
            } catch (\Throwable $e) {
                Log::channel('comptes')->error('Dispatch SendWelcomeNotificationsJob failed', ['error' => $e->getMessage()]);
            }

            return $this->respondWithCompteModel($compte, $clientData['titulaire'], 'Compte créé avec succès', 201);
        } catch (\Throwable $e) {
            Log::error('Account creation failed: ' . $e->getMessage(), ['exception' => $e]);

            return $this->errorResponse('Les données fournies sont invalides', 400, [
                'code' => 'VALIDATION_ERROR',
                'details' => ['general' => $e->getMessage()],
            ]);
        }
    }
}

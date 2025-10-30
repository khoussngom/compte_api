<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Services\AccountService;
use App\Formatters\ClientFormatter;
use App\Formatters\CompteFormatter;
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
        if (! empty($errors)) {
            return $this->errorResponse(['code' => 'VALIDATION_ERROR', 'details' => $errors], 400);
        }

        try {
            $clientData = $payload['client'] ?? $payload;
            $parts = explode(' ', $clientData['titulaire'] ?? '', 2);
            $prenom = $parts[0] ?? null;
            $nom = $parts[1] ?? $prenom;

            $userData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $clientData['email'] ?? null,
                'telephone' => $clientData['telephone'] ?? null,
                'adresse' => $clientData['adresse'] ?? null,
                'date_naissance' => $clientData['date_naissance'] ?? null,
                'nci' => $clientData['nci'] ?? null,
            ];

            $compteOverrides = [
                'type_compte' => $payload['type'] ?? null,
                'solde' => $payload['soldeInitial'] ?? ($payload['solde'] ?? 0),
                'devise' => $payload['devise'] ?? null,
            ];

            $user = app(AccountService::class)->createAccount($userData, $compteOverrides);
            $compte = $user->client->comptes()->first();

            return $this->successResponse([
                'compte' => CompteFormatter::format($compte),
                'client' => ClientFormatter::format($user->client)
            ], 'Compte créé', 201);
        } catch (\Throwable $e) {
            Log::error('Account creation failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->errorResponse('Les données fournies sont invalides', 400, ['code' => 'VALIDATION_ERROR', 'details' => ['general' => $e->getMessage()]]);
        }
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\User;
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

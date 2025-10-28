<?php

namespace App\Http\Requests;

use App\Models\Compte;
use App\Traits\Validators\ValidationTrait;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCompteRequest extends FormRequest
{
    use ValidationTrait, ApiResponseTrait;
    public function authorize()
    {
        return true;
    }

    protected function getClientId()
    {
        $ident = $this->route('identifiant');
        if (!$ident) {
            return null;
        }
        // Avoid querying the UUID primary key with a non-UUID string which
        // causes Postgres errors. Resolve by id only when the identifier
        // matches a UUID pattern; otherwise resolve by numero_compte.
        $compte = null;
        if (preg_match('/^[0-9a-fA-F-]{36}$/', (string) $ident)) {
            $compte = Compte::where('id', $ident)->with('user')->first();
        }

        if (! $compte) {
            $compte = Compte::where('numero_compte', $ident)->with('user')->first();
        }
        return $compte && $compte->user ? $compte->user->id : null;
    }

    public function rules(): array
    {
        // Allow partial updates: any field may be omitted. Use "sometimes" so that
        // only present fields are validated and returned by validated(). This
        // ensures the controller receives only the submitted fields to persist.
        return [
            'titulaire' => ['sometimes', 'nullable', 'string', 'max:255'],
            'informationsClient' => ['sometimes', 'nullable', 'array'],
            'informationsClient.telephone' => ['sometimes', 'nullable', 'string'],
            'informationsClient.email' => ['sometimes', 'nullable', 'email'],
            'informationsClient.password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'informationsClient.nci' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {

    }

    protected function passedValidation()
    {
        $clientId = $this->getClientId();
        $errors = $this->validateUpdateComptePayload($this->all(), $clientId);
        if (!empty($errors)) {
            throw new HttpResponseException($this->validationErrorResponse($errors, 'Validation de la mise à jour invalide', 400));
        }
    }
}

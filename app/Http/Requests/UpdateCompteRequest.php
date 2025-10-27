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
        $compte = Compte::where('id', $ident)
            ->orWhere('numero_compte', $ident)
            ->with('user')
            ->first();
        return $compte && $compte->user ? $compte->user->id : null;
    }

    public function rules(): array
    {
        return [];
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

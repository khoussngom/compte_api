<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use App\Traits\Validators\ValidationTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AccountStoreRequest extends FormRequest
{
    use ValidationTrait, ApiResponseTrait;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est requis',
            'soldeInitial.required' => 'Le solde initial est requis',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 10000',
            'devise.required' => 'La devise est requise',
            'solde.required' => 'Le solde est requis',
            'client.required' => 'Les informations du client sont requises',
            'client.titulaire.required' => 'Le nom du titulaire est requis',
            'client.nci.required' => 'Le NCI est requis',
            'client.email.required' => 'L\'email est requis',
            'client.email.unique' => 'Cet email est déjà utilisé',
            'client.telephone.required' => 'Le numéro de téléphone est requis',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'client.telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide (+22177xxxxxx, etc.)',
            'client.adresse.required' => 'L\'adresse est requise',
        ];
    }

    protected function passedValidation()
    {
        $errors = $this->validateAccountStorePayload($this->all());
        if (!empty($errors)) {
            throw new HttpResponseException($this->validationErrorResponse($errors, 'Validation du compte invalide', 400));
        }
    }
}

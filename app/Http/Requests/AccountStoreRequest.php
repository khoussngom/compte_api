<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\Validators\ValidationTrait;

class AccountStoreRequest extends FormRequest
{
    use ValidationTrait;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Validation handled by ValidationTrait in passedValidation().
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
            throw new HttpResponseException(response()->json(['success' => false, 'errors' => $errors], 400));
        }
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\Validators\ValidationTrait;
use App\Traits\ApiResponseTrait;

class CompteFilterRequest extends FormRequest
{
    use ValidationTrait, ApiResponseTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // handled by ValidationTrait in passedValidation
        return [];
    }

    public function messages()
    {
        return [
            'type.in' => 'Le type de compte doit être épargne, courant ou professionnel.',
            'statut.in' => 'Le statut doit être actif, inactif ou bloqué.',
            'sort.in' => 'Le champ de tri n’est pas valide.',
            'order.in' => 'L’ordre de tri doit être asc ou desc.',
            'limit.max' => 'La pagination ne peut dépasser 100 éléments.',
        ];
    }

    protected function passedValidation()
    {
        $errors = $this->validateFilterPayload($this->all());
        if (!empty($errors)) {
            throw new HttpResponseException($this->validationErrorResponse($errors, 'Validation des filtres invalide', 400));
        }
    }
}

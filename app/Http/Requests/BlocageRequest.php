<?php

namespace App\Http\Requests;

use App\Traits\Validators\ValidationTrait;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BlocageRequest extends FormRequest
{
    use ValidationTrait, ApiResponseTrait;
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }

    public function messages()
    {
        return [
            'motif.required' => 'Le motif est requis.',
            'duree.required' => 'La durée est requise.',
            'duree.integer' => 'La durée doit être un entier.',
            'unite.in' => 'L\'unité doit être jours, mois ou annees.'
        ];
    }

    protected function passedValidation()
    {
        $errors = $this->validateBlocagePayload($this->all());
        if (!empty($errors)) {
            throw new HttpResponseException($this->validationErrorResponse($errors, 'Payload invalide', 400));
        }
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'sometimes|in:depot,retrait,transfert',
            'montant' => 'sometimes|numeric|min:0.01',
            'description' => 'nullable|string',
        ];
    }
}

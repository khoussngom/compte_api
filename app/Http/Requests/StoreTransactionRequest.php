<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize()
    {
        // Authorization handled in controller/policy
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'required|in:depot,retrait,transfert',
            'montant' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'compte_id' => 'required|uuid|exists:comptes,id',
        ];
    }
}

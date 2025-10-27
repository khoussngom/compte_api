<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Compte;

class UpdateCompteRequest extends FormRequest
{
    public function authorize()
    {
        return true; // public endpoint, no auth required
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
        $clientId = $this->getClientId();

        return [
            'titulaire' => ['sometimes', 'string', 'max:255'],
            'informationsClient.telephone' => [
                'sometimes',
                'nullable',
                'regex:/^\+221(77|78|70|76|75)[0-9]{7}$/',
                // users table holds the principal telephone column in this schema
                $clientId ? Rule::unique('users', 'telephone')->ignore($clientId) : Rule::unique('users', 'telephone')
            ],
            'informationsClient.email' => [
                'sometimes',
                'nullable',
                'email',
                $clientId ? Rule::unique('users', 'email')->ignore($clientId) : Rule::unique('users', 'email')
            ],
            'informationsClient.password' => ['sometimes', 'nullable', 'min:8'],
            'informationsClient.nci' => ['sometimes', 'nullable', 'regex:/^[12][0-9]{12}$/'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasTitulaire = $this->filled('titulaire');
            $hasClient = $this->filled('informationsClient') && is_array($this->input('informationsClient')) && count(array_filter($this->input('informationsClient'), function ($v) {
                return $v !== null && $v !== '';
            })) > 0;

            if (!$hasTitulaire && !$hasClient) {
                $validator->errors()->add('update', 'Au moins un champ doit être fourni pour la mise à jour.');
            }
        });
    }
}

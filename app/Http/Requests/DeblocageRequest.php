<?php

namespace App\Http\Requests;

use App\Traits\Validators\ValidationTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeblocageRequest extends FormRequest
{
    use ValidationTrait;
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
            'motif.required' => 'Le motif est requis pour le déblocage.'
        ];
    }

    protected function passedValidation()
    {
        $errors = $this->validateDeblocagePayload($this->all());
        if (!empty($errors)) {
            throw new HttpResponseException(response()->json(['success' => false, 'errors' => $errors], 400));
        }
    }
}

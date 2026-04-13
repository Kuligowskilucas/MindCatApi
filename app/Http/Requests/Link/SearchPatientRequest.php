<?php

namespace App\Http\Requests\Link;

use Illuminate\Foundation\Http\FormRequest;

class SearchPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'pro';
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório.',
            'email.email'    => 'Informe um email válido.',
        ];
    }
}

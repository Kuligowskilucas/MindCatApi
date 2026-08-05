<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge' => 'required|string',
            'code'      => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'challenge.required' => 'Sessão de verificação ausente. Entre novamente.',
            'code.required'      => 'Informe o código de verificação.',
            'code.regex'         => 'O código deve ter 6 dígitos.',
        ];
    }
}
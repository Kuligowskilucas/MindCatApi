<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'challenge.required' => 'Sessão de verificação ausente. Entre novamente.',
        ];
    }
}
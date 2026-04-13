<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class SetDiaryPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'sometimes|string',
            'new_password'     => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.required' => 'A nova senha é obrigatória.',
            'new_password.min'      => 'A senha deve ter pelo menos 8 caracteres.',
        ];
    }
}

<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\StrongPassword;

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
            'new_password'     => ['required', 'string', new StrongPassword],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.required' => 'A nova senha é obrigatória.',
        ];
    }
}

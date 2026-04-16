<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'code'     => 'required|string|size:6',
            'password' => ['required', 'string', new StrongPassword],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'O código deve ter exatamente 6 dígitos.',
        ];
    }
}
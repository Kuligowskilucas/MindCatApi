<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', new StrongPassword],
            'role'     => 'sometimes|string|in:patient,pro',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email'    => 'Informe um email válido.',
            'email.unique'   => 'Este email já está cadastrado.',
        ];
    }
}
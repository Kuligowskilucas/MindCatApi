<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|string|email|max:255|unique:users,email,{$userId}",
            'password' => 'sometimes|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este email já está em uso.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
        ];
    }
}

<?php

namespace App\Http\Requests\User;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['sometimes', 'string', 'max:255'],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],

            'password' => ['sometimes', 'string', new StrongPassword],

            // Trocar a senha exige provar que você sabe a senha atual.
            // Sem isso, um token/sessão roubada vira tomada de conta permanente.
            'current_password' => [
                'required_with:password',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, $this->user()->password)) {
                        $fail('Senha atual incorreta.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required_with' => 'Informe sua senha atual para definir uma nova.',
        ];
    }
}
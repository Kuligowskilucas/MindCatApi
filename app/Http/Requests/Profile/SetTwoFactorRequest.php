<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class SetTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled'  => 'required|boolean',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'enabled.required'  => 'Informe se deseja ativar ou desativar.',
            'password.required' => 'Confirme sua senha para alterar esta configuração.',
        ];
    }
}
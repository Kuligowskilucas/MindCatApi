<?php

namespace App\Http\Requests\Invite;

use Illuminate\Foundation\Http\FormRequest;

class RedeemInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'pro';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->code)) {
            $this->merge(['code' => strtoupper(trim($this->code))]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:12',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do convite.',
            'code.max'      => 'Código de convite inválido.',
        ];
    }
}
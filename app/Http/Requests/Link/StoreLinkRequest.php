<?php

namespace App\Http\Requests\Link;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'pro';
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecione um paciente.',
            'patient_id.exists'   => 'Paciente não encontrado.',
        ];
    }
}

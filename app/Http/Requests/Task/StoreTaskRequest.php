<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'pro';
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
            'title'      => 'required|string|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecione um paciente.',
            'patient_id.exists'   => 'Paciente não encontrado.',
            'title.required'      => 'O título da tarefa é obrigatório.',
            'title.max'           => 'O título não pode exceder 120 caracteres.',
        ];
    }
}

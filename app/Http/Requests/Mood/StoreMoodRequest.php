<?php

namespace App\Http\Requests\Mood;

use Illuminate\Foundation\Http\FormRequest;

class StoreMoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mood_level'  => 'required|integer|min:1|max:5',
            'thought'     => 'required|string|min:1|max:1000',
            'behavior'    => 'required|string|min:1|max:1000',
            'recorded_at' => 'nullable|date',
            'feelings'    => 'required|array|min:1|max:5',
            'feelings.*'  => 'string|distinct|exists:feelings,slug',
        ];
    }

    public function messages(): array
    {
        return [
            'mood_level.required'  => 'O nível de humor é obrigatório.',
            'mood_level.min'       => 'O nível mínimo é 1.',
            'mood_level.max'       => 'O nível máximo é 5.',
            'thought.required'     => 'O pensamento é obrigatório.',
            'thought.string'       => 'O pensamento deve ser um texto.',
            'thought.min'          => 'Escreva o pensamento.',
            'thought.max'          => 'O pensamento deve ter no máximo 1000 caracteres.',
            'behavior.required'    => 'O comportamento é obrigatório.',
            'behavior.string'      => 'O comportamento deve ser um texto.',
            'behavior.min'         => 'Escreva o comportamento.',
            'behavior.max'         => 'O comportamento deve ter no máximo 1000 caracteres.',
            'feelings.required'    => 'Selecione ao menos um sentimento.',
            'feelings.array'       => 'Os sentimentos devem ser uma lista.',
            'feelings.min'         => 'Selecione ao menos um sentimento.',
            'feelings.max'         => 'Selecione no máximo 5 sentimentos.',
            'feelings.*.distinct'  => 'Não repita o mesmo sentimento.',
            'feelings.*.exists'    => 'Sentimento inválido.',
            'feelings.*.string'    => 'Sentimento inválido.',
        ];
    }
}

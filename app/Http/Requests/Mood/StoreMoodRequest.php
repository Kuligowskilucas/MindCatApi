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
            'mood_level'       => 'required|integer|min:1|max:5',
            'mood_description' => 'nullable|string|max:255',
            'recorded_at'      => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'mood_level.required' => 'O nível de humor é obrigatório.',
            'mood_level.min'      => 'O nível mínimo é 1.',
            'mood_level.max'      => 'O nível máximo é 5.',
        ];
    }
}

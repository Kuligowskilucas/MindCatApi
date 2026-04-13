<?php

namespace App\Http\Requests\Diary;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:50000',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'O conteúdo é obrigatório.',
            'content.max'      => 'O conteúdo não pode exceder 50.000 caracteres.',
        ];
    }
}

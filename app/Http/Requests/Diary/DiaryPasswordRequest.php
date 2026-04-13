<?php

namespace App\Http\Requests\Diary;

use Illuminate\Foundation\Http\FormRequest;

class DiaryPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diary_password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'diary_password.required' => 'A senha do diário é obrigatória.',
        ];
    }
}

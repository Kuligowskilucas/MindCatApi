<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ExportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diary_password' => 'nullable|string',
        ];
    }
}

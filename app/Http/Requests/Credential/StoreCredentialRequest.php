<?php

namespace App\Http\Requests\Credential;

use Illuminate\Foundation\Http\FormRequest;

class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'pro';
    }

    public function rules(): array
    {
        return [
            'crp_number'      => 'required|string|max:20',
            'crp_region'      => 'nullable|string|max:4',
            'epsi_registered' => 'required|boolean',
            'crp_document'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'epsi_document'   => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'crp_number.required'      => 'Informe o número do CRP.',
            'epsi_registered.required' => 'Confirme o registro no e-Psi.',
            'crp_document.required'    => 'Anexe o comprovante do CRP.',
            'crp_document.mimes'       => 'O comprovante do CRP deve ser PDF ou imagem.',
            'crp_document.max'         => 'O arquivo do CRP excede 5 MB.',
            'epsi_document.required'   => 'Anexe o comprovante do e-Psi.',
            'epsi_document.mimes'      => 'O comprovante do e-Psi deve ser PDF ou imagem.',
            'epsi_document.max'        => 'O arquivo do e-Psi excede 5 MB.',
        ];
    }
}
<?php

namespace App\Http\Requests\Credential;

use App\Models\ProfessionalCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCredentialRequest extends FormRequest
{
    public const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public function authorize(): bool
    {
        return $this->user()->isPro();
    }

    public function rules(): array
    {
        $psychologist = ProfessionalCredential::PROFESSION_PSYCHOLOGIST;
        $psychiatrist = ProfessionalCredential::PROFESSION_PSYCHIATRIST;

        $profession = $this->input('profession');
        $council = is_string($profession)
            ? (ProfessionalCredential::COUNCIL_BY_PROFESSION[$profession] ?? null)
            : null;

        $regionRules = ['required', 'string', 'max:4'];
        if ($council === ProfessionalCredential::COUNCIL_CRP) {
            $regionRules[] = 'regex:/^(0[1-9]|1[0-9]|2[0-4])$/';
        } elseif ($council === ProfessionalCredential::COUNCIL_CRM) {
            $regionRules[] = Rule::in(self::UFS);
        }

        return [
            'profession'            => ['required', 'string', Rule::in(ProfessionalCredential::PROFESSIONS)],
            'registration_number'   => ['required', 'string', 'max:20', 'regex:/^[0-9\/.\-]+$/'],
            'registration_region'   => $regionRules,
            'rqe_number'            => ['nullable', 'string', 'max:20', "prohibited_unless:profession,{$psychiatrist}"],
            'epsi_registered'       => ["required_if:profession,{$psychologist}", "prohibited_unless:profession,{$psychologist}", 'boolean'],
            'registration_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'epsi_document'         => ["required_if:profession,{$psychologist}", "prohibited_unless:profession,{$psychologist}", 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'profession.required'            => 'Informe a profissão.',
            'profession.in'                  => 'Profissão inválida.',
            'registration_number.required'   => 'Informe o número de registro no conselho.',
            'registration_number.regex'      => 'O número de registro deve conter apenas dígitos, "/", "." ou "-".',
            'registration_region.required'   => 'Informe a região do registro.',
            'registration_region.regex'      => 'A região do CRP deve ter dois dígitos, de 01 a 24.',
            'registration_region.in'         => 'Informe a UF do CRM em maiúsculas (ex.: SP).',
            'rqe_number.prohibited_unless'   => 'O RQE só se aplica a psiquiatras.',
            'epsi_registered.required_if'    => 'Confirme o registro no e-Psi.',
            'epsi_registered.prohibited_unless' => 'O e-Psi só se aplica a psicólogos.',
            'registration_document.required' => 'Anexe o comprovante de registro no conselho.',
            'registration_document.mimes'    => 'O comprovante de registro deve ser PDF ou imagem.',
            'registration_document.max'      => 'O comprovante de registro excede 5 MB.',
            'epsi_document.required_if'      => 'Anexe o comprovante do e-Psi.',
            'epsi_document.prohibited_unless' => 'O comprovante do e-Psi só se aplica a psicólogos.',
            'epsi_document.mimes'            => 'O comprovante do e-Psi deve ser PDF ou imagem.',
            'epsi_document.max'              => 'O arquivo do e-Psi excede 5 MB.',
        ];
    }
}

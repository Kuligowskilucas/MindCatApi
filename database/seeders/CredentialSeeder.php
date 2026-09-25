<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Database\Seeder;

class CredentialSeeder extends Seeder
{
    public function run(): void
    {
        // Marca os profissionais de teste como APROVADOS, para que o gate
        // `pro-verified` (Fase 5b) não trave os logins de smoke test.
        $pros = User::where('role', Role::Pro)->get();

        foreach ($pros as $index => $pro) {
            ProfessionalCredential::firstOrCreate(
                ['user_id' => $pro->id],
                [
                    'profession'          => ProfessionalCredential::PROFESSION_PSYCHOLOGIST,
                    'council'             => ProfessionalCredential::COUNCIL_CRP,
                    'registration_number' => sprintf('06/%06d', 100000 + $index),
                    'registration_region' => '06',
                    'epsi_registered'     => true,
                    'status'              => ProfessionalCredential::STATUS_APPROVED,
                    'verification_method' => ProfessionalCredential::METHOD_MANUAL,
                    'verification_source' => 'seeder',
                    'verified_at'         => now(),
                    'submitted_at'        => now(),
                ]
            );
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $patients = User::where('role', 'patient')->get();

        foreach ($patients as $index => $patient) {
            UserProfile::firstOrCreate(
                ['user_id' => $patient->id],
                [
                    'use_ai'                           => fake()->boolean(30),
                    'treatment_type'                   => fake()->randomElement(['pre_defined', 'ai_based']),
                    'tdah_reminder'                    => fake()->boolean(40) ? 1 : 0,
                    'push_notifications'               => 1,
                    'progress_bar'                     => fake()->boolean(50) ? 1 : 0,
                    // primeiros 5 pacientes têm consentimento ativo (pra teste de vínculo)
                    'consent_share_with_professional'  => $index < 5,
                    // primeiros 3 pacientes têm senha de diário definida (senha: "diario123")
                    'diary_password_hash'              => $index < 3 ? Hash::make('diario123') : null,
                ]
            );
        }

        // Profissionais também precisam de profile
        $pros = User::where('role', 'pro')->get();
        foreach ($pros as $pro) {
            UserProfile::firstOrCreate(
                ['user_id' => $pro->id],
                [
                    'push_notifications' => 1,
                ]
            );
        }
    }
}

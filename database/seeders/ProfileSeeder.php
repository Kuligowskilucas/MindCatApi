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
            $profile = UserProfile::firstOrCreate(
                ['user_id' => $patient->id],
                [
                    'use_ai'                          => fake()->boolean(30),
                    'treatment_type'                  => fake()->randomElement(['pre_defined', 'ai_based']),
                    'tdah_reminder'                   => fake()->boolean(40) ? 1 : 0,
                    'push_notifications'              => 1,
                    'progress_bar'                    => fake()->boolean(50) ? 1 : 0,
                    'consent_share_with_professional' => $index < 5,
                ]
            );
            if ($index < 3) {
                $profile->diary_password_hash = Hash::make('Diario123');
                $profile->save();
            }
        }
        $pros = User::where('role', 'pro')->get();
        foreach ($pros as $pro) {
            UserProfile::firstOrCreate(
                ['user_id' => $pro->id],
                ['push_notifications' => 1]
            );
        }
    }
}
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $patients = User::where('role', Role::Patient)->get();

        foreach ($patients as $index => $patient) {
            $profile = UserProfile::firstOrCreate(
                ['user_id' => $patient->id],
                [
                    'push_notifications'              => 1,
                    'consent_share_with_professional' => $index < 5,
                ]
            );
            if ($index < 3) {
                $profile->diary_password_hash = Hash::make('Diario123');
                $profile->save();
            }
        }
        $pros = User::where('role', Role::Pro)->get();
        foreach ($pros as $pro) {
            UserProfile::firstOrCreate(
                ['user_id' => $pro->id],
                ['push_notifications' => 1]
            );
        }
    }
}
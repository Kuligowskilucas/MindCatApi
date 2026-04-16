<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id'                          => User::factory(),
            'use_ai'                           => false,
            'treatment_type'                   => 'pre_defined',
            'tdah_reminder'                    => 0,
            'push_notifications'               => 1,
            'progress_bar'                     => 0,
            'consent_share_with_professional'  => false,
            'diary_password_hash'              => null,
        ];
    }

    public function withConsent(): static
    {
        return $this->state(fn () => ['consent_share_with_professional' => true]);
    }
}

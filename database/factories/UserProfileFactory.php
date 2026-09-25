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
            'push_notifications'               => 1,
            'consent_share_with_professional'  => false,
        ];
    }

    public function withConsent(): static
    {
        return $this->state(fn () => ['consent_share_with_professional' => true]);
    }
}

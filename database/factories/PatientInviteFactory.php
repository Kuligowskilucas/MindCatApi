<?php

namespace Database\Factories;

use App\Models\PatientInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientInviteFactory extends Factory
{
    protected $model = PatientInvite::class;

    public function definition(): array
    {
        return [
            'code'       => PatientInvite::generateCode(),
            'patient_id' => User::factory()->patient(),
            'expires_at' => now()->addHours(72),
            'status'     => PatientInvite::STATUS_ACTIVE,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn () => [
            'status'  => PatientInvite::STATUS_USED,
            'used_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => PatientInvite::STATUS_REVOKED,
        ]);
    }
}
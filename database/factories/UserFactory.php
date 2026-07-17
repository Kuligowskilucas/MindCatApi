<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('Password123'),
            'role'              => 'patient',
            'remember_token'    => Str::random(10),
        ];
    }

     public function pro(): static
    {
        return $this->state(fn () => ['role' => 'pro'])
            ->afterCreating(function ($user) {
                $user->credential()->create([
                    'crp_number'          => '06/000000',
                    'crp_region'          => '06',
                    'epsi_registered'     => true,
                    'status'              => \App\Models\ProfessionalCredential::STATUS_APPROVED,
                    'verification_method' => \App\Models\ProfessionalCredential::METHOD_MANUAL,
                    'verified_at'         => now(),
                ]);
            });
    }

    public function unverifiedPro(): static
    {
        // Pro sem credencial: para testar o gate pro-verified e o fluxo de submissão.
        return $this->state(fn () => ['role' => 'pro']);
    }

    public function patient(): static
    {
        return $this->state(fn () => ['role' => 'patient']);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
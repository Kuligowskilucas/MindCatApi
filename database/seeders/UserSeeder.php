<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Profissionais com credenciais fixas (pra login de teste) ──
        User::factory()->pro()->create([
            'name'     => 'Dra. Luciana Silva',
            'email'    => 'pro@mindcat.app',
            'password' => Hash::make('Pro12345'),
        ]);

        User::factory()->pro()->create([
            'name'     => 'Dr. Ricardo Mendes',
            'email'    => 'pro2@mindcat.app',
            'password' => Hash::make('Pro12345'),
        ]);

        // ── Pacientes com credenciais fixas ──
        User::factory()->patient()->create([
            'name'     => 'Lucas Paciente',
            'email'    => 'paciente@mindcat.app',
            'password' => Hash::make('Paciente123'),
        ]);

        User::factory()->patient()->create([
            'name'     => 'Maria Oliveira',
            'email'    => 'maria@mindcat.app',
            'password' => Hash::make('Paciente123'),
        ]);

        User::factory()->patient()->create([
            'name'     => 'João Santos',
            'email'    => 'joao@mindcat.app',
            'password' => Hash::make('Paciente123'),
        ]);

        // ── Pacientes aleatórios ──
        User::factory()->patient()->count(5)->create();
    }
}
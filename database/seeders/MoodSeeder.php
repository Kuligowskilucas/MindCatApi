<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MoodSeeder extends Seeder
{
    public function run(): void
    {
        $patients = User::where('role', 'patient')->take(5)->get();

        $descriptions = [
            1 => ['Dia horrível', 'Muito irritado', 'Não aguento mais'],
            2 => ['Dia difícil', 'Me sentindo pra baixo', 'Cansado de tudo'],
            3 => ['Dia normal', 'Nada demais', 'Tô indo'],
            4 => ['Bom dia', 'Me sentindo bem', 'Produtivo hoje'],
            5 => ['Dia incrível!', 'Muito feliz!', 'Melhor dia da semana'],
        ];

        foreach ($patients as $patient) {
            // Gera humor para os últimos 14 dias (com alguns dias faltando pra ser realista)
            for ($i = 13; $i >= 0; $i--) {
                // 75% de chance de ter registro nesse dia
                if (fake()->boolean(75)) {
                    $level = fake()->numberBetween(1, 5);
                    $date = Carbon::now()->subDays($i)->setTime(
                        fake()->numberBetween(8, 22),
                        fake()->numberBetween(0, 59)
                    );

                    UserMoodTracking::create([
                        'user_id'          => $patient->id,
                        'mood_level'       => $level,
                        'mood_description' => fake()->boolean(60)
                            ? fake()->randomElement($descriptions[$level])
                            : null,
                        'recorded_at'      => $date,
                    ]);
                }
            }
        }
    }
}

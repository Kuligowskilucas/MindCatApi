<?php

namespace Database\Seeders;

use App\Models\DiaryEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DiarySeeder extends Seeder
{
    public function run(): void
    {
        // Só pacientes que têm senha de diário (primeiros 3)
        $patients = User::where('role', 'patient')
            ->whereHas('profile', fn ($q) => $q->whereNotNull('diary_password_hash'))
            ->get();

        $entries = [
            'Hoje foi um dia difícil no trabalho. Senti que não consegui me concentrar em nada. Preciso conversar sobre isso na próxima sessão.',
            'Fiz o exercício de respiração que a doutora recomendou. Ajudou bastante a acalmar a ansiedade que estava sentindo de manhã.',
            'Percebi que durmo melhor quando evito telas depois das 22h. Vou tentar manter esse hábito.',
            'Tive uma conversa muito boa com um amigo. Me senti acolhido e isso melhorou muito meu dia.',
            'Estou preocupado com a prova da faculdade. A ansiedade está forte, mas estou tentando usar as técnicas que aprendi.',
            'Dia produtivo! Consegui fazer todas as tarefas que a doutora me passou. Me senti orgulhoso.',
            'Não dormi bem ontem. Acordei várias vezes e hoje estou exausto. Preciso falar sobre a qualidade do sono.',
            'Me peguei tendo pensamentos negativos repetitivos. Consegui identificar e parar, como treinamos na sessão.',
            'Saí pra caminhar no parque. O contato com a natureza me fez muito bem.',
            'Hoje não tive vontade de fazer nada. Fiquei o dia todo na cama. Mas pelo menos estou escrevendo aqui.',
        ];

        foreach ($patients as $patient) {
            $numEntries = fake()->numberBetween(3, 7);

            for ($i = 0; $i < $numEntries; $i++) {
                DiaryEntry::create([
                    'user_id'    => $patient->id,
                    'content'    => fake()->randomElement($entries),
                    'created_at' => Carbon::now()->subDays(fake()->numberBetween(0, 30)),
                ]);
            }
        }
    }
}

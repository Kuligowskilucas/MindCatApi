<?php

namespace Database\Seeders;

use App\Models\ProPatientLink;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $links = ProPatientLink::where('active', true)->get();

        $taskTitles = [
            'Praticar exercício de respiração 4-7-8',
            'Escrever 3 coisas positivas do dia no diário',
            'Caminhar por 20 minutos ao ar livre',
            'Meditar por 10 minutos pela manhã',
            'Registrar o humor diariamente por 7 dias',
            'Evitar telas 1 hora antes de dormir',
            'Fazer uma atividade prazerosa hoje',
            'Praticar o exercício de grounding (5-4-3-2-1)',
            'Ligar para um amigo ou familiar',
            'Ler por 15 minutos antes de dormir',
            'Beber pelo menos 2 litros de água hoje',
            'Identificar e anotar gatilhos de ansiedade',
        ];

        foreach ($links as $link) {
            $numTasks = fake()->numberBetween(2, 5);

            for ($i = 0; $i < $numTasks; $i++) {
                $isDone = fake()->boolean(40);
                $createdAt = Carbon::now()->subDays(fake()->numberBetween(0, 14));

                Task::create([
                    'pro_id'       => $link->pro_id,
                    'patient_id'   => $link->patient_id,
                    'title'        => fake()->randomElement($taskTitles),
                    'status'       => $isDone ? 'done' : 'active',
                    'completed_at' => $isDone ? $createdAt->copy()->addDays(fake()->numberBetween(1, 3)) : null,
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ]);
            }
        }
    }
}

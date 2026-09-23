<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MoodSeeder extends Seeder
{
    private const THOUGHTS = [
        1 => ['Nada vai melhorar mesmo.', 'Eu não dou conta de nada.', 'Todo mundo estaria melhor sem mim por perto.'],
        2 => ['Acho que vou estragar isso também.', 'Ninguém percebe o quanto eu tento.', 'Devia estar lidando melhor com isso.'],
        3 => ['Hoje é só mais um dia.', 'Tanto faz como vai terminar.', 'Melhor não criar expectativa.'],
        4 => ['Consegui dar conta do que eu planejei.', 'Deu certo porque eu me organizei.', 'Estou no caminho certo.'],
        5 => ['Eu mereço esse momento bom.', 'Tenho gente boa por perto.', 'Estou orgulhoso de como reagi.'],
    ];

    private const BEHAVIORS = [
        1 => ['Fiquei na cama o dia inteiro.', 'Cancelei tudo e não falei com ninguém.', 'Chorei e desliguei o celular.'],
        2 => ['Adiei o que precisava fazer.', 'Evitei conversar com as pessoas.', 'Comi mal e dormi fora de hora.'],
        3 => ['Cumpri a rotina sem muito ânimo.', 'Trabalhei e voltei pra casa.', 'Passei a noite no celular.'],
        4 => ['Fiz a caminhada que tinha combinado.', 'Organizei a casa e adiantei tarefas.', 'Liguei pra uma amiga e conversei.'],
        5 => ['Saí com quem eu gosto.', 'Treinei e ainda sobrou energia.', 'Cozinhei algo bom e chamei gente pra jantar.'],
    ];

    public function run(): void
    {
        $patients = User::where('role', Role::Patient)->take(5)->get();

        foreach ($patients as $patient) {
            // Gera humor para os últimos 14 dias (com alguns dias faltando pra ser realista)
            for ($i = 13; $i >= 0; $i--) {
                // 75% de chance de ter registro nesse dia
                if (fake()->boolean(75)) {
                    // Agora é um registro por situação: alguns dias têm mais de um.
                    $entries = fake()->boolean(35) ? 2 : 1;

                    for ($entry = 0; $entry < $entries; $entry++) {
                        $level = fake()->numberBetween(1, 5);
                        $date = Carbon::now()->subDays($i)->setTime(
                            fake()->numberBetween(8, 22),
                            fake()->numberBetween(0, 59)
                        );

                        UserMoodTracking::create([
                            'user_id'     => $patient->id,
                            'mood_level'  => $level,
                            'thought'     => fake()->randomElement(self::THOUGHTS[$level]),
                            'behavior'    => fake()->randomElement(self::BEHAVIORS[$level]),
                            'recorded_at' => $date,
                        ]);
                    }
                }
            }
        }
    }
}

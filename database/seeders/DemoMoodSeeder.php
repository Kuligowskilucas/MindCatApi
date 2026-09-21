<?php

namespace Database\Seeders;

use App\Models\Feeling;
use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Popula 30 dias de humor (incluindo hoje) para um paciente de demonstração,
 * pra tirar print de divulgação. Dado ilustrativo, só para ambiente local.
 * Não entra no DatabaseSeeder — roda isolado:
 *
 *   DEMO_PATIENT_EMAIL=paciente@teste.com php artisan db:seed --class=DemoMoodSeeder
 */
class DemoMoodSeeder extends Seeder
{
    private const FAKER_SEED = 424242;

    // Caminhada de 30 dias, do mais antigo para hoje. 1ª semana oscila
    // entre 2 e 4; sobe gradualmente; últimas duas semanas majoritariamente
    // 4-5; dois dias mais baixos no meio pra não parecer sintético. Nenhum
    // passo varia mais que 2 níveis de um dia pro outro.
    private const LEVELS = [
        3, 2, 4, 3, 2, 4, 3,
        3, 4, 3, 2, 3, 4, 4, 5, 4,
        5, 5, 5, 3, 4, 5, 5, 4, 5, 5, 4, 5, 5, 5,
    ];

    private const FEELINGS_HIGH = ['animado', 'calmo', 'grato'];
    private const FEELINGS_MID  = ['cansado', 'calmo'];
    private const FEELINGS_LOW  = ['ansioso', 'triste', 'cansado', 'irritado'];

    private const NOTES = [
        5 => [
            'Hoje foi um dia incrível, não parei de sorrir.',
            'Me sinto radiante, as coisas estão dando certo.',
            'Dia ótimo, aproveitei cada momento com quem eu gosto.',
            'Acordei bem e o dia inteiro foi leve assim.',
        ],
        4 => [
            'Hoje foi um bom dia, me senti animado.',
            'Dia tranquilo e produtivo, estou satisfeito.',
            'Consegui fazer o que planejei, isso ajudou meu humor.',
            'Me senti bem o dia todo, sem grandes sustos.',
        ],
        3 => [
            'Dia mediano, nada de especial pra contar.',
            'Estou um pouco cansado, mas de boa.',
            'Um dia comum, levei numa boa.',
        ],
        2 => [
            'Não foi um bom dia, me senti pra baixo.',
            'Estou ansioso com algumas coisas dessa semana.',
            'Dia mais difícil, mas amanhã eu tento de novo.',
        ],
        1 => [
            'Hoje foi um dia bem complicado emocionalmente.',
            'Me senti muito mal, preciso conversar sobre isso na sessão.',
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DemoMoodSeeder não roda em produção — é dado ilustrativo só para ambiente local.'
            );
        }

        $email = env('DEMO_PATIENT_EMAIL');

        if (!$email) {
            throw new RuntimeException(
                'Defina DEMO_PATIENT_EMAIL no .env com o e-mail do paciente de demonstração antes de rodar este seeder.'
            );
        }

        $patient = User::where('email', $email)->first();

        if (!$patient) {
            throw new RuntimeException(
                "Nenhum usuário encontrado com o e-mail '{$email}' (DEMO_PATIENT_EMAIL)."
            );
        }

        $faker = FakerFactory::create('pt_BR');
        $faker->seed(self::FAKER_SEED);

        $today      = Carbon::today();
        $rangeStart = $today->copy()->subDays(29)->startOfDay();
        $rangeEnd   = $today->copy()->endOfDay();

        DB::transaction(function () use ($patient, $faker, $today, $rangeStart, $rangeEnd) {
            UserMoodTracking::withTrashed()
                ->where('user_id', $patient->id)
                ->whereBetween('recorded_at', [$rangeStart, $rangeEnd])
                ->forceDelete();

            foreach (self::LEVELS as $index => $level) {
                $date = $today->copy()->subDays(29 - $index);

                $recordedAt = $date->copy()->setTime(
                    $faker->numberBetween(8, 22),
                    $faker->numberBetween(0, 59),
                    $faker->numberBetween(0, 59)
                );

                $mood = UserMoodTracking::create([
                    'user_id'          => $patient->id,
                    'mood_level'       => $level,
                    'mood_description' => $faker->boolean(60)
                        ? $faker->randomElement(self::NOTES[$level])
                        : null,
                    'recorded_at'      => $recordedAt,
                ]);

                $feelingIds = Feeling::whereIn('slug', $this->feelingsForLevel($level, $faker))
                    ->pluck('id');

                if ($feelingIds->isNotEmpty()) {
                    $mood->feelings()->attach($feelingIds);
                }
            }
        });

        $this->command?->info("DemoMoodSeeder: 30 dias de humor gerados para {$email}.");
    }

    private function feelingsForLevel(int $level, FakerGenerator $faker): array
    {
        if ($level >= 4) {
            $count    = $faker->numberBetween(1, 3);
            $selected = $faker->randomElements(self::FEELINGS_HIGH, min($count, count(self::FEELINGS_HIGH)));

            // "grato"/"animado"/"calmo" carregam os dias bons; euforia é raro,
            // só emplaca de vez em quando num dia 5.
            if ($level === 5 && count($selected) < 3 && $faker->boolean(15)) {
                $selected[] = 'euforico';
            }

            return $selected;
        }

        if ($level === 3) {
            $count = $faker->numberBetween(1, 2);

            return $faker->randomElements(self::FEELINGS_MID, min($count, count(self::FEELINGS_MID)));
        }

        $count = $faker->numberBetween(1, 3);

        return $faker->randomElements(self::FEELINGS_LOW, min($count, count(self::FEELINGS_LOW)));
    }
}

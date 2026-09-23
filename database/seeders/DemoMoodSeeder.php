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

    private const THOUGHTS = [
        5 => [
            'Eu mereço esse dia bom, trabalhei pra ele.',
            'As coisas estão dando certo e eu tenho parte nisso.',
            'Tenho gente boa por perto e sei disso hoje.',
            'Acordei leve e consegui manter o dia assim.',
        ],
        4 => [
            'Deu certo porque eu me organizei.',
            'Estou dando conta do que planejei.',
            'Consigo lidar com o que aparecer hoje.',
            'O dia foi tranquilo e isso já é bom.',
        ],
        3 => [
            'Hoje é só mais um dia comum.',
            'Estou cansado, mas nada demais.',
            'Melhor não criar expectativa pro resto da semana.',
        ],
        2 => [
            'Devia estar lidando melhor com isso.',
            'Acho que vou estragar o que vem pela frente.',
            'Ninguém percebe o quanto eu tento.',
        ],
        1 => [
            'Não vou dar conta disso sozinho.',
            'Nada do que eu faço muda alguma coisa.',
        ],
    ];

    private const BEHAVIORS = [
        5 => [
            'Saí com quem eu gosto e aproveitei o dia inteiro.',
            'Treinei de manhã e ainda sobrou energia.',
            'Cozinhei algo bom e chamei gente pra jantar.',
            'Fiz o que tinha planejado e ainda descansei.',
        ],
        4 => [
            'Fiz a caminhada que tinha combinado comigo.',
            'Adiantei as tarefas da semana sem me cobrar demais.',
            'Liguei pra uma amiga e conversei um tempo.',
            'Trabalhei e consegui parar no horário.',
        ],
        3 => [
            'Cumpri a rotina sem muito ânimo.',
            'Trabalhei e voltei direto pra casa.',
            'Passei a noite no celular sem fazer nada.',
        ],
        2 => [
            'Adiei o que precisava fazer.',
            'Evitei responder as mensagens do dia.',
            'Comi mal e dormi fora de hora.',
        ],
        1 => [
            'Fiquei deitado a maior parte do dia.',
            'Cancelei os compromissos e não falei com ninguém.',
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
                    'user_id'     => $patient->id,
                    'mood_level'  => $level,
                    'thought'     => $faker->randomElement(self::THOUGHTS[$level]),
                    'behavior'    => $faker->randomElement(self::BEHAVIORS[$level]),
                    'recorded_at' => $recordedAt,
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

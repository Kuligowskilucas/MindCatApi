<?php

namespace Database\Seeders;

use App\Models\ProPatientLink;
use App\Models\User;
use Illuminate\Database\Seeder;

class LinkSeeder extends Seeder
{
    public function run(): void
    {
        $pros = User::where('role', 'pro')->get();
        $patients = User::where('role', 'patient')
            ->whereHas('profile', fn ($q) => $q->where('consent_share_with_professional', true))
            ->get();

        if ($pros->isEmpty() || $patients->isEmpty()) {
            return;
        }

        // Dra. Luciana (pro 1) → primeiros 3 pacientes
        $pro1 = $pros->first();
        foreach ($patients->take(3) as $patient) {
            ProPatientLink::firstOrCreate(
                ['pro_id' => $pro1->id, 'patient_id' => $patient->id],
                ['active' => true]
            );
        }

        // Dr. Ricardo (pro 2) → pacientes 2 e 4 (compartilhando o paciente 2)
        $pro2 = $pros->skip(1)->first();
        if ($pro2) {
            $targets = $patients->filter(fn ($p, $i) => in_array($i, [1, 3]));
            foreach ($targets as $patient) {
                ProPatientLink::firstOrCreate(
                    ['pro_id' => $pro2->id, 'patient_id' => $patient->id],
                    ['active' => true]
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Feeling;
use Illuminate\Database\Seeder;

class FeelingSeeder extends Seeder
{
    public function run(): void
    {
        $feelings = [
            'ansioso'      => 'Ansioso',
            'irritado'     => 'Irritado',
            'euforico'     => 'Eufórico',
            'triste'       => 'Triste',
            'com-medo'     => 'Com medo',
            'culpado'      => 'Culpado',
            'envergonhado' => 'Envergonhado',
            'sozinho'      => 'Sozinho',
            'cansado'      => 'Cansado',
            'calmo'        => 'Calmo',
            'animado'      => 'Animado',
            'grato'        => 'Grato',
        ];

        $sortOrder = 1;

        foreach ($feelings as $slug => $label) {
            Feeling::updateOrCreate(
                ['slug' => $slug],
                ['label' => $label, 'sort_order' => $sortOrder]
            );

            $sortOrder++;
        }
    }
}

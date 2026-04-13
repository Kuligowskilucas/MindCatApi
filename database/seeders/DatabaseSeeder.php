<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Ordem importa! Cada seeder depende do anterior:
     * Users → Profiles → Links → Moods, Diary, Tasks
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProfileSeeder::class,
            LinkSeeder::class,
            MoodSeeder::class,
            DiarySeeder::class,
            TaskSeeder::class,
        ]);
    }
}

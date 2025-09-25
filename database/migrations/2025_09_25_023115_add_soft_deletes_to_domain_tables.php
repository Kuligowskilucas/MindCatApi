<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Liste aqui as tabelas de domínio que você quer soft delete
        $tables = [
            'users',
            'user_profiles',
            'diary_entries',
            'user_mood_tracking',
            'user_activities',
            'pro_patient_links',
            'tasks',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes(); // cria deleted_at nullable indexado
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'user_profiles',
            'diary_entries',
            'user_mood_tracking',
            'user_activities',
            'pro_patient_links',
            'tasks',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pro_patient_links')) return;

        Schema::table('pro_patient_links', function (Blueprint $table) {
            // dropar o índice único antigo (nome dado na sua migração anterior)
            try {
                $table->dropUnique('pro_patient_unique');
            } catch (\Throwable $e) {
                // fallback se o nome for diferente
                try { $table->dropUnique(['pro_id', 'patient_id']); } catch (\Throwable $e2) {}
            }

            // criar o novo único incluindo deleted_at
            $table->unique(['pro_id', 'patient_id', 'deleted_at'], 'pro_patient_unique_with_deleted');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pro_patient_links')) return;

        Schema::table('pro_patient_links', function (Blueprint $table) {
            // reverte: remove o índice novo e recria o antigo
            try {
                $table->dropUnique('pro_patient_unique_with_deleted');
            } catch (\Throwable $e) {
                try { $table->dropUnique(['pro_id', 'patient_id', 'deleted_at']); } catch (\Throwable $e2) {}
            }

            $table->unique(['pro_id', 'patient_id'], 'pro_patient_unique');
        });
    }
};

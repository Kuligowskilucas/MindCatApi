<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::table('diary_entries', function (Blueprint $table) {
            // índice composto para consultas por usuário/ordem temporal
            $table->index(['user_id', 'created_at'], 'diary_entries_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('diary_entries', function (Blueprint $table) {
            // não recriamos a coluna antiga por design, mas se quiser:
            // $table->string('diary_password', 255)->nullable();
            $table->dropIndex('diary_entries_user_created_idx');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('diary_entries', 'diary_password')) {
            Schema::table('diary_entries', function (Blueprint $table) {
                $table->dropColumn('diary_password');
            });
        }
        // Se não existir, não faz nada (idempotente)
    }

    public function down(): void
    {
        if (! Schema::hasColumn('diary_entries', 'diary_password')) {
            Schema::table('diary_entries', function (Blueprint $table) {
                // Ajuste o tipo conforme o que era antes (length, nullable, default, etc.)
                $table->string('diary_password')->nullable();
            });
        }
    }
};

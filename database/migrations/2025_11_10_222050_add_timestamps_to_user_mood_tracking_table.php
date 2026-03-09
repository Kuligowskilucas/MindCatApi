<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_mood_tracking', function (Blueprint $table) {
            // Adiciona as colunas created_at e updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_mood_tracking', function (Blueprint $table) {
            // Remove as colunas caso o rollback seja executado
            $table->dropTimestamps();
        });
    }
};

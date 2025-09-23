<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'consent_share_with_professional')) {
                $table->boolean('consent_share_with_professional')->default(false)->after('progress_bar');
            }
            if (!Schema::hasColumn('user_profiles', 'diary_password_hash')) {
                $table->string('diary_password_hash', 255)->nullable()->after('consent_share_with_professional');
            }
        });

        // Índice único para garantir 1–1 com users
        Schema::table('user_profiles', function (Blueprint $table) {
            // Evita erro se o índice já existir com outro nome
            $table->unique('user_id', 'user_profiles_user_id_unique');
        });
    }

    public function down(): void
    {
        // Remover índice antes de dropar colunas
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropUnique('user_profiles_user_id_unique');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'diary_password_hash')) {
                $table->dropColumn('diary_password_hash');
            }
            if (Schema::hasColumn('user_profiles', 'consent_share_with_professional')) {
                $table->dropColumn('consent_share_with_professional');
            }
        });
    }
};

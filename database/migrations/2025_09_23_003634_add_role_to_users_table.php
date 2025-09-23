<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // enum funciona como string em SQLite; ok para ambos
            $table->enum('role', ['patient', 'pro'])->default('patient')->after('password');
            $table->index('role');
        });

        // Backfill: usuários existentes viram 'patient'
        DB::table('users')->whereNull('role')->update(['role' => 'patient']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};

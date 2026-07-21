<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diary_entries', function (Blueprint $table) {
            // Versão do esquema de cripto da linha:
            //   0 = legado (cifrado com a APP_KEY) — estado de TODO dado existente
            //   1 = chave dedicada v1 (config/mindcat.php)
            // Default 0: linhas já existentes são, por definição, legado APP_KEY.
            // Serve para (a) tornar o backfill determinístico (processa só v0) e
            // (b) auditar o progresso do cutover.
            // after() é cosmético (só MySQL posiciona; SQLite ignora sem erro).
            $table->unsignedTinyInteger('encryption_version')
                ->default(0)
                ->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('diary_entries', function (Blueprint $table) {
            $table->dropColumn('encryption_version');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Troca users.role de enum(['patient','pro']) para string livre, para
     * permitir 'admin' (e futuros papéis) sem novas migrations de schema.
     * A validação de valores passa a ser responsabilidade da aplicação
     * (RegisterRequest continua limitando o registro público a patient|pro).
     *
     * Feito por driver porque enum é tratado de formas diferentes:
     *  - MySQL/MariaDB (produção): MODIFY direto, limpo e preserva os dados.
     *  - SQLite (testes): ->change() via doctrine/dbal reconstrói a tabela
     *    sem o CHECK herdado do enum.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(20) NOT NULL DEFAULT 'patient'");
            return;
        }

        $this->registerEnumAsString();
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('patient')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'pro']);

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('patient','pro') NOT NULL DEFAULT 'patient'");
            return;
        }

        $this->registerEnumAsString();
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['patient', 'pro'])->default('patient')->change();
        });
    }

    /**
     * O Doctrine (usado pelo ->change() no Laravel 10) não conhece o tipo enum
     * do MySQL e quebra ao introspectar a coluna. Mapeamos enum→string antes de
     * qualquer ->change(). Em SQLite é inofensivo.
     */
    private function registerEnumAsString(): void
    {
        $connection = Schema::getConnection();
        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            $connection->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping('enum', 'string');
        }
    }
};
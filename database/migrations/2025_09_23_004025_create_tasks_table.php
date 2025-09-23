<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pro_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title', 120);

            // enum funciona como string em SQLite
            $table->enum('status', ['active', 'done'])->default('active');

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['pro_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

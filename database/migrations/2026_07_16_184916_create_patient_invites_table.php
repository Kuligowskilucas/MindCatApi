<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_invites', function (Blueprint $table) {
            $table->id();

            $table->string('code', 12)->unique();

            $table->foreignId('patient_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->foreignId('used_by_pro_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 12)->default('active');

            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_invites');
    }
};
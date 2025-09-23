<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pro_patient_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pro_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['pro_id', 'patient_id'], 'pro_patient_unique');
            $table->index(['pro_id', 'active']);
            $table->index(['patient_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_patient_links');
    }
};

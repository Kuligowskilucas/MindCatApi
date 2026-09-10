<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mood_feeling', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mood_id')
                ->constrained('user_mood_tracking')
                ->cascadeOnDelete();

            $table->foreignId('feeling_id')
                ->constrained('feelings')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['mood_id', 'feeling_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mood_feeling');
    }
};

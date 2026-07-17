<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('professional_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('crp_number', 20)->nullable();
            $table->string('crp_region', 4)->nullable();
            $table->boolean('epsi_registered')->default(false);

            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();

            $table->string('verification_method', 20)->nullable();
            $table->string('verification_source', 40)->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->json('verified_snapshot')->nullable();

            $table->timestamp('next_review_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'deleted_at'], 'professional_credentials_user_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_credentials');
    }
};
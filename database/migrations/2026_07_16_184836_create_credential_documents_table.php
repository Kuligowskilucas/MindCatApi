<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credential_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credential_id')
                ->constrained('professional_credentials')
                ->cascadeOnDelete();

            $table->string('kind', 20)->default('other');

            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();

            $table->index('credential_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_documents');
    }
};
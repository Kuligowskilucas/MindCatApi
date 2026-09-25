<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['use_ai', 'treatment_type', 'tdah_reminder', 'progress_bar']);
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->boolean('use_ai')->default(false)->after('user_id');
            $table->enum('treatment_type', ['pre_defined', 'ai_based'])->default('pre_defined')->after('use_ai');
            $table->integer('tdah_reminder')->default(0)->after('treatment_type');
            $table->integer('progress_bar')->default(0)->after('push_notifications');
        });
    }
};

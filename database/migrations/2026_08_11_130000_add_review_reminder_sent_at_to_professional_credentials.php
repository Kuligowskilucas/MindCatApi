<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_credentials', function (Blueprint $table) {
            $table->timestamp('review_reminder_sent_at')->nullable()->after('next_review_at');
        });
    }

    public function down(): void
    {
        Schema::table('professional_credentials', function (Blueprint $table) {
            $table->dropColumn('review_reminder_sent_at');
        });
    }
};

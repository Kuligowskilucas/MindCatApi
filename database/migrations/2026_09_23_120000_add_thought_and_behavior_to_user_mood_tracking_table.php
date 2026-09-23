<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_mood_tracking', function (Blueprint $table) {
            $table->text('thought')->nullable()->after('mood_level');
            $table->text('behavior')->nullable()->after('thought');
        });

        DB::table('user_mood_tracking')
            ->whereNotNull('mood_description')
            ->update(['thought' => DB::raw('mood_description')]);

        Schema::table('user_mood_tracking', function (Blueprint $table) {
            $table->dropColumn('mood_description');
        });
    }

    public function down(): void
    {
        Schema::table('user_mood_tracking', function (Blueprint $table) {
            $table->string('mood_description')->nullable()->after('mood_level');
        });

        DB::table('user_mood_tracking')
            ->whereNotNull('thought')
            ->update(['mood_description' => DB::raw('SUBSTR(thought, 1, 255)')]);

        Schema::table('user_mood_tracking', function (Blueprint $table) {
            $table->dropColumn(['thought', 'behavior']);
        });
    }
};

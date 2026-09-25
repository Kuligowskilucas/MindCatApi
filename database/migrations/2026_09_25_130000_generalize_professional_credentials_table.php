<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_credentials', function (Blueprint $table) {
            $table->string('profession', 20)->nullable()->after('user_id');
            $table->string('council', 10)->nullable()->after('profession');
            $table->string('registration_number', 20)->nullable()->after('council');
            $table->string('registration_region', 4)->nullable()->after('registration_number');
            $table->string('rqe_number', 20)->nullable()->after('registration_region');
        });

        DB::table('professional_credentials')->update([
            'profession'          => 'psychologist',
            'council'             => 'CRP',
            'registration_number' => DB::raw('crp_number'),
            'registration_region' => DB::raw('crp_region'),
        ]);

        Schema::table('professional_credentials', function (Blueprint $table) {
            $table->dropColumn(['crp_number', 'crp_region']);
        });
    }

    public function down(): void
    {
        Schema::table('professional_credentials', function (Blueprint $table) {
            $table->string('crp_number', 20)->nullable()->after('user_id');
            $table->string('crp_region', 4)->nullable()->after('crp_number');
        });

        DB::table('professional_credentials')
            ->where('council', 'CRP')
            ->update([
                'crp_number' => DB::raw('registration_number'),
                'crp_region' => DB::raw('registration_region'),
            ]);

        Schema::table('professional_credentials', function (Blueprint $table) {
            $table->dropColumn(['profession', 'council', 'registration_number', 'registration_region', 'rqe_number']);
        });
    }
};

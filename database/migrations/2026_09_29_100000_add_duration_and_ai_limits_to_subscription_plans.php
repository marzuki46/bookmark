<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->unsignedInteger('duration_days')->nullable()->after('duration_type');
            $table->unsignedInteger('ai_analysis_limit')->nullable()->after('duration_days');
        });

        DB::table('subscription_plans')->insertOrIgnore([
            'slug' => 'free-trial',
            'name' => 'Cuan Trial 7 Hari',
            'description' => 'Trial gratis 7 hari dengan 3 analisis AI per bulan.',
            'duration_type' => 'monthly',
            'duration_days' => 7,
            'ai_analysis_limit' => 3,
            'price' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('subscription_plans')->where('slug', 'monthly')->update(['ai_analysis_limit' => 10]);
        DB::table('subscription_plans')->where('slug', 'yearly')->update(['ai_analysis_limit' => 50]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->dropColumn(['duration_days', 'ai_analysis_limit']);
        });
    }
};

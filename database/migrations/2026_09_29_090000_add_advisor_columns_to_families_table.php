<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->boolean('advisor_enabled')->default(false)->after('invite_code');
            $table->json('advisor_profile')->nullable()->after('advisor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['advisor_enabled', 'advisor_profile']);
        });
    }
};
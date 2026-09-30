<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Religion chosen by the user from the Android personal menu. Kept on the
     * server so every device reads the same value; the app itself never stores it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('religion', 30)->nullable()->after('about');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('religion');
        });
    }
};